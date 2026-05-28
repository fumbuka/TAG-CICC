<?php

namespace Tests\Feature;

use App\Livewire\Finance\Index as FinanceIndex;
use App\Livewire\Services\Index as ServicesIndex;
use App\Models\Department;
use App\Models\FinancialTransaction;
use App\Models\IncomeCategory;
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
}
