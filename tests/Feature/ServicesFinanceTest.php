<?php

namespace Tests\Feature;

use App\Livewire\Finance\Index as FinanceIndex;
use App\Livewire\Services\Index as ServicesIndex;
use App\Models\Department;
use App\Models\FinancialTransaction;
use App\Models\IncomeCategory;
use App\Models\Member;
use App\Models\Pledge;
use App\Models\PledgePayment;
use App\Models\Service;
use App\Models\ServiceRoutine;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServicesFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_page_requires_authentication(): void
    {
        $this->get('/services')->assertRedirect('/login');
    }

    public function test_finance_page_requires_authentication(): void
    {
        $this->get('/finance')->assertRedirect('/login');
    }

    public function test_service_can_be_recorded_edited_and_deleted_when_empty(): void
    {
        $user = User::factory()->create();
        $serviceType = ServiceType::create([
            'name' => 'Ibada Kuu ya Jumapili',
            'slug' => 'ibada-kuu-ya-jumapili',
        ]);
        $department = Department::create([
            'name' => 'Uinjilishaji',
            'slug' => 'uinjilishaji',
        ]);
        $zone = Zone::create([
            'name' => 'Changombe',
            'slug' => 'changombe',
        ]);
        $routine = ServiceRoutine::create([
            'service_type_id' => $serviceType->id,
            'department_id' => $department->id,
            'zone_id' => $zone->id,
            'title' => 'Ibada ya maombi',
            'day_of_week' => now()->dayOfWeek,
            'starts_at' => '18:00',
            'ends_at' => '20:00',
        ]);

        Livewire::actingAs($user)
            ->test(ServicesIndex::class)
            ->set('service_routine_id', $routine->id)
            ->set('department_id', $department->id)
            ->set('zone_id', $zone->id)
            ->set('title', 'Ibada ya maombi')
            ->set('starts_at', '18:00')
            ->set('ends_at', '20:00')
            ->set('speaker', 'Mchungaji Kiongozi')
            ->set('topic', 'Imani')
            ->set('attendance_count', 120)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('service-created');

        $service = Service::query()->where('title', 'Ibada ya maombi')->firstOrFail();

        Livewire::actingAs($user)
            ->test(ServicesIndex::class)
            ->call('edit', $service->id)
            ->set('title', 'Ibada ya maombi na sifa')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('service-updated');

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'title' => 'Ibada ya maombi na sifa',
            'service_routine_id' => $routine->id,
        ]);

        Livewire::actingAs($user)
            ->test(ServicesIndex::class)
            ->call('delete', $service->id)
            ->assertDispatched('service-deleted');

        $this->assertDatabaseMissing('services', [
            'id' => $service->id,
        ]);
    }

    public function test_service_with_financial_transactions_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $serviceType = ServiceType::create([
            'name' => 'Ibada Kuu ya Jumapili',
            'slug' => 'ibada-kuu-ya-jumapili',
        ]);
        $incomeCategory = IncomeCategory::create([
            'name' => 'Sadaka',
            'slug' => 'sadaka',
        ]);
        $service = Service::create([
            'service_type_id' => $serviceType->id,
            'title' => 'Ibada Kuu',
            'service_date' => '2026-05-28',
        ]);

        FinancialTransaction::create([
            'income_category_id' => $incomeCategory->id,
            'service_id' => $service->id,
            'amount' => 50000,
            'transaction_date' => '2026-05-28',
        ]);

        Livewire::actingAs($user)
            ->test(ServicesIndex::class)
            ->call('delete', $service->id)
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
        ]);
    }

    public function test_financial_transaction_can_be_recorded_edited_and_deleted(): void
    {
        $user = User::factory()->create();
        $serviceType = ServiceType::create([
            'name' => 'Ibada ya Zone',
            'slug' => 'ibada-ya-zone',
        ]);
        $incomeCategory = IncomeCategory::create([
            'name' => 'Sadaka',
            'slug' => 'sadaka',
        ]);
        $service = Service::create([
            'service_type_id' => $serviceType->id,
            'title' => 'Ibada ya Changombe',
            'service_date' => '2026-05-28',
        ]);

        Livewire::actingAs($user)
            ->test(FinanceIndex::class)
            ->set('income_category_id', $incomeCategory->id)
            ->set('service_id', $service->id)
            ->set('amount', '15000')
            ->set('transaction_date', '2026-05-28')
            ->set('reference_number', 'OFF-001')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('transaction-created');

        $transaction = FinancialTransaction::query()->where('reference_number', 'OFF-001')->firstOrFail();

        $this->assertSame($user->id, $transaction->recorded_by_user_id);

        Livewire::actingAs($user)
            ->test(FinanceIndex::class)
            ->call('edit', $transaction->id)
            ->set('amount', '17500')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('transaction-updated');

        $this->assertDatabaseHas('financial_transactions', [
            'id' => $transaction->id,
            'amount' => '17500.00',
        ]);

        Livewire::actingAs($user)
            ->test(FinanceIndex::class)
            ->call('delete', $transaction->id)
            ->assertDispatched('transaction-deleted');

        $this->assertDatabaseMissing('financial_transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_income_categories_can_be_defined_edited_deactivated_and_deleted(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FinanceIndex::class)
            ->set('category_name', 'Mchango wa Maendeleo')
            ->set('category_description', 'Ujenzi na maendeleo ya kanisa')
            ->call('saveCategory')
            ->assertHasNoErrors()
            ->assertDispatched('category-created');

        $category = IncomeCategory::query()->where('name', 'Mchango wa Maendeleo')->firstOrFail();

        Livewire::actingAs($user)
            ->test(FinanceIndex::class)
            ->call('editCategory', $category->id)
            ->set('category_name', 'Mchango wa Makambi')
            ->set('category_is_active', false)
            ->call('saveCategory')
            ->assertHasNoErrors()
            ->assertDispatched('category-updated');

        $this->assertDatabaseHas('income_categories', [
            'id' => $category->id,
            'name' => 'Mchango wa Makambi',
            'slug' => 'mchango-wa-makambi',
            'is_active' => false,
        ]);

        Livewire::actingAs($user)
            ->test(FinanceIndex::class)
            ->call('toggleCategoryActive', $category->id);

        $this->assertDatabaseHas('income_categories', [
            'id' => $category->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(FinanceIndex::class)
            ->call('deleteCategory', $category->id)
            ->assertDispatched('category-deleted');

        $this->assertDatabaseMissing('income_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_income_category_with_transactions_or_pledges_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $category = IncomeCategory::create([
            'name' => 'Sadaka',
            'slug' => 'sadaka',
        ]);

        FinancialTransaction::create([
            'income_category_id' => $category->id,
            'amount' => 5000,
            'transaction_date' => '2026-05-29',
        ]);

        Livewire::actingAs($user)
            ->test(FinanceIndex::class)
            ->call('deleteCategory', $category->id)
            ->assertHasErrors('category_action');

        $this->assertDatabaseHas('income_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_pledges_can_be_paid_in_installments_until_completed(): void
    {
        $user = User::factory()->create();
        $member = Member::create([
            'first_name' => 'Fumbuka',
            'last_name' => 'Adam',
            'gender' => 'male',
            'email' => 'fumbuka.adam@tag-cicc.or.tz',
        ]);
        $category = IncomeCategory::create([
            'name' => 'Mchango wa Makambi',
            'slug' => 'mchango-wa-makambi',
        ]);

        Livewire::actingAs($user)
            ->test(FinanceIndex::class)
            ->set('pledge_member_id', $member->id)
            ->set('pledge_income_category_id', $category->id)
            ->set('pledged_amount', '100000')
            ->set('pledged_at', '2026-05-29')
            ->set('due_date', '2026-06-30')
            ->call('savePledge')
            ->assertHasNoErrors()
            ->assertDispatched('pledge-created');

        $pledge = Pledge::query()->where('member_id', $member->id)->firstOrFail();

        Livewire::actingAs($user)
            ->test(FinanceIndex::class)
            ->set('payment_pledge_id', $pledge->id)
            ->set('payment_amount', '40000')
            ->set('payment_date', '2026-06-01')
            ->set('payment_reference_number', 'PLG-001')
            ->call('recordPledgePayment')
            ->assertHasNoErrors()
            ->assertDispatched('pledge-payment-created');

        $pledge->refresh();
        $this->assertSame('active', $pledge->status);

        Livewire::actingAs($user)
            ->test(FinanceIndex::class)
            ->set('payment_pledge_id', $pledge->id)
            ->set('payment_amount', '60000')
            ->set('payment_date', '2026-06-15')
            ->set('payment_reference_number', 'PLG-002')
            ->call('recordPledgePayment')
            ->assertHasNoErrors()
            ->assertDispatched('pledge-payment-created');

        $pledge->refresh();

        $this->assertSame('completed', $pledge->status);
        $this->assertSame(100000.0, $pledge->paidAmount());
        $this->assertSame(0.0, $pledge->balanceAmount());

        $this->assertDatabaseCount('pledge_payments', 2);
        $this->assertDatabaseHas('financial_transactions', [
            'pledge_id' => $pledge->id,
            'amount' => '60000.00',
            'reference_number' => 'PLG-002',
        ]);

        Livewire::actingAs($user)
            ->test(FinanceIndex::class)
            ->set('payment_pledge_id', $pledge->id)
            ->set('payment_amount', '1')
            ->set('payment_date', '2026-06-20')
            ->call('recordPledgePayment')
            ->assertHasErrors('payment_amount');
    }

    public function test_pledge_payments_can_be_deleted_and_reopen_the_pledge_balance(): void
    {
        $user = User::factory()->create();
        $category = IncomeCategory::create([
            'name' => 'Mchango wa Ujenzi',
            'slug' => 'mchango-wa-ujenzi',
        ]);
        $pledge = Pledge::create([
            'income_category_id' => $category->id,
            'donor_name' => 'Mgeni wa Kanisa',
            'pledged_amount' => 50000,
            'pledged_at' => '2026-05-29',
            'status' => 'active',
        ]);
        $transaction = FinancialTransaction::create([
            'income_category_id' => $category->id,
            'pledge_id' => $pledge->id,
            'amount' => 50000,
            'transaction_date' => '2026-05-29',
            'reference_number' => 'PLG-DEL',
        ]);
        $payment = PledgePayment::create([
            'pledge_id' => $pledge->id,
            'financial_transaction_id' => $transaction->id,
            'amount' => 50000,
            'payment_date' => '2026-05-29',
            'reference_number' => 'PLG-DEL',
        ]);

        $pledge->update(['status' => 'completed']);

        Livewire::actingAs($user)
            ->test(FinanceIndex::class)
            ->call('deletePledge', $pledge->id)
            ->assertHasErrors('pledge_action');

        Livewire::actingAs($user)
            ->test(FinanceIndex::class)
            ->call('deletePledgePayment', $payment->id)
            ->assertDispatched('pledge-payment-deleted');

        $pledge->refresh();

        $this->assertSame('active', $pledge->status);
        $this->assertSame(0.0, $pledge->paidAmount());
        $this->assertSame(50000.0, $pledge->balanceAmount());
        $this->assertDatabaseMissing('pledge_payments', [
            'id' => $payment->id,
        ]);
        $this->assertDatabaseMissing('financial_transactions', [
            'id' => $transaction->id,
        ]);
    }
}
