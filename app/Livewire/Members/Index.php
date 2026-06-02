<?php

namespace App\Livewire\Members;

use App\Livewire\Concerns\TracksImportResults;
use App\Livewire\Concerns\ChecksSeededPermissions;
use App\Models\Department;
use App\Models\ImportUpload;
use App\Models\Member;
use App\Models\MemberRelationship;
use App\Models\Zone;
use App\Services\ImportReportExportService;
use App\Services\MemberDepartmentAssignmentService;
use App\Services\SpreadsheetImportService;
use App\Support\UserDataScope;
use DateTimeInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

#[Layout('layouts.app')]
class Index extends Component
{
    use TracksImportResults;
    use ChecksSeededPermissions;
    use WithFileUploads;
    use WithPagination;

    public ?int $editingMemberId = null;

    public string $section = 'list';

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

    public $memberImport = null;

    public ?int $editingRelationshipId = null;

    public ?int $relationship_member_id = null;

    public ?int $related_member_id = null;

    public string $relationship_type = 'spouse';

    public string $relationship_notes = '';

    public function mount(?string $section = null): void
    {
        $this->section = $section ?: 'list';

        if ($this->section === 'create') {
            abort_unless($this->canSaveMembers(), 403);
        }

        if ($this->section === 'import') {
            abort_unless($this->canImportMembers(), 403);
        }

        if ($this->section === 'relationships') {
            abort_unless($this->canManageRelationships(), 403);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(MemberDepartmentAssignmentService $assignmentService): void
    {
        abort_unless($this->canSaveMembers(), 403);

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'phone_number' => ['nullable', 'string', 'max:20', Rule::unique('members', 'phone_number')->ignore($this->editingMemberId)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('members', 'email')->ignore($this->editingMemberId)],
            'residential_area' => ['nullable', 'string', 'max:255'],
            'zone_id' => ['nullable', 'integer', Rule::exists('zones', 'id')],
            'department_ids' => ['array'],
            'department_ids.*' => ['integer', Rule::exists('departments', 'id')],
        ]);

        if ($duplicate = $this->findDuplicateMember($validated, $this->editingMemberId)) {
            $this->addError('first_name', __('messages.duplicate_member_found', [
                'member' => $duplicate->fullName(),
            ]));

            return;
        }

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
        abort_unless($this->canUpdateMembers(), 403);

        $member = Member::with('departments')->findOrFail($memberId);

        $this->editingMemberId = $member->id;
        $this->section = 'create';
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
        abort_unless($this->canUpdateMembers(), 403);

        Member::findOrFail($memberId)->delete();

        if ($this->editingMemberId === $memberId) {
            $this->resetForm();
        }

        $this->dispatch('member-deleted');
    }

    public function import(
        SpreadsheetImportService $importer,
        MemberDepartmentAssignmentService $assignmentService,
        ImportReportExportService $reportExporter,
    ): BinaryFileResponse {
        abort_unless($this->canImportMembers(), 403);

        $this->validate([
            'memberImport' => ['required', 'file', 'mimes:csv,txt,xlsx,ods', 'max:5120'],
        ]);

        $originalFilename = $this->memberImport?->getClientOriginalName();
        $rows = $importer->rowsWithMetadata($this->memberImport);
        $this->startImportReport('members', count($rows), $originalFilename);

        foreach ($rows as $entry) {
            $rowNumber = $entry['row_number'];
            $row = $entry['data'];
            $record = $this->memberImportRecordLabel($row);

            try {
                $attributes = [
                    'first_name' => trim((string) ($row['first_name'] ?? $row['jina_la_kwanza'] ?? '')),
                    'middle_name' => trim((string) ($row['middle_name'] ?? $row['jina_la_kati'] ?? '')) ?: null,
                    'last_name' => trim((string) ($row['last_name'] ?? $row['jina_la_mwisho'] ?? '')),
                    'gender' => $this->normalizeGender($row['gender'] ?? $row['jinsia'] ?? ''),
                    'date_of_birth' => $this->normalizeDate($row['date_of_birth'] ?? $row['tarehe_ya_kuzaliwa'] ?? null),
                    'phone_number' => trim((string) ($row['phone_number'] ?? $row['phone'] ?? $row['simu'] ?? '')) ?: null,
                    'email' => trim((string) ($row['email'] ?? $row['barua_pepe'] ?? '')) ?: null,
                    'residential_area' => trim((string) ($row['residential_area'] ?? $row['eneo'] ?? $row['anapoishi'] ?? '')) ?: null,
                    'joined_at' => now()->toDateString(),
                    'source' => 'member',
                ];

                Validator::make($attributes, [
                    'first_name' => ['required', 'string', 'max:255'],
                    'last_name' => ['required', 'string', 'max:255'],
                    'gender' => ['required', Rule::in(['male', 'female'])],
                    'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
                    'phone_number' => ['nullable', 'string', 'max:20'],
                    'email' => ['nullable', 'email', 'max:255'],
                ])->validate();

                if ($duplicate = $this->findDuplicateMember($attributes)) {
                    $this->recordRejectedRow($rowNumber, $record, [__('messages.duplicate_member_found', [
                        'member' => $duplicate->fullName(),
                    ])], $row);

                    continue;
                }

                DB::transaction(function () use ($assignmentService, $attributes, $row): void {
                    $zoneName = trim((string) ($row['zone'] ?? $row['kanda'] ?? ''));
                    $attributes['zone_id'] = $zoneName !== '' ? $this->findOrCreateZone($zoneName)->id : null;

                    $member = Member::create($attributes);

                    $assignmentService->assignDefaultDepartments($member, Auth::user());

                    collect($this->splitNames((string) ($row['departments'] ?? $row['idara'] ?? '')))
                        ->each(function (string $departmentName) use ($member): void {
                            $department = Department::firstOrCreate(
                                ['slug' => Str::slug($departmentName)],
                                ['name' => $departmentName, 'is_active' => true],
                            );

                            $member->departments()->syncWithoutDetaching([
                                $department->id => [
                                    'assigned_by_user_id' => Auth::id(),
                                    'assignment_source' => 'manual',
                                    'started_at' => now()->toDateString(),
                                    'is_active' => true,
                                ],
                            ]);
                        });
                });

                $this->recordImportedRow($rowNumber, $record, $row);
            } catch (Throwable $exception) {
                $this->recordRejectedRow($rowNumber, $record, $this->importFailureMessages($exception), $row);
            }
        }

        $this->memberImport = null;

        $this->dispatch('members-imported', count: $this->importReport['imported_count'], rejected: $this->importReport['rejected_count']);

        return $this->downloadImportReport($reportExporter);
    }

