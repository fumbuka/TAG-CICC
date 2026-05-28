<?php

namespace App\Livewire\Leadership;

use App\Models\Department;
use App\Models\LeadershipTitle;
use App\Models\Member;
use App\Models\MemberLeadershipAssignment;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public ?int $editingTitleId = null;

    public string $title_name = '';

    public string $title_scope = 'church';

    public string $title_description = '';

    public ?int $editingAssignmentId = null;

    public ?int $member_id = null;

    public ?int $leadership_title_id = null;

    public ?int $department_id = null;

    public ?int $zone_id = null;

    public string $started_at = '';

    public string $ended_at = '';

    public bool $assignment_is_active = true;

    public string $assignment_notes = '';

    public function saveTitle(): void
    {
        $validated = $this->validate([
            'title_name' => ['required', 'string', 'max:255', Rule::unique('leadership_titles', 'name')->ignore($this->editingTitleId)],
            'title_scope' => ['required', Rule::in(['church', 'department', 'zone'])],
            'title_description' => ['nullable', 'string', 'max:1000'],
        ]);

        $attributes = [
            'name' => $validated['title_name'],
            'slug' => Str::slug($validated['title_name']),
            'scope' => $validated['title_scope'],
            'description' => $validated['title_description'] ?: null,
            'is_active' => true,
        ];

        $wasEditing = $this->editingTitleId !== null;

        $wasEditing
            ? LeadershipTitle::findOrFail($this->editingTitleId)->update($attributes)
            : LeadershipTitle::create($attributes);

        $this->resetTitleForm();

        $this->dispatch($wasEditing ? 'title-updated' : 'title-created');
    }

    public function editTitle(int $titleId): void
    {
        $title = LeadershipTitle::findOrFail($titleId);

        $this->editingTitleId = $title->id;
        $this->title_name = $title->name;
        $this->title_scope = $title->scope;
        $this->title_description = $title->description ?? '';
    }

    public function cancelTitleEdit(): void
    {
        $this->resetTitleForm();
    }

    public function deleteTitle(int $titleId): void
    {
        $title = LeadershipTitle::withCount('assignments')->findOrFail($titleId);

        if ($title->assignments_count > 0) {
            $this->addError('title_delete', __('messages.leadership_title_delete_blocked'));

            return;
        }

        $title->delete();

        $this->dispatch('title-deleted');
    }

    public function toggleTitleActive(int $titleId): void
    {
        $title = LeadershipTitle::findOrFail($titleId);

        $title->update([
            'is_active' => ! $title->is_active,
        ]);
    }

    public function saveAssignment(): void
    {
        $validated = $this->validate([
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')],
            'leadership_title_id' => ['required', 'integer', Rule::exists('leadership_titles', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'zone_id' => ['nullable', 'integer', Rule::exists('zones', 'id')],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'assignment_is_active' => ['boolean'],
            'assignment_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $title = LeadershipTitle::findOrFail($validated['leadership_title_id']);

        if ($title->scope === 'department' && ! $validated['department_id']) {
            $this->addError('department_id', __('messages.department_required_for_title'));

            return;
        }

        if ($title->scope === 'zone' && ! $validated['zone_id']) {
            $this->addError('zone_id', __('messages.zone_required_for_title'));

            return;
        }

        $attributes = [
            'member_id' => $validated['member_id'],
            'leadership_title_id' => $validated['leadership_title_id'],
            'department_id' => $title->scope === 'department' ? $validated['department_id'] : null,
            'zone_id' => $title->scope === 'zone' ? $validated['zone_id'] : null,
            'assigned_by_user_id' => Auth::id(),
            'started_at' => $validated['started_at'] ?: now()->toDateString(),
            'ended_at' => $validated['ended_at'] ?: null,
            'is_active' => $validated['assignment_is_active'],
            'notes' => $validated['assignment_notes'] ?: null,
        ];

        $wasEditing = $this->editingAssignmentId !== null;

        $wasEditing
            ? MemberLeadershipAssignment::findOrFail($this->editingAssignmentId)->update($attributes)
            : MemberLeadershipAssignment::create($attributes);

        $this->resetAssignmentForm();

        $this->dispatch($wasEditing ? 'assignment-updated' : 'assignment-created');
    }

    public function editAssignment(int $assignmentId): void
    {
        $assignment = MemberLeadershipAssignment::findOrFail($assignmentId);

        $this->editingAssignmentId = $assignment->id;
        $this->member_id = $assignment->member_id;
        $this->leadership_title_id = $assignment->leadership_title_id;
        $this->department_id = $assignment->department_id;
        $this->zone_id = $assignment->zone_id;
        $this->started_at = $assignment->started_at?->toDateString() ?? '';
        $this->ended_at = $assignment->ended_at?->toDateString() ?? '';
        $this->assignment_is_active = $assignment->is_active;
        $this->assignment_notes = $assignment->notes ?? '';
    }

    public function cancelAssignmentEdit(): void
    {
        $this->resetAssignmentForm();
    }

    public function deleteAssignment(int $assignmentId): void
    {
        MemberLeadershipAssignment::findOrFail($assignmentId)->delete();

        $this->dispatch('assignment-deleted');
    }

    public function render(): View
    {
        return view('livewire.leadership.index', [
            'titles' => LeadershipTitle::query()
                ->withCount('assignments')
                ->orderByDesc('is_active')
                ->orderBy('scope')
                ->orderBy('name')
                ->get(),
            'assignments' => MemberLeadershipAssignment::query()
                ->with(['member', 'leadershipTitle', 'department', 'zone'])
                ->latest()
                ->get(),
            'members' => Member::query()->orderBy('first_name')->orderBy('last_name')->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'zones' => Zone::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    private function resetTitleForm(): void
    {
        $this->reset(['editingTitleId', 'title_name', 'title_description']);
        $this->title_scope = 'church';
        $this->resetErrorBag();
    }

    private function resetAssignmentForm(): void
    {
        $this->reset([
            'editingAssignmentId',
            'member_id',
            'leadership_title_id',
            'department_id',
            'zone_id',
            'started_at',
            'ended_at',
            'assignment_notes',
        ]);
        $this->assignment_is_active = true;
        $this->resetErrorBag();
    }
}
