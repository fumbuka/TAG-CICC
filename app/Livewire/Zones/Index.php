<?php

namespace App\Livewire\Zones;

use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public ?int $editingZoneId = null;

    public string $name = '';

    public string $description = '';

    public function save(): void
    {
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
        $zone = Zone::findOrFail($zoneId);

        $zone->update([
            'is_active' => ! $zone->is_active,
        ]);
    }

    public function render(): View
    {
        return view('livewire.zones.index', [
            'zones' => Zone::query()
                ->withCount('members')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingZoneId', 'name', 'description']);
        $this->resetErrorBag();
    }
}