    public function saveRelationship(): void
    {
        abort_unless($this->canManageRelationships(), 403);

        $validated = $this->validate([
            'relationship_member_id' => ['required', 'integer', Rule::exists('members', 'id')],
            'related_member_id' => [
                'required',
                'integer',
                Rule::exists('members', 'id'),
                'different:relationship_member_id',
                Rule::unique('member_relationships', 'related_member_id')
                    ->ignore($this->editingRelationshipId)
                    ->where(fn ($query) => $query
                        ->where('member_id', $this->relationship_member_id)
                        ->where('relationship_type', $this->relationship_type)),
            ],
            'relationship_type' => ['required', Rule::in(array_keys($this->relationshipTypes()))],
            'relationship_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $attributes = [
            'member_id' => $validated['relationship_member_id'],
            'related_member_id' => $validated['related_member_id'],
            'relationship_type' => $validated['relationship_type'],
            'notes' => $validated['relationship_notes'] ?: null,
        ];

        if ($this->editingRelationshipId) {
            MemberRelationship::findOrFail($this->editingRelationshipId)->update($attributes);
        } else {
            MemberRelationship::create($attributes + [
                'created_by_user_id' => Auth::id(),
            ]);
        }

        $this->resetRelationshipForm();

        $this->dispatch('relationship-saved');
    }

    public function editRelationship(int $relationshipId): void
    {
        abort_unless($this->canManageRelationships(), 403);

        $relationship = MemberRelationship::findOrFail($relationshipId);

        $this->editingRelationshipId = $relationship->id;
        $this->section = 'relationships';
        $this->relationship_member_id = $relationship->member_id;
        $this->related_member_id = $relationship->related_member_id;
        $this->relationship_type = $relationship->relationship_type;
        $this->relationship_notes = $relationship->notes ?? '';
    }

    public function cancelRelationshipEdit(): void
    {
        $this->resetRelationshipForm();
    }

    public function deleteRelationship(int $relationshipId): void
    {
        abort_unless($this->canManageRelationships(), 403);

        MemberRelationship::findOrFail($relationshipId)->delete();

        if ($this->editingRelationshipId === $relationshipId) {
            $this->resetRelationshipForm();
        }

        $this->dispatch('relationship-deleted');
    }

    public function render(): View
    {
        $scope = UserDataScope::for(Auth::user());

        $members = $scope->applyMemberScope(Member::query())
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
            'section' => $this->section,
            'canCreateMembers' => $this->canCreateMembers(),
            'canUpdateMembers' => $this->canUpdateMembers(),
            'canImportMembers' => $this->canImportMembers(),
            'canManageRelationships' => $this->canManageRelationships(),
            'members' => $members,
            'relationshipMembers' => $scope->applyMemberScope(Member::query())
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
            'relationships' => MemberRelationship::query()
                ->with(['member', 'relatedMember'])
                ->latest()
                ->get(),
            'relationshipTypes' => $this->relationshipTypes(),
            'departments' => $scope->applyDepartmentScope(Department::query()->where('is_active', true))->orderBy('name')->get(),
            'zones' => $scope->applyZoneScope(Zone::query()->where('is_active', true))->orderBy('name')->get(),
            'importUploads' => ImportUpload::query()
                ->with('uploadedBy')
                ->where('module', 'members')
                ->latest()
                ->limit(10)
                ->get(),
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

    private function resetRelationshipForm(): void
    {
        $this->reset([
            'editingRelationshipId',
            'relationship_member_id',
            'related_member_id',
            'relationship_notes',
        ]);

        $this->relationship_type = 'spouse';
        $this->resetErrorBag();
    }

    private function canManageRelationships(): bool
    {
        return $this->permissionsAreUnseeded()
            || Auth::user()?->can('members.relationships')
            || Auth::user()?->can('members.update');
    }

    private function canCreateMembers(): bool
    {
        return $this->permissionsAreUnseeded() || (Auth::user()?->can('members.create') ?? false);
    }

    private function canUpdateMembers(): bool
    {
        return $this->permissionsAreUnseeded() || (Auth::user()?->can('members.update') ?? false);
    }

    private function canSaveMembers(): bool
    {
        return $this->canCreateMembers() || $this->canUpdateMembers();
    }

    private function canImportMembers(): bool
    {
        return $this->permissionsAreUnseeded() || (Auth::user()?->can('members.import') ?? false);
    }

    /**
     * @return array<string, string>
     */
    private function relationshipTypes(): array
    {
        return [
            'spouse' => __('messages.spouse'),
            'parent' => __('messages.parent'),
            'child' => __('messages.child'),
            'guardian' => __('messages.guardian'),
            'sibling' => __('messages.sibling'),
            'relative' => __('messages.relative'),
        ];
    }

    private function normalizeGender(mixed $gender): string
    {
        $value = Str::lower(trim((string) $gender));

        return match ($value) {
            'female', 'f', 'mwanamke', 'ke', 'woman' => 'female',
            'male', 'm', 'mwanaume', 'me', 'man' => 'male',
            default => $value,
        };
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::parse($value->format('Y-m-d'))->toDateString();
        }

        $value = trim((string) $value);

        return $value === '' ? null : Carbon::parse($value)->toDateString();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function memberImportRecordLabel(array $row): string
    {
        $name = trim(implode(' ', array_filter([
            trim((string) ($row['first_name'] ?? $row['jina_la_kwanza'] ?? '')),
            trim((string) ($row['middle_name'] ?? $row['jina_la_kati'] ?? '')),
            trim((string) ($row['last_name'] ?? $row['jina_la_mwisho'] ?? '')),
        ])));

        $phone = trim((string) ($row['phone_number'] ?? $row['phone'] ?? $row['simu'] ?? ''));

        return trim($name.($phone !== '' ? " ({$phone})" : ''));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function findDuplicateMember(array $attributes, ?int $ignoreMemberId = null): ?Member
    {
        $phone = trim((string) ($attributes['phone_number'] ?? ''));
        $email = trim((string) ($attributes['email'] ?? ''));

        $query = Member::query()
            ->when($ignoreMemberId, fn ($query) => $query->whereKeyNot($ignoreMemberId));

        if ($phone !== '' || $email !== '') {
            return $query
                ->where(function ($query) use ($phone, $email): void {
                    $query
                        ->when($phone !== '', fn ($query) => $query->where('phone_number', $phone))
                        ->when($email !== '', function ($query) use ($phone, $email): void {
                            $method = $phone !== '' ? 'orWhere' : 'where';
                            $query->{$method}('email', $email);
                        });
                })
                ->first();
        }

        $dateOfBirth = trim((string) ($attributes['date_of_birth'] ?? ''));

        if ($dateOfBirth === '') {
            return null;
        }

        return $query
            ->where('first_name', $attributes['first_name'] ?? '')
            ->where('last_name', $attributes['last_name'] ?? '')
            ->whereDate('date_of_birth', $dateOfBirth)
            ->first();
    }

    private function findOrCreateZone(string $zoneName): Zone
    {
        return Zone::firstOrCreate(
            ['slug' => Str::slug($zoneName)],
            ['name' => $zoneName, 'is_active' => true],
        );
    }

    /**
     * @return array<int, string>
     */
    private function splitNames(string $value): array
    {
        return collect(preg_split('/[,;|]/', $value) ?: [])
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->values()
            ->all();
    }
}
