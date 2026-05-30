<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\FinancialTransaction;
use App\Models\IncomeCategory;
use App\Models\Member;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\WeeklyDuty;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_key_statistics_and_weekly_information(): void
    {
        $user = User::factory()->create();

        collect(['users.manage', 'leadership.manage', 'calendar.manage'])->each(fn (string $permission) => Permission::create([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        $user->givePermissionTo(['users.manage', 'leadership.manage', 'calendar.manage']);

        Member::create([
            'user_id' => $user->id,
            'first_name' => 'TAG-CICC',
            'last_name' => 'Admin',
            'gender' => 'male',
            'date_of_birth' => '1980-01-01',
        ]);

        $elder = Member::create([
            'first_name' => 'Mzee',
            'last_name' => 'Baraka',
            'gender' => 'male',
            'date_of_birth' => '1970-01-01',
        ]);

        $deacon = Member::create([
            'first_name' => 'Shemasi',
            'last_name' => 'Neema',
            'gender' => 'female',
            'date_of_birth' => '1985-01-01',
        ]);

        Department::create([
            'name' => 'Maendeleo',
            'slug' => 'maendeleo',
        ]);

        Zone::create([
            'name' => 'Changombe',
            'slug' => 'changombe',
        ]);

        $serviceType = ServiceType::create([
            'name' => 'Ibada Kuu ya Jumapili',
            'slug' => 'ibada-kuu-ya-jumapili',
        ]);

        $service = Service::create([
            'service_type_id' => $serviceType->id,
            'title' => 'Ibada Kuu',
            'service_date' => now()->toDateString(),
        ]);

        $incomeCategory = IncomeCategory::create([
            'name' => 'Sadaka',
            'slug' => 'sadaka',
        ]);

        FinancialTransaction::create([
            'income_category_id' => $incomeCategory->id,
            'service_id' => $service->id,
            'amount' => 15000,
            'transaction_date' => now()->toDateString(),
        ]);

        CalendarEvent::create([
            'title' => 'Sherehe ya Kina Mama',
            'event_date' => now()->addDays(3)->toDateString(),
            'is_important' => true,
        ]);

        WeeklyDuty::create([
            'week_start' => now()->startOfWeek()->toDateString(),
            'week_end' => now()->endOfWeek()->toDateString(),
            'elder_member_id' => $elder->id,
            'deacon_member_id' => $deacon->id,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('TZS 15,000.00')
            ->assertSee('Sherehe ya Kina Mama')
            ->assertSee('Mzee Baraka')
            ->assertSee('Shemasi Neema')
            ->assertSee('3')
            ->assertSee('1');
    }

    public function test_dashboard_shows_weekly_duty_to_user_without_calendar_manage_permission(): void
    {
        $user = User::factory()->create();

        $elder = Member::create([
            'first_name' => 'Mzee',
            'last_name' => 'Baraka',
            'gender' => 'male',
            'date_of_birth' => '1970-01-01',
        ]);

        $deacon = Member::create([
            'first_name' => 'Shemasi',
            'last_name' => 'Neema',
            'gender' => 'female',
            'date_of_birth' => '1985-01-01',
        ]);

        WeeklyDuty::create([
            'week_start' => now()->startOfWeek()->toDateString(),
            'week_end' => now()->endOfWeek()->toDateString(),
            'elder_member_id' => $elder->id,
            'deacon_member_id' => $deacon->id,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Mzee Baraka')
            ->assertSee('Shemasi Neema');
    }

    public function test_dashboard_shows_next_weekly_duty_when_current_duty_is_not_assigned(): void
    {
        $user = User::factory()->create();

        $elder = Member::create([
            'first_name' => 'Mzee',
            'last_name' => 'Eliakim',
            'gender' => 'male',
            'date_of_birth' => '1965-01-01',
        ]);

        $deacon = Member::create([
            'first_name' => 'Shemasi',
            'last_name' => 'Upendo',
            'gender' => 'female',
            'date_of_birth' => '1988-01-01',
        ]);

        WeeklyDuty::create([
            'week_start' => now()->addWeek()->startOfWeek()->toDateString(),
            'week_end' => now()->addWeek()->endOfWeek()->toDateString(),
            'elder_member_id' => $elder->id,
            'deacon_member_id' => $deacon->id,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Mzee Eliakim')
            ->assertSee('Shemasi Upendo')
            ->assertSee(__('messages.next_weekly_duty'));
    }
}
