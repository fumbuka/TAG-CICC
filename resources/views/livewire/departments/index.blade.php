<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-medium text-emerald-700">{{ __('messages.departments') }}</p>
            <h1 class="text-2xl font-semibold text-gray-950">{{ __('messages.departments') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('messages.department_help') }}</p>
        </div>

        <div x-data="{ show: false, message: '' }"
            x-on:department-created.window="message = '{{ __('messages.department_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:department-updated.window="message = '{{ __('messages.department_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:department-deleted.window="message = '{{ __('messages.department_deleted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:departments-imported.window="message = '{{ __('messages.departments_imported') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-show="show"
            x-cloak
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            x-text="message"></div>

        @error('delete')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</div>
        @enderror

        <div class="grid gap-6 lg:grid-cols-[0.8fr_1.4fr]">
            <section id="department-form" class="scroll-mt-24 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">
                    {{ $editingDepartmentId ? __('messages.edit_department') : __('messages.add_department') }}
                </h2>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div>
                        <x-input-label for="name" :value="__('messages.department_name')" />
                        <x-text-input wire:model="name" id="name" class="mt-1 block w-full" type="text" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('messages.description')" />
                        <textarea wire:model="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input wire:model="is_age_based" type="checkbox" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600">
                        <span>{{ __('messages.age_rule') }}</span>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="minimum_age" :value="__('messages.minimum_age')" />
                            <x-text-input wire:model="minimum_age" id="minimum_age" class="mt-1 block w-full" type="number" min="0" />
                            <x-input-error :messages="$errors->get('minimum_age')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="maximum_age" :value="__('messages.maximum_age')" />
                            <x-text-input wire:model="maximum_age" id="maximum_age" class="mt-1 block w-full" type="number" min="0" />
                            <x-input-error :messages="$errors->get('maximum_age')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="gender_rule" :value="__('messages.gender_rule')" />
                        <select wire:model="gender_rule" id="gender_rule" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('messages.no_gender_rule') }}</option>
                            <option value="female">{{ __('messages.female') }}</option>
                            <option value="male">{{ __('messages.male') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('gender_rule')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2">
                        @if ($editingDepartmentId)
                            <x-secondary-button type="button" wire:click="cancelEdit">{{ __('messages.cancel') }}</x-secondary-button>
                        @endif
                        <x-primary-button>{{ __('messages.save') }}</x-primary-button>
                    </div>
                </form>
            </section>

            <section id="departments-list" class="scroll-mt-24 rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.existing_departments') }}</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('messages.name') }}</th>
                                <th class="px-5 py-3">{{ __('messages.rule') }}</th>
                                <th class="px-5 py-3">{{ __('messages.members') }}</th>
                                <th class="px-5 py-3">{{ __('messages.status') }}</th>
                                <th class="px-5 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($departments as $department)
                                <tr>
                                    <td class="px-5 py-4 font-medium text-gray-950">{{ $department->name }}</td>
                                    <td class="px-5 py-4 text-gray-600">
                                        @if ($department->minimum_age || $department->maximum_age || $department->gender_rule)
                                            {{ $department->minimum_age ?? '0' }}-{{ $department->maximum_age ?? '120' }}
                                            {{ $department->gender_rule ? ', '.__('messages.'.$department->gender_rule) : '' }}
                                        @else
                                            {{ __('messages.manual') }}
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ $department->members_count }}</td>
                                    <td class="px-5 py-4">
                                        <span @class([
                                            'rounded-full px-2 py-1 text-xs font-medium',
                                            'bg-emerald-50 text-emerald-700' => $department->is_active,
                                            'bg-gray-100 text-gray-600' => ! $department->is_active,
                                        ])>
                                            {{ $department->is_active ? __('messages.active') : __('messages.inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-3">
                                            <button wire:click="edit({{ $department->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
                                                {{ __('messages.edit') }}
                                            </button>
                                            <button wire:click="toggleActive({{ $department->id }})" type="button" class="text-sm font-medium text-gray-700 hover:text-gray-950">
                                                {{ $department->is_active ? __('messages.deactivate') : __('messages.activate') }}
                                            </button>
                                            <button wire:click="delete({{ $department->id }})" wire:confirm="{{ __('messages.confirm_delete_department') }}" type="button" class="text-sm font-medium text-red-600 hover:text-red-800">
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

        <section
            id="bulk-import-departments"
            x-data="{ uploading: false, progress: 0 }"
            x-on:livewire-upload-start="uploading = true; progress = 0"
            x-on:livewire-upload-progress="progress = $event.detail.progress"
            x-on:livewire-upload-finish="progress = 100; uploading = false"
            x-on:livewire-upload-error="uploading = false"
            class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.bulk_import_departments') }}</h2>
                    <p class="mt-1 text-sm text-gray-600">{{ __('messages.departments_import_help') }}</p>
                </div>
                <a href="{{ route('bulk-import-templates.download', 'departments') }}" class="inline-flex items-center justify-center rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                    {{ __('messages.download_template') }}
                </a>
            </div>

            <form wire:submit="import" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input-label for="departmentImport" :value="__('messages.import_file')" />
                    <input wire:model="departmentImport" id="departmentImport" type="file" accept=".csv,.txt,.xlsx,.ods" class="mt-1 block w-full text-sm text-gray-700 file:me-4 file:rounded-md file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100" />
                    <x-input-error :messages="$errors->get('departmentImport')" class="mt-2" />
                </div>
                <x-primary-button wire:loading.attr="disabled" wire:target="departmentImport,import">
                    <span wire:loading.remove wire:target="departmentImport,import">{{ __('messages.upload') }}</span>
                    <span wire:loading wire:target="departmentImport,import">{{ __('messages.uploading') }}</span>
                </x-primary-button>
            </form>

            <x-import-upload-status target="import" />
            <x-import-report :report="$importReport" />
        </section>
    </div>
</div>
