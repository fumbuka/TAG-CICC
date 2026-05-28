<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-medium text-emerald-700">{{ __('messages.users') }}</p>
            <h1 class="text-2xl font-semibold text-gray-950">{{ __('messages.users') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('messages.users_help') }}</p>
        </div>

        <div x-data="{ show: false, message: '' }"
            x-on:user-created.window="message = '{{ __('messages.user_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:user-updated.window="message = '{{ __('messages.user_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-show="show"
            x-cloak
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            x-text="message"></div>

        @error('user_action')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</div>
        @enderror

        <div class="grid gap-6 lg:grid-cols-[0.85fr_1.4fr]">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">
                    {{ $editingUserId ? __('messages.edit_user') : __('messages.add_user') }}
                </h2>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div>
                        <x-input-label for="name" :value="__('messages.name')" />
                        <x-text-input wire:model="name" id="name" class="mt-1 block w-full" type="text" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="email" :value="__('messages.email')" />
                            <x-text-input wire:model="email" id="email" class="mt-1 block w-full" type="email" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="phone_number" :value="__('messages.phone')" />
                            <x-text-input wire:model="phone_number" id="phone_number" class="mt-1 block w-full" type="tel" />
                            <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="password" :value="$editingUserId ? __('messages.new_password') : __('messages.password')" />
                        <x-text-input wire:model="password" id="password" class="mt-1 block w-full" type="password" autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="member_id" :value="__('messages.linked_member')" />
                        <select wire:model="member_id" id="member_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('messages.no_member_selected') }}</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}">
                                    {{ trim($member->first_name.' '.$member->middle_name.' '.$member->last_name) }}
                                    @if ($member->user)
                                        - {{ __('messages.account_linked') }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('member_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label :value="__('messages.roles')" />
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach ($roles as $role)
                                <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700">
                                    <input wire:model="role_names" type="checkbox" value="{{ $role->name }}" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600">
                                    <span>{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('role_names')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2">
                        @if ($editingUserId)
                            <x-secondary-button type="button" wire:click="cancelEdit">{{ __('messages.cancel') }}</x-secondary-button>
                        @endif
                        <x-primary-button>{{ __('messages.save') }}</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.users_list') }}</h2>
                        <x-text-input wire:model.live.debounce.300ms="search" class="w-full sm:w-72" type="search" :placeholder="__('messages.search_users')" />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('messages.name') }}</th>
                                <th class="px-5 py-3">{{ __('messages.contact') }}</th>
                                <th class="px-5 py-3">{{ __('messages.roles') }}</th>
                                <th class="px-5 py-3">{{ __('messages.status') }}</th>
                                <th class="px-5 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($users as $user)
                                <tr>
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-gray-950">{{ $user->name }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ $user->member ? trim($user->member->first_name.' '.$user->member->middle_name.' '.$user->member->last_name) : __('messages.no_member_selected') }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">
                                        <div>{{ $user->email }}</div>
                                        <div class="text-xs text-gray-500">{{ $user->phone_number ?: '-' }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</td>
                                    <td class="px-5 py-4">
                                        <span @class([
                                            'rounded-full px-2 py-1 text-xs font-medium',
                                            'bg-emerald-50 text-emerald-700' => $user->is_active,
                                            'bg-gray-100 text-gray-600' => ! $user->is_active,
                                        ])>
                                            {{ $user->is_active ? __('messages.active') : __('messages.inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-3">
                                            <button wire:click="edit({{ $user->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
                                                {{ __('messages.edit') }}
                                            </button>
                                            <button wire:click="toggleActive({{ $user->id }})" type="button" class="text-sm font-medium text-gray-700 hover:text-gray-950">
                                                {{ $user->is_active ? __('messages.deactivate') : __('messages.activate') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-gray-500">{{ __('messages.no_users') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $users->links() }}
                </div>
            </section>
        </div>
    </div>
</div>
