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
    public string $name = '';

    public string $description = '';

    public bool $is_age_based = false;

    public ?int $minimum_age = null;

    public ?int $maximum_age = null;

    public string $gender_rule = '';

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_age_based' => ['boolean'],
            'minimum_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'maximum_age' => ['nullable', 'integer', 'min:0', 'max:120', 'gte:minimum_age'],
            'gender_rule' => ['nullable', Rule::in(['male', 'female'])],
        ]);

        Department::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?: null,
            'is_age_based' => $validated['is_age_based'],
            'minimum_age' => $validated['minimum_age'],
            'maximum_age' => $validated['maximum_age'],
            'gender_rule' => $validated['gender_rule'] ?: null,
            'is_active' => true,
        ]);

        $this->reset(['name', 'description', 'is_age_based', 'minimum_age', 'maximum_age', 'gender_rule']);

        $this->dispatch('department-created');
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
}
