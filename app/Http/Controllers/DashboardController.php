<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\Expense;
use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Models\Service;
use App\Models\User;
use App\Models\WeeklyDuty;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now()->startOfDay();
        $weeklyDuty = $this->weeklyDutyForDashboard($today);

        return view('dashboard', [
            'memberCount' => Member::query()->count(),
            'departmentCount' => Department::query()->where('is_active', true)->count(),
            'zoneCount' => Zone::query()->where('is_active', true)->count(),
            'serviceCount' => Service::query()->count(),
            'cashTotal' => (float) FinancialTransaction::query()->sum('amount') - (float) Expense::query()->sum('amount'),
            'systemAccessCount' => User::query()->whereHas('member')->where('is_active', true)->count(),
            'today' => $today,
            'upcomingEvents' => CalendarEvent::query()
                ->with(['department', 'zone'])
                ->where('is_active', true)
                ->where('is_important', true)
                ->whereDate('event_date', '>=', $today->toDateString())
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
}
