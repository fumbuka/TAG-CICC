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

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now()->startOfDay();

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
            'weeklyDuty' => WeeklyDuty::query()
                ->with(['elder', 'deacon'])
                ->where('is_active', true)
                ->whereDate('week_start', '<=', $today->toDateString())
                ->whereDate('week_end', '>=', $today->toDateString())
                ->latest('week_start')
                ->first(),
        ]);
    }
}
