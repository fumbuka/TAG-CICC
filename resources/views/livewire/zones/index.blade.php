<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-medium text-emerald-700">{{ __('messages.departments') }}</p>
            <h1 class="text-2xl font-semibold text-gray-950">{{ __('messages.zones') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('messages.zone_help') }}</p>
        </div>

        <div x-data="{ show: false, message: '' }"
            x-on:zone-created.window="message = '{{ __('messages.zone_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:zone-updated.window="message = '{{ __('messages.zone_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:zone-deleted.window="message = '{{ __('messages.zone_deleted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-show="show"
            x-cloak
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            x-text="message"></div>

        @error('delete')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</div>
        @enderror

        <div class="grid gap-6 lg:grid-cols-[0.75fr_1.4fr]">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">
                    {{ $editingZoneId ? __('messages.edit_zone') : __('messages.add_zone') }}
                </h2>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div>
                        <x-input-label for="name" :value="__('messages.zone_name')" />
                        <x-text-input wire:model="name" id="name" class="mt-1 block w-full" type="text" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('messages.description')" />
                        <textarea wire:model="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2">
                        @if ($editingZoneId)
                            <x-secondary-button type="button" wire:click="cancelEdit">{{ __('messages.cancel') }}</x-secondary-button>
                        @endif
                        <x-primary-button>{{ __('messages.save') }}</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.existing_zones') }}</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('messages.name') }}</th>
                                <th class="px-5 py-3">{{ __('messages.members') }}</th>
                                <th class="px-5 py-3">{{ __('messages.status') }}</th>
                                <th class="px-5 py-3">{{ __('messages.actions') }}</th>
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
                                            {{ $zone->is_active ? __('messages.active') : __('messages.inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-3">
                                            <button wire:click="edit({{ $zone->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
                                                {{ __('messages.edit') }}
                                            </button>
                                            <button wire:click="toggleActive({{ $zone->id }})" type="button" class="text-sm font-medium text-gray-700 hover:text-gray-950">
                                                {{ $zone->is_active ? __('messages.deactivate') : __('messages.activate') }}
                                            </button>
                                            <button wire:click="delete({{ $zone->id }})" wire:confirm="{{ __('messages.confirm_delete_zone') }}" type="button" class="text-sm font-medium text-red-600 hover:text-red-800">
                                                {{ __('messages.delete') }}
                                            </button>
                                        </div>
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
