<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-medium text-emerald-700">{{ __('messages.calendar') }}</p>
            <h1 class="text-2xl font-semibold text-gray-950">{{ __('messages.calendar') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('messages.calendar_help') }}</p>
        </div>

        <div x-data="{ show: false, message: '' }"
            x-on:event-created.window="message = '{{ __('messages.event_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:event-updated.window="message = '{{ __('messages.event_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:event-deleted.window="message = '{{ __('messages.event_deleted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:duty-created.window="message = '{{ __('messages.duty_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:duty-updated.window="message = '{{ __('messages.duty_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:duty-deleted.window="message = '{{ __('messages.duty_deleted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-show="show"
            x-cloak
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            x-text="message"></div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">
                    {{ $editingEventId ? __('messages.edit_event') : __('messages.add_event') }}
                </h2>

                <form wire:submit="saveEvent" class="mt-5 space-y-4">
                    <div>
                        <x-input-label for="title" :value="__('messages.event_title')" />
                        <x-text-input wire:model="title" id="title" class="mt-1 block w-full" type="text" />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="event_date" :value="__('messages.event_date')" />
                            <x-text-input wire:model="event_date" id="event_date" class="mt-1 block w-full" type="date" />
                            <x-input-error :messages="$errors->get('event_date')" class="mt-2" />
                        </div>

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

                    <div>
                        <x-input-label for="description" :value="__('messages.description')" />
                        <textarea wire:model="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input wire:model="is_important" type="checkbox" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600">
                            <span>{{ __('messages.important_event') }}</span>
                        </label>

                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input wire:model="is_active" type="checkbox" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600">
                            <span>{{ __('messages.active') }}</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2">
                        @if ($editingEventId)
                            <x-secondary-button type="button" wire:click="cancelEventEdit">{{ __('messages.cancel') }}</x-secondary-button>
                        @endif
                        <x-primary-button>{{ __('messages.save') }}</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">
                    {{ $editingDutyId ? __('messages.edit_weekly_duty') : __('messages.add_weekly_duty') }}
                </h2>

                <form wire:submit="saveDuty" class="mt-5 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="week_start" :value="__('messages.week_start')" />
                            <x-text-input wire:model="week_start" id="week_start" class="mt-1 block w-full" type="date" />
                            <x-input-error :messages="$errors->get('week_start')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="week_end" :value="__('messages.week_end')" />
                            <x-text-input wire:model="week_end" id="week_end" class="mt-1 block w-full" type="date" />
                            <x-input-error :messages="$errors->get('week_end')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="elder_member_id" :value="__('messages.elder')" />
                            <select wire:model="elder_member_id" id="elder_member_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('messages.not_assigned') }}</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}">{{ trim($member->first_name.' '.$member->middle_name.' '.$member->last_name) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('elder_member_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="deacon_member_id" :value="__('messages.deacon')" />
                            <select wire:model="deacon_member_id" id="deacon_member_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('messages.not_assigned') }}</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}">{{ trim($member->first_name.' '.$member->middle_name.' '.$member->last_name) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('deacon_member_id')" class="mt-2" />
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input wire:model="duty_is_active" type="checkbox" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600">
                        <span>{{ __('messages.active') }}</span>
                    </label>

                    <div>
                        <x-input-label for="duty_notes" :value="__('messages.notes')" />
                        <textarea wire:model="duty_notes" id="duty_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <x-input-error :messages="$errors->get('duty_notes')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2">
                        @if ($editingDutyId)
                            <x-secondary-button type="button" wire:click="cancelDutyEdit">{{ __('messages.cancel') }}</x-secondary-button>
                        @endif
                        <x-primary-button>{{ __('messages.save') }}</x-primary-button>
                    </div>
                </form>
            </section>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.calendar_events') }}</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('messages.event_title') }}</th>
                                <th class="px-5 py-3">{{ __('messages.date') }}</th>
                                <th class="px-5 py-3">{{ __('messages.context') }}</th>
                                <th class="px-5 py-3">{{ __('messages.status') }}</th>
                                <th class="px-5 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($events as $event)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-medium text-gray-950">{{ $event->title }}</p>
                                        @if ($event->is_important)
                                            <p class="mt-1 text-xs font-medium text-emerald-700">{{ __('messages.important_event') }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">
                                        <p>{{ $event->event_date?->translatedFormat('d M Y') }}</p>
                                        @if ($event->starts_at)
                                            <p class="text-xs text-gray-500">
                                                {{ substr((string) $event->starts_at, 0, 5) }}
                                                @if ($event->ends_at)
                                                    - {{ substr((string) $event->ends_at, 0, 5) }}
                                                @endif
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">
                                        {{ $event->department?->name ?: $event->zone?->name ?: __('messages.church_scope') }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <span @class([
                                            'rounded-full px-2 py-1 text-xs font-medium',
                                            'bg-emerald-50 text-emerald-700' => $event->is_active,
                                            'bg-gray-100 text-gray-600' => ! $event->is_active,
                                        ])>
                                            {{ $event->is_active ? __('messages.active') : __('messages.inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-3">
                                            <button wire:click="editEvent({{ $event->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">{{ __('messages.edit') }}</button>
                                            <button wire:click="toggleEventActive({{ $event->id }})" type="button" class="text-sm font-medium text-gray-700 hover:text-gray-950">
                                                {{ $event->is_active ? __('messages.deactivate') : __('messages.activate') }}
                                            </button>
                                            <button wire:click="deleteEvent({{ $event->id }})" wire:confirm="{{ __('messages.confirm_delete_event') }}" type="button" class="text-sm font-medium text-red-600 hover:text-red-800">{{ __('messages.delete') }}</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-gray-500">{{ __('messages.no_calendar_events') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.weekly_duties') }}</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('messages.date') }}</th>
                                <th class="px-5 py-3">{{ __('messages.elder') }}</th>
                                <th class="px-5 py-3">{{ __('messages.deacon') }}</th>
                                <th class="px-5 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($duties as $duty)
                                <tr>
                                    <td class="px-5 py-4 text-gray-600">
                                        <p>{{ $duty->week_start?->translatedFormat('d M') }} - {{ $duty->week_end?->translatedFormat('d M Y') }}</p>
                                        <span @class([
                                            'mt-1 inline-flex rounded-full px-2 py-1 text-xs font-medium',
                                            'bg-emerald-50 text-emerald-700' => $duty->is_active,
                                            'bg-gray-100 text-gray-600' => ! $duty->is_active,
                                        ])>
                                            {{ $duty->is_active ? __('messages.active') : __('messages.inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 font-medium text-gray-950">
                                        {{ $duty->elder ? trim($duty->elder->first_name.' '.$duty->elder->middle_name.' '.$duty->elder->last_name) : __('messages.not_assigned') }}
                                    </td>
                                    <td class="px-5 py-4 font-medium text-gray-950">
                                        {{ $duty->deacon ? trim($duty->deacon->first_name.' '.$duty->deacon->middle_name.' '.$duty->deacon->last_name) : __('messages.not_assigned') }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-3">
                                            <button wire:click="editDuty({{ $duty->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">{{ __('messages.edit') }}</button>
                                            <button wire:click="toggleDutyActive({{ $duty->id }})" type="button" class="text-sm font-medium text-gray-700 hover:text-gray-950">
                                                {{ $duty->is_active ? __('messages.deactivate') : __('messages.activate') }}
                                            </button>
                                            <button wire:click="deleteDuty({{ $duty->id }})" wire:confirm="{{ __('messages.confirm_delete_duty') }}" type="button" class="text-sm font-medium text-red-600 hover:text-red-800">{{ __('messages.delete') }}</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-gray-500">{{ __('messages.no_weekly_duties') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
