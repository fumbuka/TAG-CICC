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
    public string $name = '';

    public string $description = '';

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('zones', 'name')],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Zone::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?: null,
            'is_active' => true,
        ]);

        $this->reset(['name', 'description']);

        $this->dispatch('zone-created');
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
}
