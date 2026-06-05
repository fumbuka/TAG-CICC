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
use App\Models\SmsTemplate;
use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Models\User;
use App\Models\Zone;
use App\Services\OperationalModuleReportPdfService;
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

    public function test_department_user_can_send_to_custom_members_inside_and_outside_their_department(): void
    {
        Http::fake([
            '*' => Http::response(['messages' => [
                ['message_id' => 'MSG-001'],
                ['message_id' => 'MSG-002'],
            ]], 200),
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
        $menMember = $this->memberInDepartment($men, '0654000003');
        $womenMember = $this->memberInDepartment($women, '0654000004');

        SmsSetting::current()->update([
            'sending_enabled' => true,
            'sender_id' => 'TAGCICC',
        ]);

        Livewire::actingAs($user)
            ->test(SmsIndex::class, ['section' => 'compose'])
            ->set('compose_wallet_id', (string) $menWallet->id)
            ->set('compose_title', 'Tangazo maalum')
            ->set('compose_target_type', 'custom_members')
            ->set('compose_member_ids', [$menMember->id, $womenMember->id])
            ->set('compose_message', 'Ujumbe kwa mshirika aliyechaguliwa.')
            ->call('sendCampaign')
            ->assertHasNoErrors()
            ->assertDispatched('sms-campaign-sent');

        $this->assertSame(8, $menWallet->refresh()->balance);
        $this->assertDatabaseHas('sms_logs', [
            'member_id' => $menMember->id,
            'phone_number' => '255654000003',
            'status' => SmsLog::STATUS_SENT,
            'beem_message_id' => 'MSG-001',
        ]);
        $this->assertDatabaseHas('sms_logs', [
            'member_id' => $womenMember->id,
            'phone_number' => '255654000004',
            'status' => SmsLog::STATUS_SENT,
            'beem_message_id' => 'MSG-002',
        ]);
    }

    public function test_department_user_can_send_sms_to_one_member(): void
    {
        Http::fake([
            '*' => Http::response(['messages' => [['message_id' => 'ONE-001']]], 200),
        ]);

        config([
            'sms.beem.api_key' => 'api-key',
            'sms.beem.secret_key' => 'secret-key',
            'sms.beem.sender_id' => 'TAGCICC',
        ]);

        $department = $this->department('Maendeleo');
        $user = $this->departmentLeader($department);
        $this->givePermissions($user, ['sms.view', 'sms.compose']);
        $wallet = $this->departmentWallet($department, 3);
        $member = $this->memberInDepartment($department, '0654000006');

        SmsSetting::current()->update([
            'sending_enabled' => true,
            'sender_id' => 'TAGCICC',
        ]);

        Livewire::actingAs($user)
            ->test(SmsIndex::class, ['section' => 'compose'])
            ->set('compose_wallet_id', (string) $wallet->id)
            ->set('compose_title', 'Ujumbe binafsi')
            ->set('compose_target_type', 'single_member')
            ->set('compose_member_id', (string) $member->id)
            ->set('compose_message', 'Mungu akubariki.')
            ->call('sendCampaign')
            ->assertHasNoErrors()
            ->assertDispatched('sms-campaign-sent');

        $this->assertSame(2, $wallet->refresh()->balance);
        $this->assertDatabaseHas('sms_campaigns', [
            'sms_wallet_id' => $wallet->id,
            'target_type' => 'single_member',
            'recipients_count' => 1,
            'total_credits_used' => 1,
            'status' => SmsCampaign::STATUS_SENT,
        ]);
        $this->assertDatabaseHas('sms_logs', [
            'member_id' => $member->id,
            'phone_number' => '255654000006',
            'status' => SmsLog::STATUS_SENT,
            'beem_message_id' => 'ONE-001',
        ]);
    }

    public function test_sms_buyer_can_track_their_purchase_request_status(): void
    {
        $user = User::factory()->create();
        $this->givePermissions($user, ['sms.view', 'sms.buy']);
        $wallet = SmsWallet::create([
            'owner_type' => SmsWallet::OWNER_USER,
            'user_id' => $user->id,
            'name' => 'Personal SMS Wallet',
            'credits_purchased' => 0,
            'credits_used' => 0,
            'balance' => 0,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(SmsIndex::class, ['section' => 'buy'])
            ->set('purchase_wallet_id', (string) $wallet->id)
            ->set('purchase_sms_quantity', '250')
            ->set('purchase_notes', 'Nahitaji kutuma matangazo.')
            ->call('requestPurchase')
            ->assertHasNoErrors()
            ->assertDispatched('sms-purchase-requested')
            ->assertSee(__('messages.sms_my_purchase_requests'))
            ->assertSee(__('messages.sms_purchase_status_pending'))
            ->assertSee('Personal SMS Wallet');

        $this->assertDatabaseHas('sms_purchases', [
            'sms_wallet_id' => $wallet->id,
            'requested_by_user_id' => $user->id,
            'sms_quantity' => 250,
            'status' => SmsPurchase::STATUS_PENDING,
        ]);
    }

    public function test_department_user_can_send_sms_to_manual_numbers_outside_database(): void
    {
        Http::fake([
            '*' => Http::response(['messages' => [
                ['message_id' => 'EXT-001'],
                ['message_id' => 'EXT-002'],
            ]], 200),
        ]);

        config([
            'sms.beem.api_key' => 'api-key',
            'sms.beem.secret_key' => 'secret-key',
            'sms.beem.sender_id' => 'TAGCICC',
        ]);

        $department = $this->department('Uinjilishaji');
        $user = $this->departmentLeader($department);
        $this->givePermissions($user, ['sms.view', 'sms.compose']);
        $wallet = $this->departmentWallet($department, 5);

        SmsSetting::current()->update([
            'sending_enabled' => true,
            'sender_id' => 'TAGCICC',
        ]);

        Livewire::actingAs($user)
            ->test(SmsIndex::class, ['section' => 'compose'])
            ->set('compose_wallet_id', (string) $wallet->id)
            ->set('compose_title', 'Wageni wa mkutano')
            ->set('compose_target_type', 'manual_recipients')
            ->set('compose_manual_recipients', "Mgeni Mmoja, 0654000007\n0755000008")
            ->set('compose_message', 'Karibu kwenye mkutano.')
            ->call('sendCampaign')
            ->assertHasNoErrors()
            ->assertDispatched('sms-campaign-sent');

        $this->assertSame(3, $wallet->refresh()->balance);
        $this->assertDatabaseHas('sms_campaigns', [
            'sms_wallet_id' => $wallet->id,
            'target_type' => 'manual_recipients',
            'recipients_count' => 2,
            'total_credits_used' => 2,
            'status' => SmsCampaign::STATUS_SENT,
        ]);
        $this->assertDatabaseHas('sms_logs', [
            'member_id' => null,
            'recipient_name' => 'Mgeni Mmoja',
            'phone_number' => '255654000007',
            'status' => SmsLog::STATUS_SENT,
            'beem_message_id' => 'EXT-001',
        ]);
        $this->assertDatabaseHas('sms_logs', [
            'member_id' => null,
            'recipient_name' => __('messages.sms_manual_recipient'),
            'phone_number' => '255755000008',
            'status' => SmsLog::STATUS_SENT,
            'beem_message_id' => 'EXT-002',
        ]);
    }

    public function test_failed_campaign_can_be_retried_and_deducts_only_retry_credits(): void
    {
        Http::fake([
            '*' => Http::response(['messages' => [['message_id' => 'RETRY-001']]], 200),
        ]);

        config([
            'sms.beem.api_key' => 'api-key',
            'sms.beem.secret_key' => 'secret-key',
            'sms.beem.sender_id' => 'TAGCICC',
        ]);

        $department = $this->department('Vijana');
        $user = $this->departmentLeader($department);
        $this->givePermissions($user, ['sms.view', 'sms.compose']);
        $wallet = $this->departmentWallet($department, 5);
        $member = $this->memberInDepartment($department, '0654000005');

        SmsSetting::current()->update([
            'sending_enabled' => true,
            'sender_id' => 'TAGCICC',
        ]);

        $campaign = SmsCampaign::create([
            'sms_wallet_id' => $wallet->id,
            'sent_by_user_id' => $user->id,
            'department_id' => $department->id,
            'title' => 'Campaign iliyofeli',
            'target_type' => 'department_members',
            'message' => 'Jaribu tena.',
            'recipients_count' => 1,
            'sms_parts' => 1,
            'total_credits_used' => 0,
            'status' => SmsCampaign::STATUS_FAILED,
        ]);

        $log = SmsLog::create([
            'sms_campaign_id' => $campaign->id,
            'member_id' => $member->id,
            'recipient_name' => $member->fullName(),
            'phone_number' => '255654000005',
            'message' => $campaign->message,
            'status' => SmsLog::STATUS_FAILED,
            'error_message' => 'Network failed',
        ]);

        Livewire::actingAs($user)
            ->test(SmsIndex::class, ['section' => 'campaigns'])
            ->call('retryCampaign', $campaign->id)
            ->assertHasNoErrors()
            ->assertDispatched('sms-campaign-retried');

        $this->assertSame(4, $wallet->refresh()->balance);
        $this->assertSame(1, $wallet->credits_used);
        $this->assertSame(SmsCampaign::STATUS_SENT, $campaign->refresh()->status);
        $this->assertSame(1, $campaign->total_credits_used);
        $this->assertSame(SmsLog::STATUS_SENT, $log->refresh()->status);
        $this->assertSame('RETRY-001', $log->beem_message_id);
        $this->assertNull($log->error_message);
        $this->assertDatabaseHas('sms_transactions', [
            'sms_wallet_id' => $wallet->id,
            'transaction_type' => SmsTransaction::TYPE_USAGE,
            'credits_out' => 1,
            'balance_before' => 5,
            'balance_after' => 4,
        ]);
    }

    public function test_sms_template_can_be_created_and_applied_to_compose_form(): void
    {
        $user = User::factory()->create();
        $this->givePermissions($user, ['sms.view', 'sms.compose', 'sms.templates']);

        Livewire::actingAs($user)
            ->test(SmsIndex::class, ['section' => 'templates'])
            ->set('template_title', 'Ibada ya Jumapili')
            ->set('template_message', 'Karibu {first_name} kwenye ibada ya Jumapili.')
            ->call('saveTemplate')
            ->assertHasNoErrors()
            ->assertDispatched('sms-template-saved');

        $template = SmsTemplate::query()->firstOrFail();

        Livewire::actingAs($user)
            ->test(SmsIndex::class, ['section' => 'compose'])
            ->set('compose_template_id', (string) $template->id)
            ->call('applyTemplate')
            ->assertSet('compose_message', 'Karibu {first_name} kwenye ibada ya Jumapili.');
    }

    public function test_personalized_sms_renders_member_placeholders(): void
    {
        Http::fake([
            '*' => Http::response(['messages' => [['message_id' => 'PERS-001']]], 200),
        ]);

        config([
            'sms.beem.api_key' => 'api-key',
            'sms.beem.secret_key' => 'secret-key',
            'sms.beem.sender_id' => 'TAGCICC',
        ]);

        $department = $this->department('Wanaume');
        $zone = Zone::create([
            'name' => 'Changombe',
            'slug' => 'changombe',
        ]);
        $user = $this->departmentLeader($department);
        $this->givePermissions($user, ['sms.view', 'sms.compose']);
        $wallet = $this->departmentWallet($department, 5);
        $member = $this->memberInDepartment($department, '0654000010');
        $member->update([
            'first_name' => 'Adam',
            'last_name' => 'Fumbuka',
            'zone_id' => $zone->id,
        ]);

        SmsSetting::current()->update([
            'sending_enabled' => true,
            'sender_id' => 'TAGCICC',
        ]);

        Livewire::actingAs($user)
            ->test(SmsIndex::class, ['section' => 'compose'])
            ->set('compose_wallet_id', (string) $wallet->id)
            ->set('compose_title', 'Salamu binafsi')
            ->set('compose_target_type', 'single_member')
            ->set('compose_member_id', (string) $member->id)
            ->set('compose_personalization_enabled', true)
            ->set('compose_message', 'Habari {first_name} wa {department} kutoka {zone}.')
            ->call('sendCampaign')
            ->assertHasNoErrors()
            ->assertDispatched('sms-campaign-sent');

        $this->assertDatabaseHas('sms_logs', [
            'member_id' => $member->id,
            'phone_number' => '255654000010',
            'message' => 'Habari Adam wa Wanaume kutoka Changombe.',
            'status' => SmsLog::STATUS_SENT,
        ]);
    }

    public function test_sms_campaign_can_be_scheduled_and_processed_later(): void
    {
        Http::fake([
            '*' => Http::response(['messages' => [['message_id' => 'SCH-001']]], 200),
        ]);

        config([
            'sms.beem.api_key' => 'api-key',
            'sms.beem.secret_key' => 'secret-key',
            'sms.beem.sender_id' => 'TAGCICC',
        ]);

        $department = $this->department('Vijana');
        $user = $this->departmentLeader($department);
        $this->givePermissions($user, ['sms.view', 'sms.compose', 'sms.scheduled']);
        $wallet = $this->departmentWallet($department, 5);
        $member = $this->memberInDepartment($department, '0654000011');

        SmsSetting::current()->update([
            'sending_enabled' => true,
            'sender_id' => 'TAGCICC',
        ]);

        Livewire::actingAs($user)
            ->test(SmsIndex::class, ['section' => 'compose'])
            ->set('compose_wallet_id', (string) $wallet->id)
            ->set('compose_title', 'Ratiba ya baadaye')
            ->set('compose_target_type', 'single_member')
            ->set('compose_member_id', (string) $member->id)
            ->set('compose_message', 'Usisahau mkutano.')
            ->set('compose_send_mode', 'scheduled')
            ->set('compose_scheduled_at', now()->addHour()->format('Y-m-d\TH:i'))
            ->call('sendCampaign')
            ->assertHasNoErrors()
            ->assertDispatched('sms-campaign-scheduled');

        $campaign = SmsCampaign::query()->firstOrFail();
        $this->assertSame(SmsCampaign::STATUS_SCHEDULED, $campaign->status);
        $this->assertSame(5, $wallet->refresh()->balance);
        $this->assertDatabaseHas('sms_logs', [
            'sms_campaign_id' => $campaign->id,
            'status' => SmsLog::STATUS_PENDING,
        ]);

        $campaign->update(['scheduled_at' => now()->subMinute()]);

        $this->artisan('sms:send-scheduled')
            ->assertExitCode(0);

        $this->assertSame(SmsCampaign::STATUS_SENT, $campaign->refresh()->status);
        $this->assertSame(4, $wallet->refresh()->balance);
        $this->assertDatabaseHas('sms_logs', [
            'sms_campaign_id' => $campaign->id,
            'status' => SmsLog::STATUS_SENT,
            'beem_message_id' => 'SCH-001',
        ]);
    }

    public function test_beem_delivery_callback_updates_sms_log_status(): void
    {
        $campaign = SmsCampaign::create([
            'sms_wallet_id' => SmsWallet::create([
                'owner_type' => SmsWallet::OWNER_CHURCH,
                'name' => 'Church SMS',
                'balance' => 0,
                'is_active' => true,
            ])->id,
            'title' => 'Callback campaign',
            'target_type' => 'manual_recipients',
            'message' => 'Karibu',
            'recipients_count' => 1,
            'sms_parts' => 1,
            'status' => SmsCampaign::STATUS_SENT,
        ]);
        $log = SmsLog::create([
            'sms_campaign_id' => $campaign->id,
            'recipient_name' => 'Mgeni',
            'phone_number' => '255654000012',
            'message' => 'Karibu',
            'status' => SmsLog::STATUS_SENT,
            'beem_message_id' => 'CALL-001',
        ]);

        $this->postJson('/sms/beem/callback', [
            'message_id' => 'CALL-001',
            'status' => 'delivered',
        ])->assertOk();

        $this->assertSame(SmsLog::STATUS_DELIVERED, $log->refresh()->status);
        $this->assertSame('delivered', $log->provider_status);
        $this->assertNotNull($log->delivered_at);
    }

    public function test_sms_report_can_be_downloaded_from_sms_module(): void
    {
        $this->travelTo('2026-06-05 10:00:00');

        $user = User::factory()->create();
        $this->givePermissions($user, ['sms.view', 'sms.reports']);
        $wallet = SmsWallet::create([
            'owner_type' => SmsWallet::OWNER_CHURCH,
            'name' => 'Church SMS',
            'credits_purchased' => 20,
            'credits_used' => 5,
            'balance' => 15,
            'is_active' => true,
        ]);
        SmsTransaction::create([
            'sms_wallet_id' => $wallet->id,
            'transaction_type' => SmsTransaction::TYPE_PURCHASE,
            'credits_in' => 20,
            'credits_out' => 0,
            'balance_before' => 0,
            'balance_after' => 20,
            'description' => 'Initial SMS',
            'performed_by_user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(SmsIndex::class, ['section' => 'reports'])
            ->call('downloadSmsReport')
            ->assertFileDownloaded(
                'tag-cicc-sms-operational-report-20260605-100000.pdf',
                contentType: OperationalModuleReportPdfService::CONTENT_TYPE,
            );
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
