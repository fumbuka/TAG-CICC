<?php

namespace App\Livewire\Members;

use App\Livewire\Concerns\TracksImportResults;
use App\Models\Department;
use App\Models\Member;
use App\Models\Zone;
use App\Services\MemberDepartmentAssignmentService;
use App\Services\SpreadsheetImportService;
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
use Throwable;

#[Layout('layouts.app')]
class Index extends Component
{
    use TracksImportResults;
    use WithFileUploads;
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

    public $memberImport = null;

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

    public function import(SpreadsheetImportService $importer, MemberDepartmentAssignmentService $assignmentService): void
    {
        $this->validate([
            'memberImport' => ['required', 'file', 'mimes:csv,txt,xlsx,ods', 'max:5120'],
        ]);

        $rows = $importer->rowsWithMetadata($this->memberImport);
        $this->startImportReport('members', count($rows));

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

                $this->recordImportedRow($rowNumber, $record);
            } catch (Throwable $exception) {
                $this->recordRejectedRow($rowNumber, $record, $this->importFailureMessages($exception));
            }
        }

        $this->memberImport = null;

        $this->dispatch('members-imported', count: $this->importReport['imported_count'], rejected: $this->importReport['rejected_count']);
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
