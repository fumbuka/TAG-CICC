<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-medium text-emerald-700">Organization</p>
            <h1 class="text-2xl font-semibold text-gray-950">Zones</h1>
            <p class="mt-1 text-sm text-gray-600">Ongeza na simamia zones kulingana na maeneo ya washirika.</p>
        </div>

        <div x-data="{ show: false }" x-on:zone-created.window="show = true; setTimeout(() => show = false, 3500)" x-show="show" x-cloak class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            Zone imeongezwa.
        </div>

        <div class="grid gap-6 lg:grid-cols-[0.75fr_1.4fr]">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">Ongeza zone</h2>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div>
                        <x-input-label for="name" value="Zone name" />
                        <x-text-input wire:model="name" id="name" class="mt-1 block w-full" type="text" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" value="Description" />
                        <textarea wire:model="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>Ongeza zone</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <h2 class="text-lg font-semibold text-gray-950">Zones zilizopo</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Name</th>
                                <th class="px-5 py-3">Members</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($zones as $zone)
                                <tr>
                                    <td class="px-5 py-4 font-medium text-gray-950">{{ $zone->name }}</td>
                                    <td class="px-5 py-4 text-gray-600">{{ $zone->members_count }}</td>
                                    <td class="px-5 py-4">
                                        <span @class([
                                            'rounded-full px-2 py-1 text-xs font-medium',
                                            'bg-emerald-50 text-emerald-700' => $zone->is_active,
                                            'bg-gray-100 text-gray-600' => ! $zone->is_active,
                                        ])>
                                            {{ $zone->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <button wire:click="toggleActive({{ $zone->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
                                            {{ $zone->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
