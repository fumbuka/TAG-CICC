<?php

namespace App\Livewire\Departments;

use App\Models\Department;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public ?int $editingDepartmentId = null;

    public string $name = '';

    public string $description = '';

    public bool $is_age_based = false;

    public ?int $minimum_age = null;

    public ?int $maximum_age = null;

    public string $gender_rule = '';

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
}
