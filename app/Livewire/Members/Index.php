<?php

namespace App\Livewire\Members;

use App\Models\Department;
use App\Models\Member;
use App\Models\Zone;
use App\Services\MemberDepartmentAssignmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public ?int $editingMemberId = null;

    public string $search = '';

    public string $first_name = '';

    public string $middle_name = '';

    public string $last_name = '';

    public string $gender = '';

    public string $date_of_birth = '';

    public string $phone_number = '';

    public string $email = '';

    public string $residential_area = '';

    public ?int $zone_id = null;

    /** @var array<int> */
    public array $department_ids = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(MemberDepartmentAssignmentService $assignmentService): void
    {
        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'residential_area' => ['nullable', 'string', 'max:255'],
            'zone_id' => ['nullable', 'integer', Rule::exists('zones', 'id')],
            'department_ids' => ['array'],
            'department_ids.*' => ['integer', Rule::exists('departments', 'id')],
        ]);

        $wasEditing = $this->editingMemberId !== null;

        DB::transaction(function () use ($assignmentService, $validated): void {
            $attributes = [
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?: null,
                'last_name' => $validated['last_name'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
                'phone_number' => $validated['phone_number'] ?: null,
                'email' => $validated['email'] ?: null,
                'residential_area' => $validated['residential_area'] ?: null,
                'zone_id' => $validated['zone_id'],
                'joined_at' => now()->toDateString(),
                'source' => 'member',
            ];

            $member = $this->editingMemberId
                ? tap(Member::findOrFail($this->editingMemberId))->update($attributes)
                : Member::create($attributes);

            DB::table('member_departments')
                ->where('member_id', $member->id)
                ->whereIn('assignment_source', ['automatic', 'manual'])
                ->delete();

            $assignmentService->assignDefaultDepartments($member, Auth::user());

            collect($validated['department_ids'])
                ->unique()
                ->each(function (int $departmentId) use ($member): void {
                    $member->departments()->syncWithoutDetaching([
                        $departmentId => [
                            'assigned_by_user_id' => Auth::id(),
                            'assignment_source' => 'manual',
                            'started_at' => now()->toDateString(),
                            'is_active' => true,
                        ],
                    ]);
                });
        });

        $this->resetForm();

        $this->dispatch($wasEditing ? 'member-updated' : 'member-created');
    }

    public function edit(int $memberId): void
    {
        $member = Member::with('departments')->findOrFail($memberId);

        $this->editingMemberId = $member->id;
        $this->first_name = $member->first_name;
        $this->middle_name = $member->middle_name ?? '';
        $this->last_name = $member->last_name;
        $this->gender = $member->gender;
        $this->date_of_birth = $member->date_of_birth?->toDateString() ?? '';
        $this->phone_number = $member->phone_number ?? '';
        $this->email = $member->email ?? '';
        $this->residential_area = $member->residential_area ?? '';
        $this->zone_id = $member->zone_id;
        $this->department_ids = $member->departments->pluck('id')->all();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(int $memberId): void
    {
        Member::findOrFail($memberId)->delete();

        if ($this->editingMemberId === $memberId) {
            $this->resetForm();
        }

        $this->dispatch('member-deleted');
    }

    public function render(): View
    {
        $members = Member::query()
            ->with(['departments', 'zone'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('middle_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('phone_number', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.members.index', [
            'members' => $members,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'zones' => Zone::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingMemberId',
            'first_name',
            'middle_name',
            'last_name',
            'gender',
            'date_of_birth',
            'phone_number',
            'email',
            'residential_area',
            'zone_id',
            'department_ids',
        ]);

        $this->resetErrorBag();
    }
}
