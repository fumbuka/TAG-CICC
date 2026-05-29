<?php

namespace App\Livewire\Visitors;

use App\Models\Department;
use App\Models\Member;
use App\Models\User;
use App\Models\Visitor;
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

    public ?int $editingVisitorId = null;

    public string $search = '';

    public string $full_name = '';

    public string $phone_number = '';

    public string $residential_area = '';

    public string $visited_at = '';

    public string $invited_by = '';

    public string $follow_up_status = 'new';

    public ?int $assigned_to_user_id = null;

    public string $notes = '';

    public ?int $conversionVisitorId = null;

    public string $convert_first_name = '';

    public string $convert_middle_name = '';

    public string $convert_last_name = '';

    public string $convert_gender = '';

    public string $convert_date_of_birth = '';

    public string $convert_email = '';

    public string $convert_phone_number = '';

    public string $convert_residential_area = '';

    public ?int $convert_zone_id = null;

    /** @var array<int> */
    public array $convert_department_ids = [];

    public function mount(): void
    {
        $this->visited_at = now()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'residential_area' => ['nullable', 'string', 'max:255'],
            'visited_at' => ['required', 'date', 'before_or_equal:today'],
            'invited_by' => ['nullable', 'string', 'max:255'],
            'follow_up_status' => ['required', Rule::in($this->statusKeys())],
            'assigned_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $attributes = [
            'full_name' => $validated['full_name'],
            'phone_number' => $validated['phone_number'] ?: null,
            'residential_area' => $validated['residential_area'] ?: null,
            'visited_at' => $validated['visited_at'],
            'invited_by' => $validated['invited_by'] ?: null,
            'follow_up_status' => $validated['follow_up_status'],
            'assigned_to_user_id' => $validated['assigned_to_user_id'],
            'notes' => $validated['notes'] ?: null,
        ];

        $wasEditing = $this->editingVisitorId !== null;

        $wasEditing
            ? Visitor::findOrFail($this->editingVisitorId)->update($attributes)
            : Visitor::create($attributes);

        $this->resetForm();

        $this->dispatch($wasEditing ? 'visitor-updated' : 'visitor-created');
    }

    public function edit(int $visitorId): void
    {
        $visitor = Visitor::findOrFail($visitorId);

        $this->editingVisitorId = $visitor->id;
        $this->full_name = $visitor->full_name;
        $this->phone_number = $visitor->phone_number ?? '';
        $this->residential_area = $visitor->residential_area ?? '';
        $this->visited_at = $visitor->visited_at?->toDateString() ?? '';
        $this->invited_by = $visitor->invited_by ?? '';
        $this->follow_up_status = $visitor->follow_up_status;
        $this->assigned_to_user_id = $visitor->assigned_to_user_id;
        $this->notes = $visitor->notes ?? '';
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(int $visitorId): void
    {
        Visitor::findOrFail($visitorId)->delete();

        if ($this->editingVisitorId === $visitorId) {
            $this->resetForm();
        }

        if ($this->conversionVisitorId === $visitorId) {
            $this->resetConversionForm();
        }

        $this->dispatch('visitor-deleted');
    }

    public function prepareConversion(int $visitorId): void
    {
        $visitor = Visitor::findOrFail($visitorId);

        if ($visitor->converted_member_id) {
            $this->addError('conversion', __('messages.visitor_already_converted'));

            return;
        }

        [$firstName, $middleName, $lastName] = $this->splitFullName($visitor->full_name);

        $this->conversionVisitorId = $visitor->id;
        $this->convert_first_name = $firstName;
        $this->convert_middle_name = $middleName;
        $this->convert_last_name = $lastName;
        $this->convert_gender = '';
        $this->convert_date_of_birth = '';
        $this->convert_email = '';
        $this->convert_phone_number = $visitor->phone_number ?? '';
        $this->convert_residential_area = $visitor->residential_area ?? '';
        $this->convert_zone_id = null;
        $this->convert_department_ids = [];
    }

    public function cancelConversion(): void
    {
        $this->resetConversionForm();
    }

    public function convertToMember(MemberDepartmentAssignmentService $assignmentService): void
    {
        $validated = $this->validate([
            'convert_first_name' => ['required', 'string', 'max:255'],
            'convert_middle_name' => ['nullable', 'string', 'max:255'],
            'convert_last_name' => ['required', 'string', 'max:255'],
            'convert_gender' => ['required', Rule::in(['male', 'female'])],
            'convert_date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'convert_email' => ['nullable', 'email', 'max:255'],
            'convert_phone_number' => ['nullable', 'string', 'max:20'],
            'convert_residential_area' => ['nullable', 'string', 'max:255'],
            'convert_zone_id' => ['nullable', 'integer', Rule::exists('zones', 'id')],
            'convert_department_ids' => ['array'],
            'convert_department_ids.*' => ['integer', Rule::exists('departments', 'id')],
        ]);

        $visitor = Visitor::findOrFail($this->conversionVisitorId);

        if ($visitor->converted_member_id) {
            $this->addError('conversion', __('messages.visitor_already_converted'));

            return;
        }

        DB::transaction(function () use ($assignmentService, $validated, $visitor): void {
            $member = Member::create([
                'first_name' => $validated['convert_first_name'],
                'middle_name' => $validated['convert_middle_name'] ?: null,
                'last_name' => $validated['convert_last_name'],
                'gender' => $validated['convert_gender'],
                'date_of_birth' => $validated['convert_date_of_birth'],
                'phone_number' => $validated['convert_phone_number'] ?: null,
                'email' => $validated['convert_email'] ?: null,
                'residential_area' => $validated['convert_residential_area'] ?: null,
                'zone_id' => $validated['convert_zone_id'],
                'joined_at' => now()->toDateString(),
                'source' => 'visitor',
            ]);

            $assignmentService->assignDefaultDepartments($member, Auth::user());

            collect($validated['convert_department_ids'])
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

            $visitor->update([
                'converted_member_id' => $member->id,
                'follow_up_status' => 'converted',
            ]);
        });

        $this->resetConversionForm();

        $this->dispatch('visitor-converted');
    }

    public function render(): View
    {
        $visitorsQuery = Visitor::query();

        $visitors = $visitorsQuery
            ->with(['assignedTo', 'convertedMember'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('phone_number', 'like', "%{$this->search}%")
                        ->orWhere('residential_area', 'like', "%{$this->search}%")
                        ->orWhere('invited_by', 'like', "%{$this->search}%");
                });
            })
            ->latest('visited_at')
            ->latest()
            ->paginate(10);

        return view('livewire.visitors.index', [
            'visitors' => $visitors,
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'zones' => Zone::query()->where('is_active', true)->orderBy('name')->get(),
            'totalVisitors' => Visitor::query()->count(),
            'pendingFollowUps' => Visitor::query()
                ->whereIn('follow_up_status', ['new', 'follow_up', 'invited_to_membership'])
                ->count(),
            'convertedVisitors' => Visitor::query()->whereNotNull('converted_member_id')->count(),
            'statusOptions' => $this->statusKeys(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function statusKeys(): array
    {
        return ['new', 'follow_up', 'invited_to_membership', 'converted', 'not_interested'];
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingVisitorId',
            'full_name',
            'phone_number',
            'residential_area',
            'visited_at',
            'invited_by',
            'assigned_to_user_id',
            'notes',
        ]);
        $this->follow_up_status = 'new';
        $this->visited_at = now()->toDateString();
        $this->resetErrorBag();
    }

    private function resetConversionForm(): void
    {
        $this->reset([
            'conversionVisitorId',
            'convert_first_name',
            'convert_middle_name',
            'convert_last_name',
            'convert_gender',
            'convert_date_of_birth',
            'convert_email',
            'convert_phone_number',
            'convert_residential_area',
            'convert_zone_id',
            'convert_department_ids',
        ]);
        $this->resetErrorBag();
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function splitFullName(string $fullName): array
    {
        $parts = collect(preg_split('/\s+/', trim($fullName)) ?: [])
            ->filter()
            ->values();

        if ($parts->count() === 0) {
            return ['', '', ''];
        }

        if ($parts->count() === 1) {
            return [$parts->first(), '', ''];
        }

        return [
            $parts->first(),
            $parts->slice(1, -1)->join(' '),
            $parts->last(),
        ];
    }
}
