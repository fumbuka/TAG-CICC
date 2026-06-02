<?php

namespace App\Livewire\Calendar;

use App\Livewire\Concerns\ChecksSeededPermissions;
use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\Member;
use App\Models\WeeklyDuty;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    use ChecksSeededPermissions;

    public string $section = 'events';

    public ?int $editingEventId = null;

    public string $title = '';

    public string $event_date = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public ?int $department_id = null;

    public ?int $zone_id = null;

    public string $description = '';

    public bool $is_important = true;

    public bool $is_active = true;

    public ?int $editingDutyId = null;

    public string $week_start = '';

    public string $week_end = '';

    public ?int $elder_member_id = null;

    public ?int $deacon_member_id = null;

    public string $duty_notes = '';

    public bool $duty_is_active = true;

    /**
     * @var array<int, int>
     */
    public array $submissionDepartmentIds = [];

    public function mount(?string $section = null): void
    {
        $this->section = $section ?: 'events';

        abort_unless(match ($this->section) {
            'create' => $this->canCreateCalendarEvents(),
            'weekly-duties' => $this->canManageWeeklyDuties(),
            default => $this->canViewCalendarEvents(),
        }, 403);

        $this->submissionDepartmentIds = $this->departmentIdsAllowedForSubmission();

        if (! $this->canManageCalendar() && count($this->submissionDepartmentIds) === 1) {
            $this->department_id = $this->submissionDepartmentIds[0];
        }
    }

    public function saveEvent(): void
    {
        abort_unless($this->canCreateCalendarEvents(), 403);

        $departmentRule = $this->canManageCalendar()
            ? ['nullable', 'integer', Rule::exists('departments', 'id')]
            : ['required', 'integer', Rule::in($this->submissionDepartmentIds)];

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'starts_at' => ['nullable', 'required_with:ends_at', 'date_format:H:i'],
            'ends_at' => ['nullable', 'required_with:starts_at', 'date_format:H:i', 'after:starts_at'],
            'department_id' => $departmentRule,
            'zone_id' => ['nullable', 'integer', Rule::exists('zones', 'id')],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_important' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        if (! $this->canManageCalendar()) {
            $validated['zone_id'] = null;
            $validated['is_active'] = true;
            $validated['is_important'] = true;
        }

        if ($conflict = $this->conflictingEvent($validated, $this->editingEventId)) {
            $this->addError('starts_at', __('messages.calendar_event_conflict', ['event' => $conflict->title]));

            return;
        }

        $attributes = [
            'title' => $validated['title'],
            'event_date' => $validated['event_date'],
            'starts_at' => $validated['starts_at'] ?: null,
            'ends_at' => $validated['ends_at'] ?: null,
            'department_id' => $validated['department_id'] ?: null,
            'zone_id' => $validated['zone_id'] ?: null,
            'description' => $validated['description'] ?: null,
            'is_important' => $validated['is_important'],
            'is_active' => $validated['is_active'],
        ];

        $wasEditing = $this->editingEventId !== null;

        if ($wasEditing) {
            $event = CalendarEvent::findOrFail($this->editingEventId);
            abort_unless($this->canManageEvent($event), 403);

            $event->update($attributes);
        } else {
            CalendarEvent::create($attributes + [
                'created_by_user_id' => Auth::id(),
            ]);
        }

        $this->resetEventForm();

        $this->dispatch($wasEditing ? 'event-updated' : 'event-created');
    }

    public function editEvent(int $eventId): void
    {
        $event = CalendarEvent::findOrFail($eventId);

        abort_unless($this->canManageEvent($event), 403);

        $this->editingEventId = $event->id;
        $this->section = 'create';
        $this->title = $event->title;
        $this->event_date = $event->event_date?->toDateString() ?? '';
        $this->starts_at = $event->starts_at ? substr((string) $event->starts_at, 0, 5) : '';
        $this->ends_at = $event->ends_at ? substr((string) $event->ends_at, 0, 5) : '';
        $this->department_id = $event->department_id;
        $this->zone_id = $event->zone_id;
        $this->description = $event->description ?? '';
        $this->is_important = $event->is_important;
        $this->is_active = $event->is_active;
    }

    public function cancelEventEdit(): void
    {
        $this->resetEventForm();
    }

    public function deleteEvent(int $eventId): void
    {
        $event = CalendarEvent::findOrFail($eventId);

        abort_unless($this->canManageEvent($event), 403);

        $event->delete();

        $this->dispatch('event-deleted');
    }

    public function toggleEventActive(int $eventId): void
    {
        $event = CalendarEvent::findOrFail($eventId);

        abort_unless($this->canManageEvent($event), 403);

        if (! $event->is_active && $conflict = $this->conflictingEvent([
            'event_date' => $event->event_date?->toDateString(),
            'starts_at' => $event->starts_at ? substr((string) $event->starts_at, 0, 5) : null,
            'ends_at' => $event->ends_at ? substr((string) $event->ends_at, 0, 5) : null,
        ], $event->id)) {
            $this->addError('starts_at', __('messages.calendar_event_conflict', ['event' => $conflict->title]));

            return;
        }

        $event->update([
            'is_active' => ! $event->is_active,
        ]);
    }

    public function saveDuty(): void
    {
        abort_unless($this->canManageWeeklyDuties(), 403);

        $validated = $this->validate([
            'week_start' => ['required', 'date'],
            'week_end' => ['required', 'date', 'after_or_equal:week_start'],
            'elder_member_id' => ['nullable', 'integer', Rule::exists('members', 'id')],
            'deacon_member_id' => ['nullable', 'integer', Rule::exists('members', 'id')],
            'duty_notes' => ['nullable', 'string', 'max:2000'],
            'duty_is_active' => ['boolean'],
        ]);

        $attributes = [
            'week_start' => $validated['week_start'],
            'week_end' => $validated['week_end'],
            'elder_member_id' => $validated['elder_member_id'] ?: null,
            'deacon_member_id' => $validated['deacon_member_id'] ?: null,
            'notes' => $validated['duty_notes'] ?: null,
            'is_active' => $validated['duty_is_active'],
        ];

        $wasEditing = $this->editingDutyId !== null;

        $wasEditing
            ? WeeklyDuty::findOrFail($this->editingDutyId)->update($attributes)
            : WeeklyDuty::create($attributes);

        $this->resetDutyForm();

        $this->dispatch($wasEditing ? 'duty-updated' : 'duty-created');
    }

    public function editDuty(int $dutyId): void
    {
        abort_unless($this->canManageWeeklyDuties(), 403);

        $duty = WeeklyDuty::findOrFail($dutyId);

        $this->editingDutyId = $duty->id;
        $this->section = 'weekly-duties';
        $this->week_start = $duty->week_start?->toDateString() ?? '';
        $this->week_end = $duty->week_end?->toDateString() ?? '';
        $this->elder_member_id = $duty->elder_member_id;
        $this->deacon_member_id = $duty->deacon_member_id;
        $this->duty_notes = $duty->notes ?? '';
        $this->duty_is_active = $duty->is_active;
    }

    public function cancelDutyEdit(): void
    {
        $this->resetDutyForm();
    }

    public function deleteDuty(int $dutyId): void
    {
        abort_unless($this->canManageWeeklyDuties(), 403);

        WeeklyDuty::findOrFail($dutyId)->delete();

        $this->dispatch('duty-deleted');
    }

    public function toggleDutyActive(int $dutyId): void
    {
        abort_unless($this->canManageWeeklyDuties(), 403);

        $duty = WeeklyDuty::findOrFail($dutyId);

        $duty->update([
            'is_active' => ! $duty->is_active,
        ]);
    }

    public function render(): View
    {
        $departments = Department::query()
            ->where('is_active', true)
            ->when(! $this->canManageCalendar(), fn ($query) => $query->whereIn('id', $this->submissionDepartmentIds))
            ->orderBy('name')
            ->get();

        return view('livewire.calendar.index', [
            'section' => $this->section,
            'events' => CalendarEvent::query()
                ->with(['department', 'zone'])
                ->orderByDesc('event_date')
                ->orderByDesc('starts_at')
                ->get(),
            'duties' => WeeklyDuty::query()
                ->with(['elder', 'deacon'])
                ->orderByDesc('week_start')
                ->get(),
            'members' => Member::query()->orderBy('first_name')->orderBy('last_name')->get(),
            'departments' => $departments,
            'zones' => Zone::query()->where('is_active', true)->orderBy('name')->get(),
            'canManageCalendar' => $this->canManageCalendar(),
            'canCreateCalendarEvents' => $this->canCreateCalendarEvents(),
            'canManageWeeklyDuties' => $this->canManageWeeklyDuties(),
            'submissionDepartmentIds' => $this->submissionDepartmentIds,
        ]);
    }

    private function resetEventForm(): void
    {
        $this->reset([
            'editingEventId',
            'title',
            'event_date',
            'starts_at',
            'ends_at',
            'department_id',
            'zone_id',
            'description',
        ]);
        $this->is_important = true;
        $this->is_active = true;
        if (! $this->canManageCalendar() && count($this->submissionDepartmentIds) === 1) {
            $this->department_id = $this->submissionDepartmentIds[0];
        }
        $this->resetErrorBag();
    }

    private function resetDutyForm(): void
    {
        $this->reset([
            'editingDutyId',
            'week_start',
            'week_end',
            'elder_member_id',
            'deacon_member_id',
            'duty_notes',
        ]);
        $this->duty_is_active = true;
        $this->resetErrorBag();
    }

    private function canUseCalendar(): bool
    {
        return $this->canCreateCalendarEvents();
    }

    private function canManageCalendar(): bool
    {
        return $this->permissionsAreUnseeded() || (Auth::user()?->can('calendar.manage') ?? false);
    }

    private function canViewCalendarEvents(): bool
    {
        return $this->canManageCalendar()
            || Auth::user()?->can('calendar.submit')
            || Auth::user()?->can('calendar.events');
    }

    private function canCreateCalendarEvents(): bool
    {
        return $this->canManageCalendar()
            || Auth::user()?->can('calendar.submit')
            || Auth::user()?->can('calendar.create');
    }

    private function canManageWeeklyDuties(): bool
    {
        return $this->canManageCalendar() || Auth::user()?->can('calendar.weekly-duties');
    }

    private function canManageEvent(CalendarEvent $event): bool
    {
        if ($this->canManageCalendar()) {
            return true;
        }

        return $event->department_id !== null
            && in_array($event->department_id, $this->submissionDepartmentIds, true);
    }

    /**
     * @return array<int, int>
     */
    private function departmentIdsAllowedForSubmission(): array
    {
        if ($this->canManageCalendar()) {
            return [];
        }

        $member = Auth::user()?->member;

        if (! $member) {
            return [];
        }

        return $member->leadershipAssignments()
            ->where('is_active', true)
            ->whereNotNull('department_id')
            ->whereHas('leadershipTitle', function ($query): void {
                $query->where('scope', 'department')
                    ->where('slug', 'katibu-wa-idara');
            })
            ->pluck('department_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function conflictingEvent(array $attributes, ?int $ignoreEventId = null): ?CalendarEvent
    {
        $eventDate = (string) ($attributes['event_date'] ?? '');

        if ($eventDate === '') {
            return null;
        }

        $newStart = $this->eventStartTime($attributes['starts_at'] ?? null);
        $newEnd = $this->eventEndTime($attributes['ends_at'] ?? null);

        return CalendarEvent::query()
            ->where('is_active', true)
            ->whereDate('event_date', $eventDate)
            ->when($ignoreEventId, fn ($query) => $query->whereKeyNot($ignoreEventId))
            ->get()
            ->first(fn (CalendarEvent $event): bool => $this->timeRangesOverlap(
                $newStart,
                $newEnd,
                $this->eventStartTime($event->starts_at),
                $this->eventEndTime($event->ends_at),
            ));
    }

    private function timeRangesOverlap(string $firstStart, string $firstEnd, string $secondStart, string $secondEnd): bool
    {
        return $firstStart < $secondEnd && $firstEnd > $secondStart;
    }

    private function eventStartTime(mixed $time): string
    {
        return $time ? substr((string) $time, 0, 5).':00' : '00:00:00';
    }

    private function eventEndTime(mixed $time): string
    {
        return $time ? substr((string) $time, 0, 5).':00' : '23:59:59';
    }
}
