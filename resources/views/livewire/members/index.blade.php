<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">Membership</p>
                <h1 class="text-2xl font-semibold text-gray-950">Washirika</h1>
                <p class="mt-1 text-sm text-gray-600">Sajili mshirika, mpangie zone, na mfumo utampa idara ya msingi kwa umri na jinsia.</p>
            </div>
        </div>

        <div x-data="{ show: false }" x-on:member-created.window="show = true; setTimeout(() => show = false, 3500)" x-show="show" x-cloak class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            Mshirika amesajiliwa kikamilifu.
        </div>

        <div class="grid gap-6 lg:grid-cols-[0.95fr_1.4fr]">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">Sajili mshirika</h2>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="first_name" value="First name" />
                            <x-text-input wire:model="first_name" id="first_name" class="mt-1 block w-full" type="text" />
                            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="last_name" value="Last name" />
                            <x-text-input wire:model="last_name" id="last_name" class="mt-1 block w-full" type="text" />
                            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="middle_name" value="Middle name" />
                        <x-text-input wire:model="middle_name" id="middle_name" class="mt-1 block w-full" type="text" />
                        <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="gender" value="Gender" />
                            <select wire:model="gender" id="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select gender</option>
                                <option value="female">Mwanamke</option>
                                <option value="male">Mwanaume</option>
                            </select>
                            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="date_of_birth" value="Date of birth" />
                            <x-text-input wire:model="date_of_birth" id="date_of_birth" class="mt-1 block w-full" type="date" />
                            <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="phone_number" value="Phone number" />
                            <x-text-input wire:model="phone_number" id="phone_number" class="mt-1 block w-full" type="tel" />
                            <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" value="Email" />
                            <x-text-input wire:model="email" id="email" class="mt-1 block w-full" type="email" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="residential_area" value="Residential area" />
                            <x-text-input wire:model="residential_area" id="residential_area" class="mt-1 block w-full" type="text" />
                            <x-input-error :messages="$errors->get('residential_area')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="zone_id" value="Zone" />
                            <select wire:model="zone_id" id="zone_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">No zone selected</option>
                                @foreach ($zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('zone_id')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Manual department assignment" />
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

                    <div class="flex justify-end">
                        <x-primary-button>Sajili mshirika</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-lg font-semibold text-gray-950">Orodha ya washirika</h2>
                        <x-text-input wire:model.live.debounce.300ms="search" class="w-full sm:w-72" type="search" placeholder="Search members" />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Name</th>
                                <th class="px-5 py-3">Phone</th>
                                <th class="px-5 py-3">Zone</th>
                                <th class="px-5 py-3">Departments</th>
                                <th class="px-5 py-3">Status</th>
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
                                            {{ ucfirst($member->membership_status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-gray-500">Hakuna mshirika aliyesajiliwa bado.</td>
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
    </div>
</div>
