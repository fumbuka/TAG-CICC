<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('messages.finance') }}</p>
                <h1 class="text-2xl font-semibold text-gray-950">{{ __('messages.finance') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('messages.finance_form_help') }}</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ __('messages.today_total') }}</p>
                <p class="mt-2 text-2xl font-semibold text-gray-950">{{ __('messages.currency_tzs') }} {{ number_format((float) $todayTotal, 2) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ __('messages.month_total') }}</p>
                <p class="mt-2 text-2xl font-semibold text-gray-950">{{ __('messages.currency_tzs') }} {{ number_format((float) $monthTotal, 2) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ __('messages.total_pledged') }}</p>
                <p class="mt-2 text-2xl font-semibold text-gray-950">{{ __('messages.currency_tzs') }} {{ number_format((float) $totalPledged, 2) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ __('messages.pledge_balance') }}</p>
                <p class="mt-2 text-2xl font-semibold text-gray-950">{{ __('messages.currency_tzs') }} {{ number_format((float) $totalPledgeBalance, 2) }}</p>
            </div>
        </div>

        <div x-data="{ show: false, message: '' }"
            x-on:transaction-created.window="message = '{{ __('messages.transaction_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:transaction-updated.window="message = '{{ __('messages.transaction_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:transaction-deleted.window="message = '{{ __('messages.transaction_deleted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:category-created.window="message = '{{ __('messages.income_category_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:category-updated.window="message = '{{ __('messages.income_category_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:category-deleted.window="message = '{{ __('messages.income_category_deleted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:pledge-created.window="message = '{{ __('messages.pledge_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:pledge-updated.window="message = '{{ __('messages.pledge_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:pledge-deleted.window="message = '{{ __('messages.pledge_deleted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:pledge-payment-created.window="message = '{{ __('messages.pledge_payment_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:pledge-payment-deleted.window="message = '{{ __('messages.pledge_payment_deleted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-show="show"
            x-cloak
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            x-text="message"></div>

        @error('category_action')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</div>
        @enderror

        @error('pledge_action')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</div>
        @enderror

        <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 p-5">
                <div class="flex flex-col gap-1">
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.manage_income_categories') }}</h2>
                    <p class="text-sm text-gray-600">{{ __('messages.income_category_help') }}</p>
                </div>
            </div>

            <div class="grid gap-6 p-5 lg:grid-cols-[0.85fr_1.25fr]">
                <form wire:submit="saveCategory" class="space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                        {{ $editingCategoryId ? __('messages.edit_income_category') : __('messages.add_income_category') }}
                    </h3>

                    <div>
                        <x-input-label for="category_name" :value="__('messages.income_category_name')" />
                        <x-text-input wire:model="category_name" id="category_name" class="mt-1 block w-full" type="text" />
                        <x-input-error :messages="$errors->get('category_name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="category_description" :value="__('messages.description')" />
                        <textarea wire:model="category_description" id="category_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <x-input-error :messages="$errors->get('category_description')" class="mt-2" />
                    </div>

                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input wire:model="category_is_active" type="checkbox" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                        <span>{{ __('messages.active') }}</span>
                    </label>

                    <div class="flex justify-end gap-2">
                        @if ($editingCategoryId)
                            <x-secondary-button type="button" wire:click="cancelCategoryEdit">{{ __('messages.cancel') }}</x-secondary-button>
                        @endif
                        <x-primary-button>{{ __('messages.save') }}</x-primary-button>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('messages.income_category') }}</th>
                                <th class="px-4 py-3">{{ __('messages.status') }}</th>
                                <th class="px-4 py-3">{{ __('messages.records') }}</th>
                                <th class="px-4 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($allIncomeCategories as $incomeCategory)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-950">{{ $incomeCategory->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $incomeCategory->description ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span @class([
                                            'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                            'bg-emerald-50 text-emerald-700' => $incomeCategory->is_active,
                                            'bg-gray-100 text-gray-600' => ! $incomeCategory->is_active,
                                        ])>
                                            {{ $incomeCategory->is_active ? __('messages.active') : __('messages.inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        <div>{{ __('messages.transactions') }}: {{ $incomeCategory->financial_transactions_count }}</div>
                                        <div>{{ __('messages.pledges') }}: {{ $incomeCategory->pledges_count }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-3">
                                            <button wire:click="editCategory({{ $incomeCategory->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
                                                {{ __('messages.edit') }}
                                            </button>
                                            <button wire:click="toggleCategoryActive({{ $incomeCategory->id }})" type="button" class="text-sm font-medium text-gray-700 hover:text-gray-950">
                                                {{ $incomeCategory->is_active ? __('messages.deactivate') : __('messages.activate') }}
                                            </button>
                                            <button wire:click="deleteCategory({{ $incomeCategory->id }})" wire:confirm="{{ __('messages.confirm_delete_income_category') }}" type="button" class="text-sm font-medium text-red-600 hover:text-red-800">
                                                {{ __('messages.delete') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_income_categories') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 p-5">
                <div class="flex flex-col gap-1">
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.pledges') }}</h2>
                    <p class="text-sm text-gray-600">{{ __('messages.pledge_form_help') }}</p>
                </div>
            </div>

            <div class="grid gap-6 p-5 lg:grid-cols-2">
                <form wire:submit="savePledge" class="space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                        {{ $editingPledgeId ? __('messages.edit_pledge') : __('messages.add_pledge') }}
                    </h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="pledge_member_id" :value="__('messages.pledge_member')" />
                            <select wire:model="pledge_member_id" id="pledge_member_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('messages.no_member_selected') }}</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}">{{ trim($member->first_name.' '.$member->middle_name.' '.$member->last_name) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('pledge_member_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="donor_name" :value="__('messages.donor_name')" />
                            <x-text-input wire:model="donor_name" id="donor_name" class="mt-1 block w-full" type="text" />
                            <x-input-error :messages="$errors->get('donor_name')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="pledge_income_category_id" :value="__('messages.income_category')" />
                            <select wire:model="pledge_income_category_id" id="pledge_income_category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('messages.select_income_category') }}</option>
                                @foreach ($incomeCategories as $incomeCategory)
                                    <option value="{{ $incomeCategory->id }}">{{ $incomeCategory->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('pledge_income_category_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="pledged_amount" :value="__('messages.pledged_amount')" />
                            <x-text-input wire:model="pledged_amount" id="pledged_amount" class="mt-1 block w-full" type="number" step="0.01" min="0" />
                            <x-input-error :messages="$errors->get('pledged_amount')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="pledged_at" :value="__('messages.pledged_at')" />
                            <x-text-input wire:model="pledged_at" id="pledged_at" class="mt-1 block w-full" type="date" />
                            <x-input-error :messages="$errors->get('pledged_at')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="due_date" :value="__('messages.due_date')" />
                            <x-text-input wire:model="due_date" id="due_date" class="mt-1 block w-full" type="date" />
                            <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="pledge_status" :value="__('messages.status')" />
                            <select wire:model="pledge_status" id="pledge_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="active">{{ __('messages.pledge_status_active') }}</option>
                                <option value="completed">{{ __('messages.pledge_status_completed') }}</option>
                                <option value="cancelled">{{ __('messages.pledge_status_cancelled') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('pledge_status')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="pledge_service_id" :value="__('messages.related_service')" />
                            <select wire:model="pledge_service_id" id="pledge_service_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('messages.no_service_selected') }}</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->service_date?->format('Y-m-d') }} - {{ $service->title }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('pledge_service_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="pledge_department_id" :value="__('messages.department')" />
                            <select wire:model="pledge_department_id" id="pledge_department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('messages.no_department_selected') }}</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('pledge_department_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="pledge_zone_id" :value="__('messages.zone')" />
                            <select wire:model="pledge_zone_id" id="pledge_zone_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('messages.no_zone_selected') }}</option>
                                @foreach ($zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('pledge_zone_id')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="pledge_notes" :value="__('messages.notes')" />
                        <textarea wire:model="pledge_notes" id="pledge_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <x-input-error :messages="$errors->get('pledge_notes')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2">
                        @if ($editingPledgeId)
                            <x-secondary-button type="button" wire:click="cancelPledgeEdit">{{ __('messages.cancel') }}</x-secondary-button>
                        @endif
                        <x-primary-button>{{ __('messages.save') }}</x-primary-button>
                    </div>
                </form>

                <form wire:submit="recordPledgePayment" class="space-y-4 rounded-lg border border-gray-200 p-4">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.record_pledge_payment') }}</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ __('messages.pledge_payment_help') }}</p>
                    </div>

                    <div>
                        <x-input-label for="payment_pledge_id" :value="__('messages.pledge')" />
                        <select wire:model="payment_pledge_id" id="payment_pledge_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('messages.select_pledge') }}</option>
                            @foreach ($pledges->where('status', '!=', 'cancelled') as $pledge)
                                @php
                                    $paid = (float) ($pledge->payments_sum_amount ?? 0);
                                    $balance = max((float) $pledge->pledged_amount - $paid, 0);
                                    $donor = $pledge->member
                                        ? trim($pledge->member->first_name.' '.$pledge->member->middle_name.' '.$pledge->member->last_name)
                                        : $pledge->donor_name;
                                @endphp
                                @if ($balance > 0)
                                    <option value="{{ $pledge->id }}">{{ $donor }} - {{ $pledge->incomeCategory?->name }} - {{ __('messages.balance') }} {{ __('messages.currency_tzs') }} {{ number_format($balance, 2) }}</option>
                                @endif
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('payment_pledge_id')" class="mt-2" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="payment_amount" :value="__('messages.payment_amount')" />
                            <x-text-input wire:model="payment_amount" id="payment_amount" class="mt-1 block w-full" type="number" step="0.01" min="0" />
                            <x-input-error :messages="$errors->get('payment_amount')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="payment_date" :value="__('messages.payment_date')" />
                            <x-text-input wire:model="payment_date" id="payment_date" class="mt-1 block w-full" type="date" />
                            <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="payment_reference_number" :value="__('messages.payment_reference_number')" />
                        <x-text-input wire:model="payment_reference_number" id="payment_reference_number" class="mt-1 block w-full" type="text" />
                        <x-input-error :messages="$errors->get('payment_reference_number')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="payment_notes" :value="__('messages.notes')" />
                        <textarea wire:model="payment_notes" id="payment_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <x-input-error :messages="$errors->get('payment_notes')" class="mt-2" />
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('messages.save') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="border-t border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('messages.pledge') }}</th>
                                <th class="px-5 py-3">{{ __('messages.income_category') }}</th>
                                <th class="px-5 py-3">{{ __('messages.pledged_amount') }}</th>
                                <th class="px-5 py-3">{{ __('messages.paid_amount') }}</th>
                                <th class="px-5 py-3">{{ __('messages.balance') }}</th>
                                <th class="px-5 py-3">{{ __('messages.status') }}</th>
                                <th class="px-5 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($pledges as $pledge)
                                @php
                                    $paid = (float) ($pledge->payments_sum_amount ?? 0);
                                    $balance = max((float) $pledge->pledged_amount - $paid, 0);
                                    $progress = (float) $pledge->pledged_amount > 0 ? min((int) round(($paid / (float) $pledge->pledged_amount) * 100), 100) : 0;
                                    $donor = $pledge->member
                                        ? trim($pledge->member->first_name.' '.$pledge->member->middle_name.' '.$pledge->member->last_name)
                                        : $pledge->donor_name;
                                @endphp
                                <tr>
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-gray-950">{{ $donor }}</div>
                                        <div class="text-xs text-gray-500">{{ $pledge->pledged_at?->format('Y-m-d') }} {{ $pledge->due_date ? '- '.$pledge->due_date->format('Y-m-d') : '' }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">
                                        <div>{{ $pledge->incomeCategory?->name ?: '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ $pledge->department?->name ?: '-' }} / {{ $pledge->zone?->name ?: '-' }}</div>
                                    </td>
                                    <td class="px-5 py-4 font-medium text-gray-950">{{ __('messages.currency_tzs') }} {{ number_format((float) $pledge->pledged_amount, 2) }}</td>
                                    <td class="px-5 py-4 text-gray-600">{{ __('messages.currency_tzs') }} {{ number_format($paid, 2) }}</td>
                                    <td class="px-5 py-4 text-gray-600">{{ __('messages.currency_tzs') }} {{ number_format($balance, 2) }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex min-w-28 flex-col gap-2">
                                            <span @class([
                                                'inline-flex w-fit rounded-full px-2 py-1 text-xs font-semibold',
                                                'bg-emerald-50 text-emerald-700' => $pledge->status === 'completed',
                                                'bg-amber-50 text-amber-700' => $pledge->status === 'active',
                                                'bg-gray-100 text-gray-600' => $pledge->status === 'cancelled',
                                            ])>
                                                {{ __('messages.pledge_status_'.$pledge->status) }}
                                            </span>
                                            <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                                                <div class="h-full rounded-full bg-emerald-600" style="width: {{ $progress }}%"></div>
                                            </div>
                                            <div class="text-xs text-gray-500">{{ $progress }}% {{ __('messages.progress') }}</div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-3">
                                            <button wire:click="editPledge({{ $pledge->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
                                                {{ __('messages.edit') }}
                                            </button>
                                            <button wire:click="deletePledge({{ $pledge->id }})" wire:confirm="{{ __('messages.confirm_delete_pledge') }}" type="button" class="text-sm font-medium text-red-600 hover:text-red-800">
                                                {{ __('messages.delete') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-gray-500">{{ __('messages.no_pledges') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="border-t border-gray-200 p-5">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.latest_pledge_payments') }}</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('messages.date') }}</th>
                                <th class="px-4 py-3">{{ __('messages.pledge') }}</th>
                                <th class="px-4 py-3">{{ __('messages.amount') }}</th>
                                <th class="px-4 py-3">{{ __('messages.reference_number') }}</th>
                                <th class="px-4 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($pledgePayments as $payment)
                                @php
                                    $donor = $payment->pledge?->member
                                        ? trim($payment->pledge->member->first_name.' '.$payment->pledge->member->middle_name.' '.$payment->pledge->member->last_name)
                                        : $payment->pledge?->donor_name;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-gray-600">{{ $payment->payment_date?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-950">{{ $donor ?: '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ $payment->pledge?->incomeCategory?->name ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-950">{{ __('messages.currency_tzs') }} {{ number_format((float) $payment->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $payment->reference_number ?: '-' }}</td>
                                    <td class="px-4 py-3">
                                        <button wire:click="deletePledgePayment({{ $payment->id }})" wire:confirm="{{ __('messages.confirm_delete_pledge_payment') }}" type="button" class="text-sm font-medium text-red-600 hover:text-red-800">
                                            {{ __('messages.delete') }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_pledge_payments') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

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
                                        @if ($transaction->pledge)
                                            <div class="mt-1 text-xs font-medium text-emerald-700">{{ __('messages.pledge_payment') }}</div>
                                        @endif
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
