<?php

namespace App\Livewire\Services;

use App\Models\Department;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public ?int $editingServiceId = null;

    public string $search = '';

    public ?int $service_type_id = null;

    public ?int $department_id = null;

    public ?int $zone_id = null;

    public string $title = '';

    public string $service_date = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public string $speaker = '';

    public string $topic = '';

    public ?int $attendance_count = null;

    public string $notes = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'service_type_id' => ['required', 'integer', Rule::exists('service_types', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'zone_id' => ['nullable', 'integer', Rule::exists('zones', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'service_date' => ['required', 'date'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after_or_equal:starts_at'],
            'speaker' => ['nullable', 'string', 'max:255'],
            'topic' => ['nullable', 'string', 'max:255'],
            'attendance_count' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $wasEditing = $this->editingServiceId !== null;

        $attributes = [
            'service_type_id' => $validated['service_type_id'],
            'department_id' => $validated['department_id'],
            'zone_id' => $validated['zone_id'],
            'title' => $validated['title'],
            'service_date' => $validated['service_date'],
            'starts_at' => $validated['starts_at'] ?: null,
            'ends_at' => $validated['ends_at'] ?: null,
            'speaker' => $validated['speaker'] ?: null,
            'topic' => $validated['topic'] ?: null,
            'attendance_count' => $validated['attendance_count'],
            'notes' => $validated['notes'] ?: null,
        ];

        $this->editingServiceId
            ? Service::findOrFail($this->editingServiceId)->update($attributes)
            : Service::create($attributes);

        $this->resetForm();

        $this->dispatch($wasEditing ? 'service-updated' : 'service-created');
    }

    public function edit(int $serviceId): void
    {
        $service = Service::findOrFail($serviceId);

        $this->editingServiceId = $service->id;
        $this->service_type_id = $service->service_type_id;
        $this->department_id = $service->department_id;
        $this->zone_id = $service->zone_id;
        $this->title = $service->title;
        $this->service_date = $service->service_date?->toDateString() ?? '';
        $this->starts_at = $service->starts_at ? substr((string) $service->starts_at, 0, 5) : '';
        $this->ends_at = $service->ends_at ? substr((string) $service->ends_at, 0, 5) : '';
        $this->speaker = $service->speaker ?? '';
        $this->topic = $service->topic ?? '';
        $this->attendance_count = $service->attendance_count;
        $this->notes = $service->notes ?? '';
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(int $serviceId): void
    {
        $service = Service::query()
            ->withCount('financialTransactions')
            ->findOrFail($serviceId);

        if ($service->financial_transactions_count > 0) {
            $this->addError('delete', __('messages.service_delete_blocked'));

            return;
        }

        $service->delete();

        if ($this->editingServiceId === $serviceId) {
            $this->resetForm();
        }

        $this->dispatch('service-deleted');
    }

    public function render(): View
    {
        $services = Service::query()
            ->with(['serviceType', 'department', 'zone'])
            ->withSum('financialTransactions', 'amount')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('title', 'like', "%{$this->search}%")
                        ->orWhere('speaker', 'like', "%{$this->search}%")
                        ->orWhere('topic', 'like', "%{$this->search}%")
                        ->orWhereDate('service_date', $this->search);
                });
            })
            ->latest('service_date')
            ->latest()
            ->paginate(10);

        return view('livewire.services.index', [
            'services' => $services,
            'serviceTypes' => ServiceType::query()->where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'zones' => Zone::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingServiceId',
            'service_type_id',
            'department_id',
            'zone_id',
            'title',
            'service_date',
            'starts_at',
            'ends_at',
            'speaker',
            'topic',
            'attendance_count',
            'notes',
        ]);

        $this->resetErrorBag();
    }
}
