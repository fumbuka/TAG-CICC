<?php

namespace App\Livewire\Zones;

use App\Livewire\Concerns\ChecksSeededPermissions;
use App\Livewire\Concerns\TracksImportResults;
use App\Models\ImportUpload;
use App\Models\Zone;
use App\Services\ImportReportExportService;
use App\Services\SpreadsheetImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

#[Layout('layouts.app')]
class Index extends Component
{
    use TracksImportResults;
    use ChecksSeededPermissions;
    use WithFileUploads;

    public ?int $editingZoneId = null;

    public string $section = 'list';

    public string $name = '';

    public string $description = '';

    public $zoneImport = null;

    public function mount(?string $section = null): void
    {
        $this->section = $section ?: 'list';

        abort_unless(match ($this->section) {
            'create' => $this->canCreateZones(),
            'import' => $this->canImportZones(),
            default => $this->canViewZones(),
        }, 403);
    }

    public function save(): void
    {
        abort_unless($this->editingZoneId ? $this->canManageZones() : $this->canCreateZones(), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('zones', 'name')->ignore($this->editingZoneId)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $wasEditing = $this->editingZoneId !== null;

        $attributes = [
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?: null,
            'is_active' => true,
        ];

        $wasEditing
            ? Zone::findOrFail($this->editingZoneId)->update($attributes)
            : Zone::create($attributes);

        $this->resetForm();

        $this->dispatch($wasEditing ? 'zone-updated' : 'zone-created');
    }

    public function edit(int $zoneId): void
    {
        abort_unless($this->canManageZones(), 403);

        $zone = Zone::findOrFail($zoneId);

        $this->editingZoneId = $zone->id;
        $this->name = $zone->name;
        $this->description = $zone->description ?? '';
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(int $zoneId): void
    {
        abort_unless($this->canManageZones(), 403);

        $zone = Zone::withCount('members')->findOrFail($zoneId);

        if ($zone->members_count > 0) {
            $this->addError('delete', __('messages.zone_delete_blocked'));

            return;
        }

        $zone->delete();

        $this->dispatch('zone-deleted');
    }

    public function toggleActive(int $zoneId): void
    {
        abort_unless($this->canManageZones(), 403);

        $zone = Zone::findOrFail($zoneId);

        $zone->update([
            'is_active' => ! $zone->is_active,
        ]);
    }

    public function import(SpreadsheetImportService $importer, ImportReportExportService $reportExporter): BinaryFileResponse
    {
        abort_unless($this->canImportZones(), 403);

        $this->validate([
            'zoneImport' => ['required', 'file', 'mimes:csv,txt,xlsx,ods', 'max:5120'],
        ]);

        $originalFilename = $this->zoneImport?->getClientOriginalName();
        $rows = $importer->rowsWithMetadata($this->zoneImport);
        $this->startImportReport('zones', count($rows), $originalFilename);

        foreach ($rows as $entry) {
            $rowNumber = $entry['row_number'];
            $row = $entry['data'];
            $name = trim((string) ($row['name'] ?? $row['zone_name'] ?? $row['jina'] ?? $row['jina_la_kanda'] ?? ''));

            try {
                $attributes = [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'description' => trim((string) ($row['description'] ?? $row['maelezo'] ?? '')) ?: null,
                    'is_active' => true,
                ];

                Validator::make($attributes, [
                    'name' => ['required', 'string', 'max:255'],
                ])->validate();

                Zone::updateOrCreate(
                    ['slug' => $attributes['slug']],
                    $attributes,
                );

                $this->recordImportedRow($rowNumber, $name, $row);
            } catch (Throwable $exception) {
                $this->recordRejectedRow($rowNumber, $name, $this->importFailureMessages($exception), $row);
            }
        }

        $this->zoneImport = null;

        $this->dispatch('zones-imported', count: $this->importReport['imported_count'], rejected: $this->importReport['rejected_count']);

        return $this->downloadImportReport($reportExporter);
    }

    public function render(): View
    {
        return view('livewire.zones.index', [
            'section' => $this->section,
            'canCreateZones' => $this->canCreateZones(),
            'canImportZones' => $this->canImportZones(),
            'canManageZones' => $this->canManageZones(),
            'zones' => Zone::query()
                ->withCount('members')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
            'importUploads' => ImportUpload::query()
                ->with('uploadedBy')
                ->where('module', 'zones')
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingZoneId', 'name', 'description']);
        $this->resetErrorBag();
    }

    private function canViewZones(): bool
    {
        return $this->permissionsAreUnseeded()
            || auth()->user()?->can('zones.manage')
            || auth()->user()?->can('zones.list');
    }

    private function canCreateZones(): bool
    {
        return $this->permissionsAreUnseeded()
            || auth()->user()?->can('zones.manage')
            || auth()->user()?->can('zones.create');
    }

    private function canImportZones(): bool
    {
        return $this->permissionsAreUnseeded()
            || auth()->user()?->can('zones.manage')
            || auth()->user()?->can('zones.import');
    }

    private function canManageZones(): bool
    {
        return $this->permissionsAreUnseeded() || (auth()->user()?->can('zones.manage') ?? false);
    }
}
