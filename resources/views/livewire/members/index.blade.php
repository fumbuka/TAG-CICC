<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('messages.membership') }}</p>
                <h1 class="text-2xl font-semibold text-gray-950">{{ __('messages.members') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('messages.member_form_help') }}</p>
            </div>
        </div>

        <div x-data="{ show: false, message: '' }"
            x-on:member-created.window="message = '{{ __('messages.member_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:member-updated.window="message = '{{ __('messages.member_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:member-deleted.window="message = '{{ __('messages.member_deleted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:members-imported.window="message = '{{ __('messages.members_imported') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-show="show"
            x-cloak
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            x-text="message"></div>

        @error('delete')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</div>
        @enderror

        <div class="grid gap-6 lg:grid-cols-[0.95fr_1.4fr]">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">
                    {{ $editingMemberId ? __('messages.edit_member') : __('messages.register_member') }}
                </h2>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="first_name" :value="__('messages.first_name')" />
                            <x-text-input wire:model="first_name" id="first_name" class="mt-1 block w-full" type="text" />
                            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="last_name" :value="__('messages.last_name')" />
                            <x-text-input wire:model="last_name" id="last_name" class="mt-1 block w-full" type="text" />
                            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="middle_name" :value="__('messages.middle_name')" />
                        <x-text-input wire:model="middle_name" id="middle_name" class="mt-1 block w-full" type="text" />
                        <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="gender" :value="__('messages.gender')" />
                            <select wire:model="gender" id="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('messages.select_gender') }}</option>
                                <option value="female">{{ __('messages.female') }}</option>
                                <option value="male">{{ __('messages.male') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="date_of_birth" :value="__('messages.date_of_birth')" />
                            <x-text-input wire:model="date_of_birth" id="date_of_birth" class="mt-1 block w-full" type="date" />
                            <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="phone_number" :value="__('messages.phone')" />
                            <x-text-input wire:model="phone_number" id="phone_number" class="mt-1 block w-full" type="tel" />
                            <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('messages.email')" />
                            <x-text-input wire:model="email" id="email" class="mt-1 block w-full" type="email" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="residential_area" :value="__('messages.residential_area')" />
                            <x-text-input wire:model="residential_area" id="residential_area" class="mt-1 block w-full" type="text" />
                            <x-input-error :messages="$errors->get('residential_area')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="zone_id" :value="__('messages.zone')" />
                            <select wire:model="zone_id" id="zone_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('messages.no_zone_selected') }}</option>
                                @foreach ($zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('zone_id')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label :value="__('messages.manual_department_assignment')" />
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach ($departments as $department)
                                <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700">
                                    <input wire:model="department_ids" type="checkbox" value="{{ $department->id }}" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600">
                                    <span>{{ $department->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('department_ids')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2">
                        @if ($editingMemberId)
                            <x-secondary-button type="button" wire:click="cancelEdit">{{ __('messages.cancel') }}</x-secondary-button>
                        @endif
                        <x-primary-button>{{ __('messages.save') }}</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.members_list') }}</h2>
                        <x-text-input wire:model.live.debounce.300ms="search" class="w-full sm:w-72" type="search" :placeholder="__('messages.search_members')" />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('messages.name') }}</th>
                                <th class="px-5 py-3">{{ __('messages.phone') }}</th>
                                <th class="px-5 py-3">{{ __('messages.zone') }}</th>
                                <th class="px-5 py-3">{{ __('messages.departments') }}</th>
                                <th class="px-5 py-3">{{ __('messages.status') }}</th>
                                <th class="px-5 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($members as $member)
                                <tr>
                                    <td class="px-5 py-4 font-medium text-gray-950">
                                        {{ trim($member->first_name.' '.$member->middle_name.' '.$member->last_name) }}
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ $member->phone_number ?: '-' }}</td>
                                    <td class="px-5 py-4 text-gray-600">{{ $member->zone?->name ?: '-' }}</td>
                                    <td class="px-5 py-4 text-gray-600">
                                        {{ $member->departments->pluck('name')->join(', ') ?: '-' }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">
                                            {{ __('messages.active') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-3">
                                            <button wire:click="edit({{ $member->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
                                                {{ __('messages.edit') }}
                                            </button>
                                            <button wire:click="delete({{ $member->id }})" wire:confirm="{{ __('messages.confirm_delete_member') }}" type="button" class="text-sm font-medium text-red-600 hover:text-red-800">
                                                {{ __('messages.delete') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-gray-500">{{ __('messages.no_members') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $members->links() }}
                </div>
            </section>
        </div>

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.bulk_import_members') }}</h2>
                    <p class="mt-1 text-sm text-gray-600">{{ __('messages.members_import_help') }}</p>
                </div>
                <a href="{{ route('bulk-import-templates.download', 'members') }}" class="inline-flex items-center justify-center rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                    {{ __('messages.download_template') }}
                </a>
            </div>

            <form wire:submit="import" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input-label for="memberImport" :value="__('messages.import_file')" />
                    <input wire:model="memberImport" id="memberImport" type="file" accept=".csv,.txt,.xlsx,.ods" class="mt-1 block w-full text-sm text-gray-700 file:me-4 file:rounded-md file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100" />
                    <x-input-error :messages="$errors->get('memberImport')" class="mt-2" />
                </div>
                <x-primary-button wire:loading.attr="disabled" wire:target="memberImport,import">
                    {{ __('messages.upload') }}
                </x-primary-button>
            </form>
        </section>
    </div>
</div>
