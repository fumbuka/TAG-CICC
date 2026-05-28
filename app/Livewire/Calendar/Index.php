<?php

namespace App\Livewire\Calendar;

use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\Member;
use App\Models\WeeklyDuty;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
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

    public function saveEvent(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'starts_at' => ['nullable', 'required_with:ends_at', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after_or_equal:starts_at'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'zone_id' => ['nullable', 'integer', Rule::exists('zones', 'id')],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_important' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

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

        $wasEditing
            ? CalendarEvent::findOrFail($this->editingEventId)->update($attributes)
            : CalendarEvent::create($attributes);

        $this->resetEventForm();

        $this->dispatch($wasEditing ? 'event-updated' : 'event-created');
    }

    public function editEvent(int $eventId): void
    {
        $event = CalendarEvent::findOrFail($eventId);

        $this->editingEventId = $event->id;
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
        CalendarEvent::findOrFail($eventId)->delete();

        $this->dispatch('event-deleted');
    }

    public function toggleEventActive(int $eventId): void
    {
        $event = CalendarEvent::findOrFail($eventId);

        $event->update([
            'is_active' => ! $event->is_active,
        ]);
    }

    public function saveDuty(): void
    {
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
        $duty = WeeklyDuty::findOrFail($dutyId);

        $this->editingDutyId = $duty->id;
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
        WeeklyDuty::findOrFail($dutyId)->delete();

        $this->dispatch('duty-deleted');
    }

    public function toggleDutyActive(int $dutyId): void
    {
        $duty = WeeklyDuty::findOrFail($dutyId);

        $duty->update([
            'is_active' => ! $duty->is_active,
        ]);
    }

    public function render(): View
    {
        return view('livewire.calendar.index', [
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
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'zones' => Zone::query()->where('is_active', true)->orderBy('name')->get(),
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
}
