<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-medium text-emerald-700">{{ __('messages.leadership') }}</p>
            <h1 class="text-2xl font-semibold text-gray-950">{{ __('messages.leadership') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('messages.leadership_help') }}</p>
        </div>

        <div x-data="{ show: false, message: '' }"
            x-on:title-created.window="message = '{{ __('messages.leadership_title_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:title-updated.window="message = '{{ __('messages.leadership_title_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:title-deleted.window="message = '{{ __('messages.leadership_title_deleted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:assignment-created.window="message = '{{ __('messages.leadership_assignment_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:assignment-updated.window="message = '{{ __('messages.leadership_assignment_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:assignment-deleted.window="message = '{{ __('messages.leadership_assignment_deleted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-show="show"
            x-cloak
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            x-text="message"></div>

        @error('title_delete')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</div>
        @enderror

        @if ($accessCredentials)
            <section class="rounded-lg border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-amber-950">
                            {{ $accessCredentials['created'] ? __('messages.leader_access_created') : __('messages.leader_access_updated') }}
                        </h2>
                        <p class="mt-1 text-sm text-amber-800">
                            {{ $accessCredentials['created'] ? __('messages.leader_access_created_help') : __('messages.leader_access_updated_help') }}
                        </p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-800 shadow-sm">{{ $accessCredentials['role_name'] }}</span>
                </div>

                <dl @class([
                    'mt-4 grid gap-3 text-sm',
                    'md:grid-cols-4' => $accessCredentials['password'],
                    'md:grid-cols-3' => ! $accessCredentials['password'],
                ])>
                    <div class="rounded-md bg-white p-3 shadow-sm">
                        <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('messages.name') }}</dt>
                        <dd class="mt-1 font-medium text-gray-950">{{ $accessCredentials['name'] }}</dd>
                    </div>
                    <div class="rounded-md bg-white p-3 shadow-sm">
                        <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('messages.email') }}</dt>
                        <dd class="mt-1 break-all font-mono text-gray-950">{{ $accessCredentials['email'] }}</dd>
                    </div>
                    <div class="rounded-md bg-white p-3 shadow-sm">
                        <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('messages.phone') }}</dt>
                        <dd class="mt-1 font-mono text-gray-950">{{ $accessCredentials['phone_number'] ?: '-' }}</dd>
                    </div>
                    @if ($accessCredentials['password'])
                        <div class="rounded-md bg-white p-3 shadow-sm">
                            <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('messages.temporary_password') }}</dt>
                            <dd class="mt-1 font-mono font-semibold text-gray-950">{{ $accessCredentials['password'] }}</dd>
                        </div>
                    @endif
                </dl>
            </section>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">
                    {{ $editingTitleId ? __('messages.edit_leadership_title') : __('messages.add_leadership_title') }}
                </h2>

                <form wire:submit="saveTitle" class="mt-5 space-y-4">
                    <div>
                        <x-input-label for="title_name" :value="__('messages.title_name')" />
                        <x-text-input wire:model="title_name" id="title_name" class="mt-1 block w-full" type="text" />
                        <x-input-error :messages="$errors->get('title_name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="title_scope" :value="__('messages.title_scope')" />
                        <select wire:model="title_scope" id="title_scope" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="church">{{ __('messages.church_scope') }}</option>
                            <option value="department">{{ __('messages.department_scope') }}</option>
                            <option value="zone">{{ __('messages.zone_scope') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('title_scope')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="title_description" :value="__('messages.description')" />
                        <textarea wire:model="title_description" id="title_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <x-input-error :messages="$errors->get('title_description')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2">
                        @if ($editingTitleId)
                            <x-secondary-button type="button" wire:click="cancelTitleEdit">{{ __('messages.cancel') }}</x-secondary-button>
                        @endif
                        <x-primary-button>{{ __('messages.save') }}</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">
                    {{ $editingAssignmentId ? __('messages.edit_leadership_assignment') : __('messages.assign_leadership') }}
                </h2>

                <form wire:submit="saveAssignment" class="mt-5 space-y-4">
                    <div>
                        <x-input-label for="member_id" :value="__('messages.member')" />
                        <select wire:model="member_id" id="member_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('messages.select_member') }}</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}">{{ trim($member->first_name.' '.$member->middle_name.' '.$member->last_name) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('member_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="leadership_title_id" :value="__('messages.leadership_title')" />
                        <select wire:model="leadership_title_id" id="leadership_title_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('messages.select_leadership_title') }}</option>
                            @foreach ($titles->where('is_active', true) as $title)
                                <option value="{{ $title->id }}">{{ $title->name }} - {{ __('messages.'.$title->scope.'_scope') }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('leadership_title_id')" class="mt-2" />
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
                            <x-input-label for="started_at" :value="__('messages.started_at')" />
                            <x-text-input wire:model="started_at" id="started_at" class="mt-1 block w-full" type="date" />
                            <x-input-error :messages="$errors->get('started_at')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="ended_at" :value="__('messages.ended_at')" />
                            <x-text-input wire:model="ended_at" id="ended_at" class="mt-1 block w-full" type="date" />
                            <x-input-error :messages="$errors->get('ended_at')" class="mt-2" />
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input wire:model="assignment_is_active" type="checkbox" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600">
                        <span>{{ __('messages.active') }}</span>
                    </label>

                    <div>
                        <x-input-label for="assignment_notes" :value="__('messages.notes')" />
                        <textarea wire:model="assignment_notes" id="assignment_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <x-input-error :messages="$errors->get('assignment_notes')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2">
                        @if ($editingAssignmentId)
                            <x-secondary-button type="button" wire:click="cancelAssignmentEdit">{{ __('messages.cancel') }}</x-secondary-button>
                        @endif
                        <x-primary-button>{{ __('messages.save') }}</x-primary-button>
                    </div>
                </form>
            </section>
        </div>

        <div class="grid gap-6 lg:grid-cols-[0.85fr_1.15fr]">
            <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.leadership_titles') }}</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('messages.name') }}</th>
                                <th class="px-5 py-3">{{ __('messages.title_scope') }}</th>
                                <th class="px-5 py-3">{{ __('messages.status') }}</th>
                                <th class="px-5 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($titles as $title)
                                <tr>
                                    <td class="px-5 py-4 font-medium text-gray-950">{{ $title->name }}</td>
                                    <td class="px-5 py-4 text-gray-600">{{ __('messages.'.$title->scope.'_scope') }}</td>
                                    <td class="px-5 py-4">
                                        <span @class([
                                            'rounded-full px-2 py-1 text-xs font-medium',
                                            'bg-emerald-50 text-emerald-700' => $title->is_active,
                                            'bg-gray-100 text-gray-600' => ! $title->is_active,
                                        ])>
                                            {{ $title->is_active ? __('messages.active') : __('messages.inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-3">
                                            <button wire:click="editTitle({{ $title->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">{{ __('messages.edit') }}</button>
                                            <button wire:click="toggleTitleActive({{ $title->id }})" type="button" class="text-sm font-medium text-gray-700 hover:text-gray-950">
                                                {{ $title->is_active ? __('messages.deactivate') : __('messages.activate') }}
                                            </button>
                                            <button wire:click="deleteTitle({{ $title->id }})" wire:confirm="{{ __('messages.confirm_delete_leadership_title') }}" type="button" class="text-sm font-medium text-red-600 hover:text-red-800">{{ __('messages.delete') }}</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.leadership_assignments') }}</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('messages.member') }}</th>
                                <th class="px-5 py-3">{{ __('messages.leadership_title') }}</th>
                                <th class="px-5 py-3">{{ __('messages.context') }}</th>
                                <th class="px-5 py-3">{{ __('messages.status') }}</th>
                                <th class="px-5 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($assignments as $assignment)
                                <tr>
                                    <td class="px-5 py-4 font-medium text-gray-950">
                                        {{ trim($assignment->member->first_name.' '.$assignment->member->middle_name.' '.$assignment->member->last_name) }}
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ $assignment->leadershipTitle?->name ?: '-' }}</td>
                                    <td class="px-5 py-4 text-gray-600">
                                        {{ $assignment->department?->name ?: $assignment->zone?->name ?: __('messages.church_scope') }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <span @class([
                                            'rounded-full px-2 py-1 text-xs font-medium',
                                            'bg-emerald-50 text-emerald-700' => $assignment->is_active,
                                            'bg-gray-100 text-gray-600' => ! $assignment->is_active,
                                        ])>
                                            {{ $assignment->is_active ? __('messages.active') : __('messages.inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-3">
                                            <button wire:click="editAssignment({{ $assignment->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">{{ __('messages.edit') }}</button>
                                            <button wire:click="deleteAssignment({{ $assignment->id }})" wire:confirm="{{ __('messages.confirm_delete_leadership_assignment') }}" type="button" class="text-sm font-medium text-red-600 hover:text-red-800">{{ __('messages.delete') }}</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-gray-500">{{ __('messages.no_leadership_assignments') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
