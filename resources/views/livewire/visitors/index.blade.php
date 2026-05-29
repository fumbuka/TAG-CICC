<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-red-700">{{ __('messages.visitor_care') }}</p>
                <h1 class="text-2xl font-semibold text-gray-950">{{ __('messages.visitors') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('messages.visitors_help') }}</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ __('messages.total_visitors') }}</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950">{{ number_format($totalVisitors) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ __('messages.pending_follow_ups') }}</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950">{{ number_format($pendingFollowUps) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ __('messages.converted_visitors') }}</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950">{{ number_format($convertedVisitors) }}</p>
            </div>
        </div>

        <div x-data="{ show: false, message: '' }"
            x-on:visitor-created.window="message = '{{ __('messages.visitor_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:visitor-updated.window="message = '{{ __('messages.visitor_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:visitor-deleted.window="message = '{{ __('messages.visitor_deleted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:visitor-converted.window="message = '{{ __('messages.visitor_converted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-show="show"
            x-cloak
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            x-text="message"></div>

        @error('conversion')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</div>
        @enderror

        <div class="grid gap-6 lg:grid-cols-[0.9fr_1.4fr]">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">
                    {{ $editingVisitorId ? __('messages.edit_visitor') : __('messages.register_visitor') }}
                </h2>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div>
                        <x-input-label for="full_name" :value="__('messages.full_name')" />
                        <x-text-input wire:model="full_name" id="full_name" class="mt-1 block w-full" type="text" />
                        <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="phone_number" :value="__('messages.phone')" />
                            <x-text-input wire:model="phone_number" id="phone_number" class="mt-1 block w-full" type="tel" />
                            <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="visited_at" :value="__('messages.visited_at')" />
                            <x-text-input wire:model="visited_at" id="visited_at" class="mt-1 block w-full" type="date" />
                            <x-input-error :messages="$errors->get('visited_at')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="residential_area" :value="__('messages.residential_area')" />
                        <x-text-input wire:model="residential_area" id="residential_area" class="mt-1 block w-full" type="text" />
                        <x-input-error :messages="$errors->get('residential_area')" class="mt-2" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="invited_by" :value="__('messages.invited_by')" />
                            <x-text-input wire:model="invited_by" id="invited_by" class="mt-1 block w-full" type="text" />
                            <x-input-error :messages="$errors->get('invited_by')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="follow_up_status" :value="__('messages.follow_up_status')" />
                            <select wire:model="follow_up_status" id="follow_up_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                @foreach ($statusOptions as $status)
                                    <option value="{{ $status }}">{{ __('messages.visitor_status_'.$status) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('follow_up_status')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="assigned_to_user_id" :value="__('messages.assigned_follow_up')" />
                        <select wire:model="assigned_to_user_id" id="assigned_to_user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                            <option value="">{{ __('messages.not_assigned') }}</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('assigned_to_user_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="notes" :value="__('messages.notes')" />
                        <textarea wire:model="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"></textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2">
                        @if ($editingVisitorId)
                            <x-secondary-button type="button" wire:click="cancelEdit">{{ __('messages.cancel') }}</x-secondary-button>
                        @endif
                        <x-primary-button>{{ __('messages.save') }}</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.visitors_list') }}</h2>
                        <x-text-input wire:model.live.debounce.300ms="search" class="w-full sm:w-72" type="search" :placeholder="__('messages.search_visitors')" />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('messages.name') }}</th>
                                <th class="px-5 py-3">{{ __('messages.contact') }}</th>
                                <th class="px-5 py-3">{{ __('messages.visited_at') }}</th>
                                <th class="px-5 py-3">{{ __('messages.follow_up_status') }}</th>
                                <th class="px-5 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($visitors as $visitor)
                                <tr>
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-gray-950">{{ $visitor->full_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $visitor->invited_by ? __('messages.invited_by').': '.$visitor->invited_by : '-' }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">
                                        <div>{{ $visitor->phone_number ?: '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ $visitor->residential_area ?: '-' }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ $visitor->visited_at?->format('Y-m-d') }}</td>
                                    <td class="px-5 py-4">
                                        <div class="space-y-1">
                                            <span @class([
                                                'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                                'bg-blue-50 text-blue-700' => $visitor->follow_up_status === 'new',
                                                'bg-amber-50 text-amber-700' => in_array($visitor->follow_up_status, ['follow_up', 'invited_to_membership'], true),
                                                'bg-emerald-50 text-emerald-700' => $visitor->follow_up_status === 'converted',
                                                'bg-gray-100 text-gray-600' => $visitor->follow_up_status === 'not_interested',
                                            ])>
                                                {{ __('messages.visitor_status_'.$visitor->follow_up_status) }}
                                            </span>
                                            <div class="text-xs text-gray-500">
                                                {{ __('messages.assigned_follow_up') }}: {{ $visitor->assignedTo?->name ?: __('messages.not_assigned') }}
                                            </div>
                                            @if ($visitor->convertedMember)
                                                <div class="text-xs font-medium text-emerald-700">
                                                    {{ __('messages.member') }}: {{ trim($visitor->convertedMember->first_name.' '.$visitor->convertedMember->middle_name.' '.$visitor->convertedMember->last_name) }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-3">
                                            <button wire:click="edit({{ $visitor->id }})" type="button" class="text-sm font-medium text-red-700 hover:text-red-900">
                                                {{ __('messages.edit') }}
                                            </button>
                                            @if (! $visitor->converted_member_id)
                                                <button wire:click="prepareConversion({{ $visitor->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
                                                    {{ __('messages.convert_to_member') }}
                                                </button>
                                            @endif
                                            <button wire:click="delete({{ $visitor->id }})" wire:confirm="{{ __('messages.confirm_delete_visitor') }}" type="button" class="text-sm font-medium text-red-600 hover:text-red-800">
                                                {{ __('messages.delete') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-gray-500">{{ __('messages.no_visitors') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $visitors->links() }}
                </div>
            </section>
        </div>

        @if ($conversionVisitorId)
            <section class="rounded-lg border border-red-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-1">
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.convert_visitor_to_member') }}</h2>
                    <p class="text-sm text-gray-600">{{ __('messages.convert_visitor_help') }}</p>
                </div>

                <form wire:submit="convertToMember" class="mt-5 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="convert_first_name" :value="__('messages.first_name')" />
                            <x-text-input wire:model="convert_first_name" id="convert_first_name" class="mt-1 block w-full" type="text" />
                            <x-input-error :messages="$errors->get('convert_first_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="convert_middle_name" :value="__('messages.middle_name')" />
                            <x-text-input wire:model="convert_middle_name" id="convert_middle_name" class="mt-1 block w-full" type="text" />
                            <x-input-error :messages="$errors->get('convert_middle_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="convert_last_name" :value="__('messages.last_name')" />
                            <x-text-input wire:model="convert_last_name" id="convert_last_name" class="mt-1 block w-full" type="text" />
                            <x-input-error :messages="$errors->get('convert_last_name')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-4">
                        <div>
                            <x-input-label for="convert_gender" :value="__('messages.gender')" />
                            <select wire:model="convert_gender" id="convert_gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                <option value="">{{ __('messages.select_gender') }}</option>
                                <option value="female">{{ __('messages.female') }}</option>
                                <option value="male">{{ __('messages.male') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('convert_gender')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="convert_date_of_birth" :value="__('messages.date_of_birth')" />
                            <x-text-input wire:model="convert_date_of_birth" id="convert_date_of_birth" class="mt-1 block w-full" type="date" />
                            <x-input-error :messages="$errors->get('convert_date_of_birth')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="convert_phone_number" :value="__('messages.phone')" />
                            <x-text-input wire:model="convert_phone_number" id="convert_phone_number" class="mt-1 block w-full" type="tel" />
                            <x-input-error :messages="$errors->get('convert_phone_number')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="convert_email" :value="__('messages.email')" />
                            <x-text-input wire:model="convert_email" id="convert_email" class="mt-1 block w-full" type="email" />
                            <x-input-error :messages="$errors->get('convert_email')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="convert_residential_area" :value="__('messages.residential_area')" />
                            <x-text-input wire:model="convert_residential_area" id="convert_residential_area" class="mt-1 block w-full" type="text" />
                            <x-input-error :messages="$errors->get('convert_residential_area')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="convert_zone_id" :value="__('messages.zone')" />
                            <select wire:model="convert_zone_id" id="convert_zone_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                <option value="">{{ __('messages.no_zone_selected') }}</option>
                                @foreach ($zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('convert_zone_id')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label :value="__('messages.manual_department_assignment')" />
                        <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($departments as $department)
                                <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700">
                                    <input wire:model="convert_department_ids" type="checkbox" value="{{ $department->id }}" class="rounded border-gray-300 text-red-700 focus:ring-red-600">
                                    <span>{{ $department->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('convert_department_ids')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <x-secondary-button type="button" wire:click="cancelConversion">{{ __('messages.cancel') }}</x-secondary-button>
                        <x-primary-button>{{ __('messages.convert_to_member') }}</x-primary-button>
                    </div>
                </form>
            </section>
        @endif
    </div>
</div>
