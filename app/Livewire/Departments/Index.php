<?php

namespace App\Livewire\Departments;

use App\Models\Department;
use App\Services\SpreadsheetImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads;

    public ?int $editingDepartmentId = null;

    public string $name = '';

    public string $description = '';

    public bool $is_age_based = false;

    public ?int $minimum_age = null;

    public ?int $maximum_age = null;

    public string $gender_rule = '';

    public $departmentImport = null;

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($this->editingDepartmentId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_age_based' => ['boolean'],
            'minimum_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'maximum_age' => ['nullable', 'integer', 'min:0', 'max:120', 'gte:minimum_age'],
            'gender_rule' => ['nullable', Rule::in(['male', 'female'])],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?: null,
            'is_age_based' => $validated['is_age_based'],
            'minimum_age' => $validated['minimum_age'],
            'maximum_age' => $validated['maximum_age'],
            'gender_rule' => $validated['gender_rule'] ?: null,
            'is_active' => true,
        ];

        $wasEditing = $this->editingDepartmentId !== null;

        $wasEditing
            ? Department::findOrFail($this->editingDepartmentId)->update($attributes)
            : Department::create($attributes);

        $this->resetForm();

        $this->dispatch($wasEditing ? 'department-updated' : 'department-created');
    }

    public function edit(int $departmentId): void
    {
        $department = Department::findOrFail($departmentId);

        $this->editingDepartmentId = $department->id;
        $this->name = $department->name;
        $this->description = $department->description ?? '';
        $this->is_age_based = $department->is_age_based;
        $this->minimum_age = $department->minimum_age;
        $this->maximum_age = $department->maximum_age;
        $this->gender_rule = $department->gender_rule ?? '';
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(int $departmentId): void
    {
        $department = Department::withCount('members')->findOrFail($departmentId);

        if ($department->members_count > 0) {
            $this->addError('delete', __('messages.department_delete_blocked'));

            return;
        }

        $department->delete();

        $this->dispatch('department-deleted');
    }

    public function toggleActive(int $departmentId): void
    {
        $department = Department::findOrFail($departmentId);

        $department->update([
            'is_active' => ! $department->is_active,
        ]);
    }

    public function import(SpreadsheetImportService $importer): void
    {
        $this->validate([
            'departmentImport' => ['required', 'file', 'mimes:csv,txt,xlsx,ods', 'max:5120'],
        ]);

        $imported = 0;

        foreach ($importer->rows($this->departmentImport) as $row) {
            $name = trim((string) ($row['name'] ?? $row['department_name'] ?? $row['jina'] ?? $row['jina_la_idara'] ?? ''));

            $attributes = [
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => trim((string) ($row['description'] ?? $row['maelezo'] ?? '')) ?: null,
                'is_age_based' => $this->normalizeBoolean($row['is_age_based'] ?? $row['age_rule'] ?? $row['inategemea_umri'] ?? $row['rule_ya_umri'] ?? false),
                'minimum_age' => $this->normalizeInteger($row['minimum_age'] ?? $row['umri_wa_chini'] ?? null),
                'maximum_age' => $this->normalizeInteger($row['maximum_age'] ?? $row['umri_wa_juu'] ?? null),
                'gender_rule' => $this->normalizeGender($row['gender_rule'] ?? $row['jinsia'] ?? null),
                'is_active' => true,
            ];

            Validator::make($attributes, [
                'name' => ['required', 'string', 'max:255'],
                'minimum_age' => ['nullable', 'integer', 'min:0', 'max:120'],
                'maximum_age' => ['nullable', 'integer', 'min:0', 'max:120', 'gte:minimum_age'],
                'gender_rule' => ['nullable', Rule::in(['male', 'female'])],
            ])->validate();

            Department::updateOrCreate(
                ['slug' => $attributes['slug']],
                $attributes,
            );

            $imported++;
        }

        $this->departmentImport = null;

        $this->dispatch('departments-imported', count: $imported);
    }

    public function render(): View
    {
        return view('livewire.departments.index', [
            'departments' => Department::query()
                ->withCount('members')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingDepartmentId', 'name', 'description', 'is_age_based', 'minimum_age', 'maximum_age', 'gender_rule']);
        $this->resetErrorBag();
    }

    private function normalizeGender(mixed $gender): ?string
    {
        $value = Str::lower(trim((string) $gender));

        return match ($value) {
            '' => null,
            'female', 'f', 'mwanamke', 'ke', 'woman' => 'female',
            'male', 'm', 'mwanaume', 'me', 'man' => 'male',
            default => $value,
        };
    }

    private function normalizeBoolean(mixed $value): bool
    {
        return in_array(Str::lower(trim((string) $value)), ['1', 'true', 'yes', 'ndio', 'y'], true);
    }

    private function normalizeInteger(mixed $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }
}
