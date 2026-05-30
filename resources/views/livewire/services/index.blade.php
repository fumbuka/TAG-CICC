<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('messages.services') }}</p>
                <h1 class="text-2xl font-semibold text-gray-950">{{ __('messages.services') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('messages.service_form_help') }}</p>
            </div>
        </div>

        <div x-data="{ show: false, message: '' }"
            x-on:service-created.window="message = '{{ __('messages.service_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:service-updated.window="message = '{{ __('messages.service_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:service-deleted.window="message = '{{ __('messages.service_deleted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-show="show"
            x-cloak
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            x-text="message"></div>

        @error('delete')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</div>
        @enderror

        <div class="grid gap-6 lg:grid-cols-[0.95fr_1.4fr]">
            <section id="record-service" class="scroll-mt-24 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">
                    {{ $editingServiceId ? __('messages.edit_service') : __('messages.record_service') }}
                </h2>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div>
                        <x-input-label for="title" :value="__('messages.service_title')" />
                        <x-text-input wire:model="title" id="title" class="mt-1 block w-full" type="text" />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="service_routine_id" :value="__('messages.service_routine')" />
                        <select wire:model.live="service_routine_id" id="service_routine_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('messages.select_service_routine') }}</option>
                            @foreach ($serviceRoutines as $routine)
                                <option value="{{ $routine->id }}">
                                    {{ $routine->title }} - {{ __('messages.day_'.$routine->day_of_week) }}
                                    @if ($routine->starts_at)
                                        {{ substr((string) $routine->starts_at, 0, 5) }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('service_routine_id')" class="mt-2" />

                        @if ($selectedRoutineNextDate)
                            <p class="mt-2 text-xs font-medium text-emerald-700">
                                {{ __('messages.next_service_date') }}: {{ $selectedRoutineNextDate }}
                            </p>
                        @endif
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="starts_at" :value="__('messages.starts_at')" />
                            <x-text-input wire:model="starts_at" id="starts_at" class="mt-1 block w-full" type="time" />
                            <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="ends_at" :value="__('messages.ends_at')" />
                            <x-text-input wire:model="ends_at" id="ends_at" class="mt-1 block w-full" type="time" />
                            <x-input-error :messages="$errors->get('ends_at')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="department_id" :value="__('messages.department')" />
                            <select wire:model="department_id" id="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('messages.no_department_selected') }}</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
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

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="speaker" :value="__('messages.speaker')" />
                            <x-text-input wire:model="speaker" id="speaker" class="mt-1 block w-full" type="text" />
                            <x-input-error :messages="$errors->get('speaker')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="topic" :value="__('messages.topic')" />
                            <x-text-input wire:model="topic" id="topic" class="mt-1 block w-full" type="text" />
                            <x-input-error :messages="$errors->get('topic')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="attendance_count" :value="__('messages.attendance')" />
                        <x-text-input wire:model="attendance_count" id="attendance_count" class="mt-1 block w-full" type="number" min="0" />
                        <x-input-error :messages="$errors->get('attendance_count')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="notes" :value="__('messages.notes')" />
                        <textarea wire:model="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2">
                        @if ($editingServiceId)
                            <x-secondary-button type="button" wire:click="cancelEdit">{{ __('messages.cancel') }}</x-secondary-button>
                        @endif
                        <x-primary-button>{{ __('messages.save') }}</x-primary-button>
                    </div>
                </form>
            </section>

            <section id="services-list" class="scroll-mt-24 rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.services_list') }}</h2>
                        <x-text-input wire:model.live.debounce.300ms="search" class="w-full sm:w-72" type="search" :placeholder="__('messages.search_services')" />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('messages.service') }}</th>
                                <th class="px-5 py-3">{{ __('messages.date') }}</th>
                                <th class="px-5 py-3">{{ __('messages.context') }}</th>
                                <th class="px-5 py-3">{{ __('messages.attendance') }}</th>
                                <th class="px-5 py-3">{{ __('messages.total_income') }}</th>
                                <th class="px-5 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($services as $service)
                                <tr>
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-gray-950">{{ $service->title }}</div>
                                        <div class="text-xs text-gray-500">{{ $service->serviceRoutine?->title ?: $service->serviceType?->name ?: '-' }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">
                                        <div>{{ $service->service_date?->format('Y-m-d') }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ $service->starts_at ? substr((string) $service->starts_at, 0, 5) : '--:--' }}
                                            @if ($service->ends_at)
                                                - {{ substr((string) $service->ends_at, 0, 5) }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">
                                        <div>{{ $service->department?->name ?: '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ $service->zone?->name ?: '-' }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ $service->attendance_count ?? '-' }}</td>
                                    <td class="px-5 py-4 text-gray-600">
                                        {{ __('messages.currency_tzs') }} {{ number_format((float) ($service->financial_transactions_sum_amount ?? 0), 2) }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-3">
                                            <button wire:click="edit({{ $service->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
                                                {{ __('messages.edit') }}
                                            </button>
                                            <button wire:click="delete({{ $service->id }})" wire:confirm="{{ __('messages.confirm_delete_service') }}" type="button" class="text-sm font-medium text-red-600 hover:text-red-800">
                                                {{ __('messages.delete') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-gray-500">{{ __('messages.no_services') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $services->links() }}
                </div>
            </section>
        </div>
    </div>
</div>
