<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\Expense;
use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Models\Service;
use App\Models\SiteVisit;
use App\Models\User;
use App\Models\WeeklyDuty;
use App\Models\Zone;
use App\Support\UserDataScope;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now()->startOfDay();
        $scope = UserDataScope::for(request()->user());
        $weeklyDuty = $this->weeklyDutyForDashboard($today);
        $incomeQuery = $scope->applyFinanceScope(FinancialTransaction::query());
        $expenseQuery = $scope->applyFinanceScope(Expense::query());

        return view('dashboard', [
            'dashboardScopeLabel' => $scope->label(),
            'memberCount' => $scope->applyMemberScope(Member::query())->count(),
            'departmentCount' => $scope->applyDepartmentScope(Department::query()->where('is_active', true))->count(),
            'zoneCount' => $scope->applyZoneScope(Zone::query()->where('is_active', true))->count(),
            'serviceCount' => $scope->applyServiceScope(Service::query())->count(),
            'cashTotal' => $scope->canSeeFinance() ? (float) $incomeQuery->sum('amount') - (float) $expenseQuery->sum('amount') : null,
            'systemAccessCount' => User::query()->whereHas('member')->where('is_active', true)->count(),
            'siteVisitorStats' => $this->siteVisitorStats($today),
            'today' => $today,
            'upcomingEvents' => $scope->applyCalendarEventScope(
                CalendarEvent::query()
                    ->with(['department', 'zone'])
                    ->where('is_active', true)
                    ->where('is_important', true)
                    ->whereDate('event_date', '>=', $today->toDateString()),
            )
                ->orderBy('event_date')
                ->orderBy('starts_at')
                ->limit(3)
                ->get(),
            'weeklyDuty' => $weeklyDuty,
            'weeklyDutyIsCurrent' => $weeklyDuty !== null
                && $weeklyDuty->week_start?->lte($today)
                && $weeklyDuty->week_end?->gte($today),
        ]);
    }

    private function weeklyDutyForDashboard(Carbon $today): ?WeeklyDuty
    {
        $baseQuery = WeeklyDuty::query()
            ->with(['elder', 'deacon'])
            ->where('is_active', true);

        $currentDuty = (clone $baseQuery)
            ->whereDate('week_start', '<=', $today->toDateString())
            ->whereDate('week_end', '>=', $today->toDateString())
            ->latest('week_start')
            ->first();

        return $currentDuty ?: (clone $baseQuery)
            ->whereDate('week_start', '>', $today->toDateString())
            ->orderBy('week_start')
            ->first();
    }

    /**
     * Counts unique public website visitors without exposing raw IP addresses.
     *
     * @return array{today: int, month: int, year: int}
     */
    private function siteVisitorStats(Carbon $today): array
    {
        if (! Schema::hasTable('site_visits')) {
            return [
                'today' => 0,
                'month' => 0,
                'year' => 0,
            ];
        }

        return [
            'today' => $this->uniqueSiteVisitorsBetween($today->copy()->startOfDay(), $today->copy()->endOfDay()),
            'month' => $this->uniqueSiteVisitorsBetween($today->copy()->startOfMonth(), $today->copy()->endOfMonth()),
            'year' => $this->uniqueSiteVisitorsBetween($today->copy()->startOfYear(), $today->copy()->endOfYear()),
        ];
    }

    private function uniqueSiteVisitorsBetween(Carbon $start, Carbon $end): int
    {
        return SiteVisit::query()
            ->whereBetween('visited_at', [$start, $end])
            ->distinct('visitor_hash')
            ->count('visitor_hash');
    }
}
