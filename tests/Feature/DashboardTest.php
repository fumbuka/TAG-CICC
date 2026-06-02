<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialTransaction;
use App\Models\IncomeCategory;
use App\Models\LeadershipTitle;
use App\Models\Member;
use App\Models\MemberLeadershipAssignment;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\SiteVisit;
use App\Models\User;
use App\Models\WeeklyDuty;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_key_statistics_and_weekly_information(): void
    {
        $user = User::factory()->create();

        collect(['users.manage', 'leadership.manage', 'calendar.manage', 'finance.view'])->each(fn (string $permission) => Permission::create([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        $user->givePermissionTo(['users.manage', 'leadership.manage', 'calendar.manage', 'finance.view']);

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
            ->assertViewHas('memberCount', 3)
            ->assertViewHas('departmentCount', 1)
            ->assertViewHas('zoneCount', 1)
            ->assertViewHas('serviceCount', 1)
            ->assertViewHas('cashTotal', 15000.0)
            ->assertViewHas('siteVisitorStats', ['today' => 0, 'month' => 0, 'year' => 0])
            ->assertSee('TZS 15,000.00')
            ->assertSee('Sherehe ya Kina Mama')
            ->assertSee('Mzee Baraka')
            ->assertSee('Shemasi Neema')
            ->assertSee('3')
            ->assertSee('1');
    }

    public function test_dashboard_shows_unique_public_website_visitors_by_day_month_and_year(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $user = User::factory()->create();
        Permission::create([
            'name' => 'users.manage',
            'guard_name' => 'web',
        ]);
        $user->givePermissionTo('users.manage');

        SiteVisit::create([
            'visitor_hash' => 'visitor-one',
            'path' => '/',
            'visited_at' => now()->subHour(),
        ]);
        SiteVisit::create([
            'visitor_hash' => 'visitor-one',
            'path' => '/about',
            'visited_at' => now()->subMinutes(30),
        ]);
        SiteVisit::create([
            'visitor_hash' => 'visitor-two',
            'path' => '/ministries',
            'visited_at' => now()->subMinutes(10),
        ]);
        SiteVisit::create([
            'visitor_hash' => 'visitor-three',
            'path' => '/public-calendar',
            'visited_at' => now()->subDays(3),
        ]);
        SiteVisit::create([
            'visitor_hash' => 'visitor-four',
            'path' => '/weekly-leadership',
            'visited_at' => now()->subMonth(),
        ]);
        SiteVisit::create([
            'visitor_hash' => 'visitor-five',
            'path' => '/',
            'visited_at' => now()->subYear(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('siteVisitorStats', ['today' => 2, 'month' => 3, 'year' => 4])
            ->assertSee(__('messages.site_visitors'))
            ->assertSee(__('messages.site_visitors_today'))
            ->assertSee(__('messages.site_visitors_month'))
            ->assertSee(__('messages.site_visitors_year'));
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

    public function test_department_leader_dashboard_is_scoped_to_their_departments(): void
    {
        $user = User::factory()->create();
        $leader = Member::create([
            'user_id' => $user->id,
            'first_name' => 'Kiongozi',
            'last_name' => 'Wanaume',
            'gender' => 'male',
            'date_of_birth' => '1980-01-01',
        ]);

        $men = Department::create([
            'name' => 'Wababa',
            'slug' => 'wababa',
        ]);
        $women = Department::create([
            'name' => 'Wamama',
            'slug' => 'wamama',
        ]);

        $title = LeadershipTitle::create([
            'name' => 'Mkurugenzi wa Idara',
            'slug' => 'mkurugenzi-wa-idara',
            'scope' => 'department',
        ]);

        MemberLeadershipAssignment::create([
            'member_id' => $leader->id,
            'leadership_title_id' => $title->id,
            'department_id' => $men->id,
            'is_active' => true,
        ]);

        $menMemberOne = Member::create([
            'first_name' => 'Adam',
            'last_name' => 'Mzee',
            'gender' => 'male',
            'date_of_birth' => '1980-01-01',
        ]);
        $menMemberTwo = Member::create([
            'first_name' => 'Baraka',
            'last_name' => 'Mzee',
            'gender' => 'male',
            'date_of_birth' => '1981-01-01',
        ]);
        $womenMember = Member::create([
            'first_name' => 'Neema',
            'last_name' => 'Mama',
            'gender' => 'female',
            'date_of_birth' => '1985-01-01',
        ]);

        $menMemberOne->departments()->attach($men->id, ['is_active' => true]);
        $menMemberTwo->departments()->attach($men->id, ['is_active' => true]);
        $womenMember->departments()->attach($women->id, ['is_active' => true]);

        $serviceType = ServiceType::create([
            'name' => 'Ibada ya Idara',
            'slug' => 'ibada-ya-idara',
        ]);

        Service::create([
            'service_type_id' => $serviceType->id,
            'title' => 'Ibada ya Kanisa',
            'service_date' => now()->toDateString(),
        ]);
        Service::create([
            'service_type_id' => $serviceType->id,
            'department_id' => $men->id,
            'title' => 'Ibada ya Wababa',
            'service_date' => now()->toDateString(),
        ]);
        Service::create([
            'service_type_id' => $serviceType->id,
            'department_id' => $women->id,
            'title' => 'Ibada ya Wamama',
            'service_date' => now()->toDateString(),
        ]);

        CalendarEvent::create([
            'title' => 'Tukio la Kanisa',
            'event_date' => now()->addDay()->toDateString(),
            'is_important' => true,
        ]);
        CalendarEvent::create([
            'department_id' => $men->id,
            'title' => 'Tukio la Wababa',
            'event_date' => now()->addDays(2)->toDateString(),
            'is_important' => true,
        ]);
        CalendarEvent::create([
            'department_id' => $women->id,
            'title' => 'Tukio la Wamama',
            'event_date' => now()->addDays(3)->toDateString(),
            'is_important' => true,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('dashboardScopeLabel', 'Idara: Wababa')
            ->assertViewHas('memberCount', 2)
            ->assertViewHas('departmentCount', 1)
            ->assertViewHas('serviceCount', 2)
            ->assertSee('Tukio la Kanisa')
            ->assertSee('Tukio la Wababa')
            ->assertDontSee('Tukio la Wamama');
    }

    public function test_department_treasurer_dashboard_finance_is_scoped_to_their_department(): void
    {
        $user = User::factory()->create();
        Permission::create([
            'name' => 'finance.view',
            'guard_name' => 'web',
        ]);
        $user->givePermissionTo('finance.view');

        $treasurer = Member::create([
            'user_id' => $user->id,
            'first_name' => 'Hazina',
            'last_name' => 'Wababa',
            'gender' => 'male',
            'date_of_birth' => '1980-01-01',
        ]);

        $men = Department::create([
            'name' => 'Wababa',
            'slug' => 'wababa',
        ]);
        $women = Department::create([
            'name' => 'Wamama',
            'slug' => 'wamama',
        ]);
        $title = LeadershipTitle::create([
            'name' => 'Mweka Hazina wa Idara',
            'slug' => 'mweka-hazina-wa-idara',
            'scope' => 'department',
        ]);

        MemberLeadershipAssignment::create([
            'member_id' => $treasurer->id,
            'leadership_title_id' => $title->id,
            'department_id' => $men->id,
            'is_active' => true,
        ]);

        $incomeCategory = IncomeCategory::create([
            'name' => 'Sadaka ya Idara',
            'slug' => 'sadaka-ya-idara',
        ]);
        $expenseCategory = ExpenseCategory::create([
            'name' => 'Matumizi ya Idara',
            'slug' => 'matumizi-ya-idara',
        ]);

        FinancialTransaction::create([
            'income_category_id' => $incomeCategory->id,
            'department_id' => $men->id,
            'amount' => 100,
            'transaction_date' => now()->toDateString(),
        ]);
        FinancialTransaction::create([
            'income_category_id' => $incomeCategory->id,
            'department_id' => $women->id,
            'amount' => 500,
            'transaction_date' => now()->toDateString(),
        ]);
        FinancialTransaction::create([
            'income_category_id' => $incomeCategory->id,
            'amount' => 1000,
            'transaction_date' => now()->toDateString(),
        ]);
        Expense::create([
            'expense_category_id' => $expenseCategory->id,
            'department_id' => $men->id,
            'amount' => 25,
            'expense_date' => now()->toDateString(),
        ]);
        Expense::create([
            'expense_category_id' => $expenseCategory->id,
            'department_id' => $women->id,
            'amount' => 10,
            'expense_date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('cashTotal', 75.0)
            ->assertSee('TZS 75.00')
            ->assertDontSee('TZS 1,000.00');
    }
}
