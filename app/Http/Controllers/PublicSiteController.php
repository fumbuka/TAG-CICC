<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\Member;
use App\Models\ServiceRoutine;
use App\Models\WeeklyDuty;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PublicSiteController extends Controller
{
    public function __invoke(): View
    {
        $today = now()->startOfDay();

        return view('public.home', [
            'memberCount' => Schema::hasTable('members') ? Member::query()->count() : 0,
            'departmentCount' => Schema::hasTable('departments') ? Department::query()->where('is_active', true)->count() : 0,
            'serviceRoutines' => Schema::hasTable('service_routines') ? ServiceRoutine::query()
                ->with(['department', 'zone'])
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->limit(6)
                ->get() : collect(),
            'upcomingEvents' => $this->upcomingEvents($today, 4),
            'weeklyDuty' => $this->weeklyDuty($today),
        ]);
    }

    public function calendar(): View
    {
        return view('public.calendar', [
            'events' => Schema::hasTable('calendar_events') ? CalendarEvent::query()
                ->with(['department', 'zone'])
                ->where('is_active', true)
                ->whereDate('event_date', '>=', now()->toDateString())
                ->orderBy('event_date')
                ->orderBy('starts_at')
                ->paginate(12) : new LengthAwarePaginator([], 0, 12),
        ]);
    }

    public function weeklyLeadership(): View
    {
        $today = now()->startOfDay();

        return view('public.weekly-leadership', [
            'currentDuty' => $this->weeklyDuty($today),
            'duties' => Schema::hasTable('weekly_duties') ? WeeklyDuty::query()
                ->with(['elder', 'deacon'])
                ->where('is_active', true)
                ->whereDate('week_end', '>=', $today->toDateString())
                ->orderBy('week_start')
                ->limit(12)
                ->get() : collect(),
        ]);
    }

    private function upcomingEvents(Carbon $today, int $limit): Collection
    {
        if (! Schema::hasTable('calendar_events')) {
            return collect();
        }

        return CalendarEvent::query()
            ->with(['department', 'zone'])
            ->where('is_active', true)
            ->where('is_important', true)
            ->whereDate('event_date', '>=', $today->toDateString())
            ->orderBy('event_date')
            ->orderBy('starts_at')
            ->limit($limit)
            ->get();
    }

    private function weeklyDuty(Carbon $today): ?WeeklyDuty
    {
        if (! Schema::hasTable('weekly_duties')) {
            return null;
        }

        $query = WeeklyDuty::query()
            ->with(['elder', 'deacon'])
            ->where('is_active', true);

        $current = (clone $query)
            ->whereDate('week_start', '<=', $today->toDateString())
            ->whereDate('week_end', '>=', $today->toDateString())
            ->latest('week_start')
            ->first();

        return $current ?: (clone $query)
            ->whereDate('week_start', '>', $today->toDateString())
            ->orderBy('week_start')
            ->first();
    }
}
