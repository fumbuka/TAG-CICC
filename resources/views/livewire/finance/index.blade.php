<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('messages.finance') }}</p>
                <h1 class="text-2xl font-semibold text-gray-950">{{ __('messages.finance') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('messages.finance_form_help') }}</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ __('messages.today_total') }}</p>
                <p class="mt-2 text-2xl font-semibold text-gray-950">{{ __('messages.currency_tzs') }} {{ number_format((float) $todayTotal, 2) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ __('messages.month_total') }}</p>
                <p class="mt-2 text-2xl font-semibold text-gray-950">{{ __('messages.currency_tzs') }} {{ number_format((float) $monthTotal, 2) }}</p>
            </div>
        </div>

        <div x-data="{ show: false, message: '' }"
            x-on:transaction-created.window="message = '{{ __('messages.transaction_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:transaction-updated.window="message = '{{ __('messages.transaction_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:transaction-deleted.window="message = '{{ __('messages.transaction_deleted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-show="show"
            x-cloak
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            x-text="message"></div>

        <div class="grid gap-6 lg:grid-cols-[0.95fr_1.4fr]">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">
                    {{ $editingTransactionId ? __('messages.edit_transaction') : __('messages.record_transaction') }}
                </h2>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="income_category_id" :value="__('messages.income_category')" />
                            <select wire:model="income_category_id" id="income_category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('messages.select_income_category') }}</option>
                                @foreach ($incomeCategories as $incomeCategory)
                                    <option value="{{ $incomeCategory->id }}">{{ $incomeCategory->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('income_category_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="amount" :value="__('messages.amount')" />
                            <x-text-input wire:model="amount" id="amount" class="mt-1 block w-full" type="number" step="0.01" min="0" />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="transaction_date" :value="__('messages.transaction_date')" />
                            <x-text-input wire:model="transaction_date" id="transaction_date" class="mt-1 block w-full" type="date" />
                            <x-input-error :messages="$errors->get('transaction_date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="reference_number" :value="__('messages.reference_number')" />
                            <x-text-input wire:model="reference_number" id="reference_number" class="mt-1 block w-full" type="text" />
                            <x-input-error :messages="$errors->get('reference_number')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="service_id" :value="__('messages.related_service')" />
                        <select wire:model="service_id" id="service_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('messages.no_service_selected') }}</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}">{{ $service->service_date?->format('Y-m-d') }} - {{ $service->title }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('service_id')" class="mt-2" />
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
                        <x-input-label for="notes" :value="__('messages.notes')" />
                        <textarea wire:model="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2">
                        @if ($editingTransactionId)
                            <x-secondary-button type="button" wire:click="cancelEdit">{{ __('messages.cancel') }}</x-secondary-button>
                        @endif
                        <x-primary-button>{{ __('messages.save') }}</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.transactions_list') }}</h2>
                        <x-text-input wire:model.live.debounce.300ms="search" class="w-full sm:w-72" type="search" :placeholder="__('messages.search_transactions')" />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('messages.date') }}</th>
                                <th class="px-5 py-3">{{ __('messages.income_category') }}</th>
                                <th class="px-5 py-3">{{ __('messages.context') }}</th>
                                <th class="px-5 py-3">{{ __('messages.amount') }}</th>
                                <th class="px-5 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td class="px-5 py-4 text-gray-600">
                                        <div>{{ $transaction->transaction_date?->format('Y-m-d') }}</div>
                                        <div class="text-xs text-gray-500">{{ $transaction->reference_number ?: '-' }}</div>
                                    </td>
                                    <td class="px-5 py-4 font-medium text-gray-950">{{ $transaction->incomeCategory?->name ?: '-' }}</td>
                                    <td class="px-5 py-4 text-gray-600">
                                        <div>{{ $transaction->service?->title ?: '-' }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ $transaction->department?->name ?: '-' }} / {{ $transaction->zone?->name ?: '-' }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 font-medium text-gray-950">
                                        {{ __('messages.currency_tzs') }} {{ number_format((float) $transaction->amount, 2) }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-3">
                                            <button wire:click="edit({{ $transaction->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
                                                {{ __('messages.edit') }}
                                            </button>
                                            <button wire:click="delete({{ $transaction->id }})" wire:confirm="{{ __('messages.confirm_delete_transaction') }}" type="button" class="text-sm font-medium text-red-600 hover:text-red-800">
                                                {{ __('messages.delete') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-gray-500">{{ __('messages.no_transactions') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $transactions->links() }}
                </div>
            </section>
        </div>
    </div>
</div>
