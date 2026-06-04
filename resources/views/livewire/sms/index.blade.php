<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-red-700">{{ __('messages.sms') }}</p>
                <h1 class="text-2xl font-semibold text-gray-950">{{ $smsSections[$section] ?? __('messages.sms_dashboard') }}</h1>
                <p class="mt-1 max-w-3xl text-sm text-gray-600">{{ __('messages.sms_module_help') }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ($smsSections as $smsSection => $label)
                    <a href="{{ route('sms.index', $smsSection === 'dashboard' ? null : $smsSection) }}" wire:navigate @class([
                        'inline-flex items-center justify-center rounded-md px-3 py-2 text-sm font-semibold shadow-sm transition',
                        'bg-red-700 text-white hover:bg-red-800' => $section === $smsSection,
                        'border border-gray-300 bg-white text-gray-700 hover:border-red-200 hover:bg-red-50 hover:text-red-800' => $section !== $smsSection,
                    ])>
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div x-data="{ show: false, message: '' }"
            x-on:sms-purchase-requested.window="message = '{{ __('messages.sms_purchase_requested') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:sms-purchase-approved.window="message = '{{ __('messages.sms_purchase_approved') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:sms-purchase-rejected.window="message = '{{ __('messages.sms_purchase_rejected') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:sms-purchase-paid.window="message = '{{ __('messages.sms_purchase_paid') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:sms-wallet-created.window="message = '{{ __('messages.sms_wallet_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:sms-wallet-adjusted.window="message = '{{ __('messages.sms_wallet_adjusted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:sms-settings-updated.window="message = '{{ __('messages.sms_settings_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:sms-campaign-previewed.window="message = '{{ __('messages.sms_campaign_preview_ready') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:sms-campaign-sent.window="message = '{{ __('messages.sms_campaign_sent') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:sms-campaign-retried.window="message = '{{ __('messages.sms_campaign_retried') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-show="show"
            x-cloak
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            x-text="message"></div>

        @if ($section === 'dashboard')
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @forelse ($wallets as $wallet)
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ $wallet->ownerLabel() }}</p>
                                <h2 class="mt-1 text-lg font-semibold text-gray-950">{{ $wallet->name }}</h2>
                            </div>
                            <span @class([
                                'rounded-full px-2 py-1 text-xs font-semibold',
                                'bg-emerald-50 text-emerald-700' => $wallet->balance > $settings->low_balance_threshold,
                                'bg-amber-50 text-amber-800' => $wallet->balance <= $settings->low_balance_threshold,
                            ])>
                                {{ number_format($wallet->balance) }}
                            </span>
                        </div>
                        <dl class="mt-5 grid grid-cols-3 gap-3 text-sm">
                            <div>
                                <dt class="text-gray-500">{{ __('messages.sms_purchased') }}</dt>
                                <dd class="mt-1 font-semibold text-gray-950">{{ number_format($wallet->credits_purchased) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('messages.sms_used') }}</dt>
                                <dd class="mt-1 font-semibold text-gray-950">{{ number_format($wallet->credits_used) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('messages.sms_remaining') }}</dt>
                                <dd class="mt-1 font-semibold text-gray-950">{{ number_format($wallet->balance) }}</dd>
                            </div>
                        </dl>
                        @if ($wallet->balance <= $settings->low_balance_threshold)
                            <p class="mt-4 rounded-md bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">
                                {{ __('messages.sms_low_balance_warning', ['balance' => number_format($wallet->balance)]) }}
                            </p>
                        @endif
                    </div>
                @empty
                    <div class="rounded-lg border border-gray-200 bg-white p-5 text-sm text-gray-600 shadow-sm md:col-span-2 xl:col-span-4">
                        {{ __('messages.sms_no_wallets') }}
                    </div>
                @endforelse
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ __('messages.sms_failed_count') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-red-700">{{ number_format($failedSmsCount) }}</p>
                </section>
                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.sms_recent_campaigns') }}</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($campaigns->take(5) as $campaign)
                            <div class="flex items-start justify-between gap-4 border-t border-gray-100 pt-3 first:border-t-0 first:pt-0">
                                <div>
                                    <p class="font-medium text-gray-950">{{ $campaign->title }}</p>
                                    <p class="text-sm text-gray-500">{{ $campaign->sentBy?->name }} · {{ number_format($campaign->recipients_count) }} {{ __('messages.sms_recipients') }}</p>
                                </div>
                                <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">{{ __('messages.sms_status_'.$campaign->status) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-600">{{ __('messages.sms_no_campaigns') }}</p>
                        @endforelse
                    </div>
                </section>
                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm lg:col-span-3">
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.sms_recent_purchases') }}</h2>
                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @forelse ($purchases->take(6) as $purchase)
                            <div class="rounded-md border border-gray-100 bg-gray-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-medium text-gray-950">{{ $purchase->wallet?->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $purchase->requestedBy?->name }} · {{ $purchase->created_at?->format('d M Y') }}</p>
                                    </div>
                                    <span class="rounded-full bg-white px-2 py-1 text-xs font-semibold text-gray-600">{{ __('messages.sms_purchase_status_'.$purchase->status) }}</span>
                                </div>
                                <p class="mt-3 text-sm text-gray-600">{{ number_format($purchase->sms_quantity) }} {{ __('messages.sms_credits') }} · {{ __('messages.currency_tzs') }} {{ number_format($purchase->total_amount) }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-600">{{ __('messages.sms_no_purchases') }}</p>
                        @endforelse
                    </div>
                </section>
            </div>
        @endif

        @if ($section === 'buy' && $canBuySms)
            <div class="space-y-6">
                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <form wire:submit="requestPurchase" class="grid gap-4 lg:grid-cols-4">
                        <div class="lg:col-span-2">
                            <x-input-label for="purchase_wallet_id" :value="__('messages.sms_wallet')" />
                            <select wire:model="purchase_wallet_id" id="purchase_wallet_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                @foreach ($activeWallets as $wallet)
                                    <option value="{{ $wallet->id }}">{{ $wallet->name }} ({{ number_format($wallet->balance) }})</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('purchase_wallet_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="purchase_sms_quantity" :value="__('messages.sms_quantity')" />
                            <x-text-input wire:model.live="purchase_sms_quantity" id="purchase_sms_quantity" type="number" min="1" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('purchase_sms_quantity')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label :value="__('messages.sms_total_amount')" />
                            <p class="mt-2 text-lg font-semibold text-gray-950">{{ __('messages.currency_tzs') }} {{ number_format(((int) $purchase_sms_quantity) * $settings->price_per_sms) }}</p>
                            <p class="text-xs text-gray-500">{{ number_format((int) $purchase_sms_quantity) }} x {{ __('messages.currency_tzs') }} {{ number_format($settings->price_per_sms) }}</p>
                        </div>
                        <div class="lg:col-span-4">
                            <x-input-label for="purchase_notes" :value="__('messages.notes')" />
                            <textarea wire:model="purchase_notes" id="purchase_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"></textarea>
                            <x-input-error :messages="$errors->get('purchase_notes')" class="mt-2" />
                        </div>
                        <div class="lg:col-span-4 flex justify-end">
                            <x-primary-button>{{ __('messages.sms_request_purchase') }}</x-primary-button>
                        </div>
                    </form>
                </section>

                <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 p-5">
                        <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.sms_my_purchase_requests') }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ __('messages.sms_my_purchase_requests_help') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('messages.sms_wallet') }}</th>
                                    <th class="px-4 py-3">{{ __('messages.sms_quantity') }}</th>
                                    <th class="px-4 py-3">{{ __('messages.sms_total_amount') }}</th>
                                    <th class="px-4 py-3">{{ __('messages.status') }}</th>
                                    <th class="px-4 py-3">{{ __('messages.date') }}</th>
                                    <th class="px-4 py-3">{{ __('messages.approved_by') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($myPurchases as $purchase)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-gray-950">{{ $purchase->wallet?->name }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ number_format($purchase->sms_quantity) }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ __('messages.currency_tzs') }} {{ number_format($purchase->total_amount) }}</td>
                                        <td class="px-4 py-3"><span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">{{ __('messages.sms_purchase_status_'.$purchase->status) }}</span></td>
                                        <td class="px-4 py-3 text-gray-600">{{ $purchase->created_at?->format('d M Y H:i') }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $purchase->approvedBy?->name ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('messages.sms_no_purchases') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        @endif

        @if ($section === 'compose' && $canComposeSms)
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
                    <form wire:submit="sendCampaign" class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="compose_wallet_id" :value="__('messages.sms_wallet')" />
                                <select wire:model.live="compose_wallet_id" id="compose_wallet_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                    @foreach ($activeWallets as $wallet)
                                        <option value="{{ $wallet->id }}">{{ $wallet->name }} ({{ number_format($wallet->balance) }})</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('compose_wallet_id')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="compose_title" :value="__('messages.sms_campaign_title')" />
                                <x-text-input wire:model="compose_title" id="compose_title" type="text" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('compose_title')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="compose_target_type" :value="__('messages.sms_target_group')" />
                                <select wire:model.live="compose_target_type" id="compose_target_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                    @foreach ($targetOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('compose_target_type')" class="mt-2" />
                            </div>
                            @if ($compose_target_type === 'department_members')
                                <div>
                                    <x-input-label for="compose_department_id" :value="__('messages.department')" />
                                    <select wire:model.live="compose_department_id" id="compose_department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                        <option value="">{{ __('messages.select_department') }}</option>
                                        @foreach ($departmentOptions as $department)
                                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('compose_department_id')" class="mt-2" />
                                </div>
                            @endif
                            @if ($compose_target_type === 'single_member')
                                <div class="md:col-span-2">
                                    <x-input-label for="compose_member_id" :value="__('messages.sms_single_recipient')" />
                                    <select wire:model.live="compose_member_id" id="compose_member_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                        <option value="">{{ __('messages.sms_select_member') }}</option>
                                        @foreach ($recipientMembers as $member)
                                            <option value="{{ $member->id }}">{{ $member->fullName() }} · {{ $member->phone_number }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('compose_member_id')" class="mt-2" />
                                </div>
                            @endif
                            @if ($compose_target_type === 'custom_members')
                                <div class="md:col-span-2">
                                    <x-input-label for="compose_member_ids" :value="__('messages.sms_custom_recipients')" />
                                    <select wire:model.live="compose_member_ids" id="compose_member_ids" multiple size="8" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                        @foreach ($recipientMembers as $member)
                                            <option value="{{ $member->id }}">{{ $member->fullName() }} · {{ $member->phone_number }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-2 text-xs text-gray-500">{{ __('messages.sms_custom_recipients_help') }}</p>
                                    <x-input-error :messages="$errors->get('compose_member_ids')" class="mt-2" />
                                    <x-input-error :messages="$errors->get('compose_member_ids.*')" class="mt-2" />
                                </div>
                            @endif
                            @if ($compose_target_type === 'manual_recipients')
                                <div class="md:col-span-2">
                                    <x-input-label for="compose_manual_recipients" :value="__('messages.sms_manual_recipients')" />
                                    <textarea wire:model.live.debounce.400ms="compose_manual_recipients" id="compose_manual_recipients" rows="7" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" placeholder="{{ __('messages.sms_manual_recipients_placeholder') }}"></textarea>
                                    <p class="mt-2 text-xs text-gray-500">{{ __('messages.sms_manual_recipients_help') }}</p>
                                    <x-input-error :messages="$errors->get('compose_manual_recipients')" class="mt-2" />
                                </div>
                            @endif
                        </div>
                        <div>
                            <x-input-label for="compose_message" :value="__('messages.sms_message')" />
                            <textarea wire:model.live.debounce.400ms="compose_message" id="compose_message" rows="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"></textarea>
                            <x-input-error :messages="$errors->get('compose_message')" class="mt-2" />
                        </div>

                        <div class="flex flex-wrap justify-end gap-2">
                            <x-secondary-button type="button" wire:click="previewCampaign">{{ __('messages.sms_preview_campaign') }}</x-secondary-button>
                            <x-primary-button wire:confirm="{{ __('messages.sms_confirm_send', ['recipients' => number_format($preview['recipients_count']), 'parts' => number_format($preview['sms_parts']), 'credits' => number_format($preview['credits_required']), 'before' => number_format($preview['balance_before']), 'after' => number_format($preview['balance_after'])]) }}">
                                {{ __('messages.sms_confirm_send_button') }}
                            </x-primary-button>
                        </div>
                    </form>

                    <aside class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.sms_campaign_preview') }}</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">{{ __('messages.sms_recipients') }}</dt>
                                <dd class="font-semibold text-gray-950">{{ number_format($preview['recipients_count']) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">{{ __('messages.sms_parts_per_recipient') }}</dt>
                                <dd class="font-semibold text-gray-950">{{ number_format($preview['sms_parts']) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">{{ __('messages.sms_credits_required') }}</dt>
                                <dd class="font-semibold text-gray-950">{{ number_format($preview['credits_required']) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 border-t border-gray-200 pt-3">
                                <dt class="text-gray-500">{{ __('messages.sms_balance_before') }}</dt>
                                <dd class="font-semibold text-gray-950">{{ number_format($preview['balance_before']) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">{{ __('messages.sms_balance_after') }}</dt>
                                <dd class="font-semibold text-gray-950">{{ number_format($preview['balance_after']) }}</dd>
                            </div>
                        </dl>
                        @if ($preview['balance_before'] <= $settings->low_balance_threshold)
                            <p class="mt-4 rounded-md bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">
                                {{ __('messages.sms_low_balance_warning', ['balance' => number_format($preview['balance_before'])]) }}
                            </p>
                        @endif
                    </aside>
                </div>
            </section>
        @endif

        @if ($section === 'campaigns')
            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.sms_campaign_history') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('messages.sms_campaign_title') }}</th>
                                <th class="px-4 py-3">{{ __('messages.sms_wallet') }}</th>
                                <th class="px-4 py-3">{{ __('messages.sms_recipients') }}</th>
                                <th class="px-4 py-3">{{ __('messages.sms_credits_used') }}</th>
                                <th class="px-4 py-3">{{ __('messages.status') }}</th>
                                <th class="px-4 py-3">{{ __('messages.date') }}</th>
                                <th class="px-4 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($campaigns as $campaign)
                                @php($failedLogs = $campaign->logs->where('status', \App\Models\SmsLog::STATUS_FAILED)->count())
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-950">{{ $campaign->title }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $campaign->wallet?->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ number_format($campaign->recipients_count) }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ number_format($campaign->total_credits_used) }}</td>
                                    <td class="px-4 py-3"><span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">{{ __('messages.sms_status_'.$campaign->status) }}</span></td>
                                    <td class="px-4 py-3 text-gray-600">{{ $campaign->created_at?->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3">
                                        @if ($canComposeSms && $failedLogs > 0)
                                            <button wire:click="retryCampaign({{ $campaign->id }})" wire:confirm="{{ __('messages.sms_confirm_retry', ['count' => number_format($failedLogs)]) }}" type="button" class="font-medium text-red-700 hover:text-red-900">
                                                {{ __('messages.sms_retry_failed') }}
                                            </button>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">{{ __('messages.sms_no_campaigns') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($section === 'approvals' && $canApproveSms)
            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.sms_purchase_approval') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('messages.sms_wallet') }}</th>
                                <th class="px-4 py-3">{{ __('messages.requested_by') }}</th>
                                <th class="px-4 py-3">{{ __('messages.sms_quantity') }}</th>
                                <th class="px-4 py-3">{{ __('messages.sms_total_amount') }}</th>
                                <th class="px-4 py-3">{{ __('messages.status') }}</th>
                                <th class="px-4 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($purchases as $purchase)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-950">{{ $purchase->wallet?->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $purchase->requestedBy?->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ number_format($purchase->sms_quantity) }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ __('messages.currency_tzs') }} {{ number_format($purchase->total_amount) }}</td>
                                    <td class="px-4 py-3"><span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">{{ __('messages.sms_purchase_status_'.$purchase->status) }}</span></td>
                                    <td class="px-4 py-3">
                                        @if ($purchase->status === 'pending')
                                            <div class="flex flex-wrap gap-3">
                                                <button wire:click="approvePurchase({{ $purchase->id }})" type="button" class="font-medium text-emerald-700 hover:text-emerald-900">{{ __('messages.approve') }}</button>
                                                <button wire:click="rejectPurchase({{ $purchase->id }})" type="button" class="font-medium text-red-600 hover:text-red-800">{{ __('messages.reject') }}</button>
                                            </div>
                                        @elseif ($purchase->status === 'approved')
                                            <button wire:click="markPurchasePaid({{ $purchase->id }})" type="button" class="font-medium text-emerald-700 hover:text-emerald-900">{{ __('messages.sms_mark_paid') }}</button>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('messages.sms_no_purchases') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($section === 'wallets' && $canManageSmsWallets)
            <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.sms_create_wallet') }}</h2>
                    <form wire:submit="createWallet" class="mt-4 space-y-4">
                        <div>
                            <x-input-label for="wallet_owner_type" :value="__('messages.sms_wallet_owner')" />
                            <select wire:model.live="wallet_owner_type" id="wallet_owner_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                <option value="church">{{ __('messages.church_scope') }}</option>
                                <option value="department">{{ __('messages.department') }}</option>
                                <option value="user">{{ __('messages.user') }}</option>
                            </select>
                        </div>
                        @if ($wallet_owner_type === 'department')
                            <div>
                                <x-input-label for="wallet_department_id" :value="__('messages.department')" />
                                <select wire:model="wallet_department_id" id="wallet_department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                    <option value="">{{ __('messages.select_department') }}</option>
                                    @foreach ($departmentOptions as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @if ($wallet_owner_type === 'user')
                            <div>
                                <x-input-label for="wallet_user_id" :value="__('messages.user')" />
                                <select wire:model="wallet_user_id" id="wallet_user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                    <option value="">{{ __('messages.select_user') }}</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div>
                            <x-input-label for="wallet_name" :value="__('messages.name')" />
                            <x-text-input wire:model="wallet_name" id="wallet_name" type="text" class="mt-1 block w-full" />
                        </div>
                        <div class="flex justify-end"><x-primary-button>{{ __('messages.save') }}</x-primary-button></div>
                    </form>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.sms_manual_adjustment') }}</h2>
                    <form wire:submit="adjustWallet" class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <x-input-label for="adjustment_wallet_id" :value="__('messages.sms_wallet')" />
                            <select wire:model="adjustment_wallet_id" id="adjustment_wallet_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                <option value="">{{ __('messages.select_wallet') }}</option>
                                @foreach ($wallets as $wallet)
                                    <option value="{{ $wallet->id }}">{{ $wallet->name }} ({{ number_format($wallet->balance) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="adjustment_type" :value="__('messages.type')" />
                            <select wire:model="adjustment_type" id="adjustment_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                <option value="add">{{ __('messages.add') }}</option>
                                <option value="deduct">{{ __('messages.deduct') }}</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="adjustment_credits" :value="__('messages.sms_credits')" />
                            <x-text-input wire:model="adjustment_credits" id="adjustment_credits" type="number" min="1" class="mt-1 block w-full" />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="adjustment_description" :value="__('messages.description')" />
                            <x-text-input wire:model="adjustment_description" id="adjustment_description" type="text" class="mt-1 block w-full" />
                        </div>
                        <div class="md:col-span-2 flex justify-end"><x-primary-button>{{ __('messages.save') }}</x-primary-button></div>
                    </form>
                </section>
            </div>
        @endif

        @if ($section === 'reports')
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.sms_reports') }}</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.sms_report_wallets') }}</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-950">{{ number_format($reportSummary['wallets_count']) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.sms_report_balance') }}</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-950">{{ number_format($reportSummary['current_balance']) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.sms_report_purchased') }}</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-950">{{ number_format($reportSummary['credits_purchased']) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.sms_report_used') }}</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-950">{{ number_format($reportSummary['credits_used']) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.sms_report_paid_revenue') }}</p>
                        <p class="mt-2 text-xl font-semibold text-gray-950">{{ __('messages.currency_tzs') }} {{ number_format($reportSummary['paid_revenue']) }}</p>
                    </div>
                </div>

                <div class="mt-6 rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-950">{{ __('messages.sms_usage_by_wallet') }}</h3>
                            <p class="text-sm text-gray-500">{{ __('messages.sms_pending_purchase_value') }}: {{ __('messages.currency_tzs') }} {{ number_format($reportSummary['pending_value']) }}</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse ($walletUsageSummary as $summary)
                            <div>
                                <div class="flex items-center justify-between gap-4 text-sm">
                                    <span class="font-medium text-gray-800">{{ $summary['name'] }}</span>
                                    <span class="text-gray-500">{{ number_format($summary['used']) }} / {{ number_format($summary['purchased']) }}</span>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-white">
                                    <div class="h-2 rounded-full bg-red-700" style="width: {{ $summary['usage_percent'] }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-600">{{ __('messages.sms_no_wallets') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('messages.sms_wallet') }}</th>
                                <th class="px-4 py-3">{{ __('messages.type') }}</th>
                                <th class="px-4 py-3">{{ __('messages.sms_credits_in') }}</th>
                                <th class="px-4 py-3">{{ __('messages.sms_credits_out') }}</th>
                                <th class="px-4 py-3">{{ __('messages.sms_balance_after') }}</th>
                                <th class="px-4 py-3">{{ __('messages.description') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-950">{{ $transaction->wallet?->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ __('messages.sms_transaction_'.$transaction->transaction_type) }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ number_format($transaction->credits_in) }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ number_format($transaction->credits_out) }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ number_format($transaction->balance_after) }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $transaction->description }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('messages.sms_no_transactions') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($section === 'settings' && $canManageSmsSettings)
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <form wire:submit="saveSettings" class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="setting_price_per_sms" :value="__('messages.sms_price_per_sms')" />
                        <x-text-input wire:model="setting_price_per_sms" id="setting_price_per_sms" type="number" min="1" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="setting_low_balance_threshold" :value="__('messages.sms_low_balance_threshold')" />
                        <x-text-input wire:model="setting_low_balance_threshold" id="setting_low_balance_threshold" type="number" min="0" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="setting_sender_id" :value="__('messages.sms_sender_id')" />
                        <x-text-input wire:model="setting_sender_id" id="setting_sender_id" type="text" class="mt-1 block w-full" />
                    </div>
                    <label class="mt-6 flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input wire:model="setting_sending_enabled" type="checkbox" class="rounded border-gray-300 text-red-700 focus:ring-red-600">
                        <span>{{ __('messages.sms_enable_sending') }}</span>
                    </label>
                    <div class="md:col-span-2 rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-600">
                        {{ __('messages.sms_beem_env_help') }}
                    </div>
                    <div class="md:col-span-2 flex justify-end"><x-primary-button>{{ __('messages.save') }}</x-primary-button></div>
                </form>
            </section>
        @endif
    </div>
</div>
