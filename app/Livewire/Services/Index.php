<?php

namespace App\Livewire\Services;

use App\Livewire\Concerns\ChecksSeededPermissions;
use App\Models\Department;
use App\Models\Service;
use App\Models\ServiceRoutine;
use App\Models\ServiceType;
use App\Models\Zone;
use App\Support\UserDataScope;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;
    use ChecksSeededPermissions;

    public ?int $editingServiceId = null;

    public string $section = 'list';

    public string $search = '';

    public ?int $service_routine_id = null;

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

    public function mount(?string $section = null): void
    {
        $this->section = $section ?: 'list';

        abort_unless(match ($this->section) {
            'create' => $this->canRecordServices(),
            default => $this->canViewServices(),
        }, 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedServiceRoutineId(): void
    {
        if (! $this->service_routine_id) {
            return;
        }

        $routine = ServiceRoutine::find($this->service_routine_id);

        if (! $routine) {
            return;
        }

        $this->service_type_id = $routine->service_type_id;
        $this->department_id = $routine->department_id;
        $this->zone_id = $routine->zone_id;
        $this->title = $routine->title;
        $this->starts_at = $routine->starts_at ? substr((string) $routine->starts_at, 0, 5) : '';
        $this->ends_at = $routine->ends_at ? substr((string) $routine->ends_at, 0, 5) : '';
        $this->speaker = $routine->speaker ?? '';
        $this->topic = $routine->topic ?? '';
    }

    public function save(): void
    {
        abort_unless($this->canRecordServices(), 403);

        $validated = $this->validate([
            'service_routine_id' => ['required', 'integer', Rule::exists('service_routines', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'zone_id' => ['nullable', 'integer', Rule::exists('zones', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after_or_equal:starts_at'],
            'speaker' => ['nullable', 'string', 'max:255'],
            'topic' => ['nullable', 'string', 'max:255'],
            'attendance_count' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $wasEditing = $this->editingServiceId !== null;
        $routine = ServiceRoutine::findOrFail($validated['service_routine_id']);
        $service = $this->editingServiceId ? Service::findOrFail($this->editingServiceId) : null;

        $attributes = [
            'service_type_id' => $routine->service_type_id,
            'service_routine_id' => $routine->id,
            'department_id' => $validated['department_id'],
            'zone_id' => $validated['zone_id'],
            'title' => $validated['title'],
            'service_date' => $service?->service_routine_id === $routine->id
                ? $service->service_date
                : $this->nextDateForRoutine($routine),
            'starts_at' => $validated['starts_at'] ?: null,
            'ends_at' => $validated['ends_at'] ?: null,
            'speaker' => $validated['speaker'] ?: null,
            'topic' => $validated['topic'] ?: null,
            'attendance_count' => $validated['attendance_count'],
            'notes' => $validated['notes'] ?: null,
        ];

        $service
            ? $service->update($attributes)
            : Service::create($attributes);

        $this->resetForm();

        $this->dispatch($wasEditing ? 'service-updated' : 'service-created');
    }

    public function edit(int $serviceId): void
    {
        abort_unless($this->canRecordServices(), 403);

        $service = Service::findOrFail($serviceId);

        $this->editingServiceId = $service->id;
        $this->section = 'create';
        $this->service_routine_id = $service->service_routine_id;
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
        abort_unless($this->canRecordServices(), 403);

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
        $scope = UserDataScope::for(Auth::user());

        $services = $scope->applyServiceScope(Service::query())
            ->with(['serviceType', 'serviceRoutine', 'department', 'zone'])
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
            'section' => $this->section,
            'canRecordServices' => $this->canRecordServices(),
            'services' => $services,
            'serviceRoutines' => $scope
                ->applyServiceScope(
                    ServiceRoutine::query()
                        ->with(['serviceType', 'department', 'zone'])
                        ->where('is_active', true),
                )
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->orderBy('title')
                ->get(),
            'serviceTypes' => ServiceType::query()->where('is_active', true)->orderBy('name')->get(),
            'departments' => $scope->applyDepartmentScope(Department::query()->where('is_active', true))->orderBy('name')->get(),
            'zones' => $scope->applyZoneScope(Zone::query()->where('is_active', true))->orderBy('name')->get(),
            'selectedRoutineNextDate' => $this->selectedRoutineNextDate(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingServiceId',
            'service_routine_id',
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

    private function selectedRoutineNextDate(): ?string
    {
        if (! $this->service_routine_id) {
            return null;
        }

        $routine = ServiceRoutine::find($this->service_routine_id);

        return $routine ? $this->nextDateForRoutine($routine) : null;
    }

    private function nextDateForRoutine(ServiceRoutine $routine): string
    {
        $today = Carbon::today();
        $daysToAdd = ($routine->day_of_week - $today->dayOfWeek + 7) % 7;

        return $today->copy()->addDays($daysToAdd)->toDateString();
    }

    private function canViewServices(): bool
    {
        return $this->permissionsAreUnseeded()
            || Auth::user()?->can('services.manage')
            || Auth::user()?->can('services.list');
    }

    private function canRecordServices(): bool
    {
        return $this->permissionsAreUnseeded()
            || Auth::user()?->can('services.manage')
            || Auth::user()?->can('services.record');
    }
}
