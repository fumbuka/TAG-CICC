<?php

namespace Tests\Feature;

use App\Livewire\Sms\Index as SmsIndex;
use App\Models\Department;
use App\Models\LeadershipTitle;
use App\Models\Member;
use App\Models\MemberLeadershipAssignment;
use App\Models\SmsCampaign;
use App\Models\SmsLog;
use App\Models\SmsPurchase;
use App\Models\SmsSetting;
use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SmsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_sms_page_requires_authentication_and_sms_permission(): void
    {
        Permission::create([
            'name' => 'sms.view',
            'guard_name' => 'web',
        ]);

        $this->get('/sms')->assertRedirect('/login');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/sms')
            ->assertForbidden();

        $user->givePermissionTo('sms.view');

        $this->actingAs($user)
            ->get('/sms')
            ->assertOk();
    }

    public function test_sms_purchase_approval_adds_credits_only_to_selected_wallet(): void
    {
        $financeUser = User::factory()->create();
        $this->givePermissions($financeUser, ['sms.view', 'sms.approve']);

        $men = $this->department('Wanaume');
        $women = $this->department('Wanawake');
        $menWallet = $this->departmentWallet($men, 0);
        $womenWallet = $this->departmentWallet($women, 0);

        $purchase = SmsPurchase::create([
            'sms_wallet_id' => $womenWallet->id,
            'requested_by_user_id' => $financeUser->id,
            'sms_quantity' => 800,
            'price_per_sms' => 25,
            'total_amount' => 20000,
            'status' => SmsPurchase::STATUS_PENDING,
        ]);

        Livewire::actingAs($financeUser)
            ->test(SmsIndex::class, ['section' => 'approvals'])
            ->call('approvePurchase', $purchase->id)
            ->assertHasNoErrors()
            ->assertDispatched('sms-purchase-approved');

        $this->assertSame(0, $menWallet->refresh()->balance);
        $this->assertSame(800, $womenWallet->refresh()->balance);
        $this->assertSame(800, $womenWallet->credits_purchased);
        $this->assertDatabaseHas('sms_transactions', [
            'sms_wallet_id' => $womenWallet->id,
            'transaction_type' => SmsTransaction::TYPE_PURCHASE,
            'credits_in' => 800,
            'balance_after' => 800,
        ]);
    }

    public function test_department_user_cannot_send_with_another_department_wallet(): void
    {
        $men = $this->department('Wanaume');
        $women = $this->department('Wanawake');
        $user = $this->departmentLeader($men);
        $this->givePermissions($user, ['sms.view', 'sms.compose']);

        $womenWallet = $this->departmentWallet($women, 10);

        Livewire::actingAs($user)
            ->test(SmsIndex::class, ['section' => 'compose'])
            ->set('compose_wallet_id', (string) $womenWallet->id)
            ->set('compose_title', 'Tangazo la idara')
            ->set('compose_target_type', 'department_members')
            ->set('compose_department_id', (string) $men->id)
            ->set('compose_message', 'Karibuni kwenye ibada ya idara.')
            ->call('sendCampaign')
            ->assertForbidden();
    }

    public function test_campaign_is_blocked_when_wallet_balance_is_insufficient(): void
    {
        $department = $this->department('Vijana');
        $user = $this->departmentLeader($department);
        $this->givePermissions($user, ['sms.view', 'sms.compose']);
        $wallet = $this->departmentWallet($department, 0);
        $this->memberInDepartment($department, '0654000001');

        SmsSetting::current()->update([
            'sending_enabled' => true,
            'sender_id' => 'TAGCICC',
        ]);

        Livewire::actingAs($user)
            ->test(SmsIndex::class, ['section' => 'compose'])
            ->set('compose_wallet_id', (string) $wallet->id)
            ->set('compose_title', 'Ratiba ya vijana')
            ->set('compose_target_type', 'department_members')
            ->set('compose_department_id', (string) $department->id)
            ->set('compose_message', 'Tunakutana Jumamosi saa mbili asubuhi.')
            ->call('sendCampaign')
            ->assertHasErrors(['compose_wallet_id']);

        $this->assertSame(0, $wallet->refresh()->balance);
        $this->assertDatabaseCount('sms_campaigns', 0);
        $this->assertDatabaseCount('sms_logs', 0);
        $this->assertDatabaseCount('sms_transactions', 0);
    }

    public function test_campaign_deducts_only_the_selected_department_wallet(): void
    {
        Http::fake([
            '*' => Http::response(['successful' => true], 200),
        ]);

        config([
            'sms.beem.api_key' => 'api-key',
            'sms.beem.secret_key' => 'secret-key',
            'sms.beem.sender_id' => 'TAGCICC',
        ]);

        $men = $this->department('Wanaume');
        $women = $this->department('Wanawake');
        $user = $this->departmentLeader($men);
        $this->givePermissions($user, ['sms.view', 'sms.compose']);

        $menWallet = $this->departmentWallet($men, 10);
        $womenWallet = $this->departmentWallet($women, 800);
        $this->memberInDepartment($men, '0654000002');

        SmsSetting::current()->update([
            'sending_enabled' => true,
            'sender_id' => 'TAGCICC',
        ]);

        Livewire::actingAs($user)
            ->test(SmsIndex::class, ['section' => 'compose'])
            ->set('compose_wallet_id', (string) $menWallet->id)
            ->set('compose_title', 'Ratiba ya wanaume')
            ->set('compose_target_type', 'department_members')
            ->set('compose_department_id', (string) $men->id)
            ->set('compose_message', 'Tunakutana Jumapili saa moja jioni.')
            ->call('sendCampaign')
            ->assertHasNoErrors()
            ->assertDispatched('sms-campaign-sent');

        $this->assertSame(9, $menWallet->refresh()->balance);
        $this->assertSame(1, $menWallet->credits_used);
        $this->assertSame(800, $womenWallet->refresh()->balance);
        $this->assertDatabaseHas('sms_campaigns', [
            'sms_wallet_id' => $menWallet->id,
            'status' => SmsCampaign::STATUS_SENT,
            'recipients_count' => 1,
            'total_credits_used' => 1,
        ]);
        $this->assertDatabaseHas('sms_logs', [
            'sms_campaign_id' => SmsCampaign::query()->firstOrFail()->id,
            'status' => SmsLog::STATUS_SENT,
            'phone_number' => '255654000002',
        ]);
        $this->assertDatabaseHas('sms_transactions', [
            'sms_wallet_id' => $menWallet->id,
            'transaction_type' => SmsTransaction::TYPE_USAGE,
            'credits_out' => 1,
            'balance_before' => 10,
            'balance_after' => 9,
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function givePermissions(User $user, array $permissions): void
    {
        collect($permissions)->each(fn (string $permission): Permission => Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        $user->givePermissionTo($permissions);
    }

    private function department(string $name): Department
    {
        return Department::create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
        ]);
    }

    private function departmentWallet(Department $department, int $balance): SmsWallet
    {
        return SmsWallet::create([
            'owner_type' => SmsWallet::OWNER_DEPARTMENT,
            'department_id' => $department->id,
            'name' => $department->name.' SMS Wallet',
            'credits_purchased' => $balance,
            'credits_used' => 0,
            'balance' => $balance,
            'is_active' => true,
        ]);
    }

    private function departmentLeader(Department $department): User
    {
        $user = User::factory()->create();
        $member = Member::create([
            'user_id' => $user->id,
            'first_name' => 'Kiongozi',
            'last_name' => $department->slug,
            'gender' => 'male',
            'phone_number' => $user->phone_number,
            'email' => $user->email,
            'membership_status' => 'active',
        ]);
        $title = LeadershipTitle::firstOrCreate([
            'slug' => 'katibu-wa-idara',
        ], [
            'name' => 'Katibu wa Idara',
            'scope' => 'department',
        ]);

        MemberLeadershipAssignment::create([
            'member_id' => $member->id,
            'leadership_title_id' => $title->id,
            'department_id' => $department->id,
            'started_at' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        return $user;
    }

    private function memberInDepartment(Department $department, string $phone): Member
    {
        $member = Member::create([
            'first_name' => 'Mshirika',
            'last_name' => $department->slug,
            'gender' => 'male',
            'phone_number' => $phone,
            'membership_status' => 'active',
        ]);

        $member->departments()->attach($department->id, [
            'assignment_source' => 'manual',
            'started_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        return $member;
    }
}
