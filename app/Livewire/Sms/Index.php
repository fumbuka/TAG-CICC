<?php

namespace App\Livewire\Sms;

use App\Livewire\Concerns\ChecksSeededPermissions;
use App\Models\Department;
use App\Models\Member;
use App\Models\SmsCampaign;
use App\Models\SmsLog;
use App\Models\SmsPurchase;
use App\Models\SmsSetting;
use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Models\User;
use App\Services\Sms\BeemSmsService;
use App\Services\Sms\SmsMessageCounter;
use App\Services\Sms\SmsRecipientResolver;
use App\Services\Sms\SmsWalletService;
use App\Support\UserDataScope;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

#[Layout('layouts.app')]
class Index extends Component
{
    use ChecksSeededPermissions;

    public string $section = 'dashboard';

    public string $purchase_wallet_id = '';

    public string $purchase_sms_quantity = '1000';

    public string $purchase_notes = '';

    public string $compose_wallet_id = '';

    public string $compose_title = '';

    public string $compose_target_type = 'all_members';

    public string $compose_department_id = '';

    /**
     * @var array<int, int|string>
     */
    public array $compose_member_ids = [];

    public string $compose_message = '';

    public string $wallet_owner_type = SmsWallet::OWNER_DEPARTMENT;

    public string $wallet_department_id = '';

    public string $wallet_user_id = '';

    public string $wallet_name = '';

    public string $adjustment_wallet_id = '';

    public string $adjustment_type = 'add';

    public string $adjustment_credits = '';

    public string $adjustment_description = '';

    public string $setting_price_per_sms = '25';

    public string $setting_low_balance_threshold = '100';

    public string $setting_sender_id = '';

    public bool $setting_sending_enabled = false;

    public function mount(?string $section = null): void
    {
        $this->section = $section ?: 'dashboard';

        abort_unless($this->canAccessSection($this->section), 403);

        $this->loadSettings();
    }

    public function requestPurchase(): void
    {
        abort_unless($this->canBuySms(), 403);

        $validated = $this->validate([
            'purchase_wallet_id' => ['required', 'integer', Rule::exists('sms_wallets', 'id')],
            'purchase_sms_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'purchase_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $wallet = SmsWallet::findOrFail($validated['purchase_wallet_id']);
        abort_unless($this->canUseWallet($wallet), 403);

        $settings = SmsSetting::current();
        $quantity = (int) $validated['purchase_sms_quantity'];

        SmsPurchase::create([
            'sms_wallet_id' => $wallet->id,
            'requested_by_user_id' => Auth::id(),
            'sms_quantity' => $quantity,
            'price_per_sms' => $settings->price_per_sms,
            'total_amount' => $quantity * $settings->price_per_sms,
            'status' => SmsPurchase::STATUS_PENDING,
            'notes' => $validated['purchase_notes'] ?: null,
        ]);

        $this->reset(['purchase_sms_quantity', 'purchase_notes']);
        $this->purchase_sms_quantity = '1000';

        $this->dispatch('sms-purchase-requested');
    }

    public function approvePurchase(int $purchaseId): void
    {
        abort_unless($this->canApproveSms(), 403);

        $walletService = app(SmsWalletService::class);
        $purchase = SmsPurchase::with('wallet')->findOrFail($purchaseId);

        if ($purchase->status !== SmsPurchase::STATUS_PENDING) {
            return;
        }

        DB::transaction(function () use ($purchase, $walletService): void {
            $purchase->update([
                'status' => SmsPurchase::STATUS_APPROVED,
                'approved_by_user_id' => Auth::id(),
                'decided_at' => now(),
            ]);

            $walletService->addCredits(
                $purchase->wallet,
                (int) $purchase->sms_quantity,
                __('messages.sms_purchase_ledger_description', ['quantity' => $purchase->sms_quantity]),
                Auth::user(),
            );
        });

        $this->dispatch('sms-purchase-approved');
    }

    public function rejectPurchase(int $purchaseId): void
    {
        abort_unless($this->canApproveSms(), 403);

        SmsPurchase::query()
            ->whereKey($purchaseId)
            ->where('status', SmsPurchase::STATUS_PENDING)
            ->update([
                'status' => SmsPurchase::STATUS_REJECTED,
                'approved_by_user_id' => Auth::id(),
                'decided_at' => now(),
            ]);

        $this->dispatch('sms-purchase-rejected');
    }

    public function markPurchasePaid(int $purchaseId): void
    {
        abort_unless($this->canApproveSms(), 403);

        SmsPurchase::query()
            ->whereKey($purchaseId)
            ->where('status', SmsPurchase::STATUS_APPROVED)
            ->update([
                'status' => SmsPurchase::STATUS_PAID,
                'paid_at' => now(),
            ]);

        $this->dispatch('sms-purchase-paid');
    }

    public function createWallet(): void
    {
        abort_unless($this->canManageSmsWallets(), 403);

        $walletService = app(SmsWalletService::class);
        $validated = $this->validate([
            'wallet_owner_type' => ['required', Rule::in([SmsWallet::OWNER_CHURCH, SmsWallet::OWNER_DEPARTMENT, SmsWallet::OWNER_USER])],
            'wallet_department_id' => ['nullable', 'required_if:wallet_owner_type,'.SmsWallet::OWNER_DEPARTMENT, 'integer', Rule::exists('departments', 'id')],
            'wallet_user_id' => ['nullable', 'required_if:wallet_owner_type,'.SmsWallet::OWNER_USER, 'integer', Rule::exists('users', 'id')],
            'wallet_name' => ['nullable', 'string', 'max:255'],
        ]);

        $wallet = match ($validated['wallet_owner_type']) {
            SmsWallet::OWNER_CHURCH => $walletService->ensureChurchWallet(),
            SmsWallet::OWNER_USER => $walletService->ensureUserWallet(User::findOrFail((int) $validated['wallet_user_id'])),
            default => $walletService->ensureDepartmentWallet(Department::findOrFail((int) $validated['wallet_department_id'])),
        };

        if ($validated['wallet_name']) {
            $wallet->update(['name' => $validated['wallet_name']]);
        }

        $this->reset(['wallet_department_id', 'wallet_user_id', 'wallet_name']);
        $this->wallet_owner_type = SmsWallet::OWNER_DEPARTMENT;

        $this->dispatch('sms-wallet-created');
    }

    public function adjustWallet(): void
    {
        abort_unless($this->canManageSmsWallets(), 403);

        $walletService = app(SmsWalletService::class);
        $validated = $this->validate([
            'adjustment_wallet_id' => ['required', 'integer', Rule::exists('sms_wallets', 'id')],
            'adjustment_type' => ['required', Rule::in(['add', 'deduct'])],
            'adjustment_credits' => ['required', 'integer', 'min:1', 'max:1000000'],
            'adjustment_description' => ['required', 'string', 'max:255'],
        ]);

        $wallet = SmsWallet::findOrFail($validated['adjustment_wallet_id']);
        $credits = (int) $validated['adjustment_credits'];

        if ($validated['adjustment_type'] === 'add') {
            $walletService->addCredits($wallet, $credits, $validated['adjustment_description'], Auth::user(), SmsTransaction::TYPE_ADJUSTMENT);
        } else {
            $walletService->deductCredits($wallet, $credits, $validated['adjustment_description'], Auth::user(), SmsTransaction::TYPE_ADJUSTMENT);
        }

        $this->reset(['adjustment_wallet_id', 'adjustment_credits', 'adjustment_description']);
        $this->adjustment_type = 'add';

        $this->dispatch('sms-wallet-adjusted');
    }

    public function saveSettings(): void
    {
        abort_unless($this->canManageSmsSettings(), 403);

        $validated = $this->validate([
            'setting_price_per_sms' => ['required', 'integer', 'min:1', 'max:10000'],
            'setting_low_balance_threshold' => ['required', 'integer', 'min:0', 'max:1000000'],
            'setting_sender_id' => ['nullable', 'string', 'max:20'],
            'setting_sending_enabled' => ['boolean'],
        ]);

        SmsSetting::current()->update([
            'price_per_sms' => (int) $validated['setting_price_per_sms'],
            'low_balance_threshold' => (int) $validated['setting_low_balance_threshold'],
            'sender_id' => $validated['setting_sender_id'] ?: null,
            'sending_enabled' => $validated['setting_sending_enabled'],
        ]);

        $this->dispatch('sms-settings-updated');
    }

    public function previewCampaign(): void
    {
        abort_unless($this->canComposeSms(), 403);

        $resolver = app(SmsRecipientResolver::class);
        $counter = app(SmsMessageCounter::class);
        $this->validateCompose();
        try {
            $this->campaignPreview($resolver, $counter);
        } catch (RuntimeException $exception) {
            $this->addError('compose_target_type', $exception->getMessage());

            return;
        }

        $this->dispatch('sms-campaign-previewed');
    }

    public function sendCampaign(): void
    {
        abort_unless($this->canComposeSms(), 403);

        $resolver = app(SmsRecipientResolver::class);
        $counter = app(SmsMessageCounter::class);
        $walletService = app(SmsWalletService::class);
        $beemSmsService = app(BeemSmsService::class);
        $validated = $this->validateCompose();
        $wallet = SmsWallet::findOrFail($validated['compose_wallet_id']);
        abort_unless($this->canUseWallet($wallet), 403);

        $settings = SmsSetting::current();
        try {
            $recipients = $this->resolveRecipients($resolver);
        } catch (RuntimeException $exception) {
            $this->addError('compose_target_type', $exception->getMessage());

            return;
        }
        $parts = $counter->parts($validated['compose_message']);
        $requiredCredits = $recipients->count() * $parts;

        if ($recipients->isEmpty()) {
            $this->addError('compose_target_type', __('messages.sms_no_recipients'));

            return;
        }

        if ($wallet->balance < $requiredCredits) {
            $this->addError('compose_wallet_id', __('messages.sms_insufficient_balance_with_required', [
                'required' => $requiredCredits,
                'balance' => $wallet->balance,
            ]));

            return;
        }

        if (! $settings->sending_enabled) {
            $this->addError('compose_wallet_id', __('messages.sms_sending_disabled'));

            return;
        }

        $campaign = SmsCampaign::create([
            'sms_wallet_id' => $wallet->id,
            'sent_by_user_id' => Auth::id(),
            'department_id' => $validated['compose_target_type'] === 'department_members' ? (int) $validated['compose_department_id'] : null,
            'title' => $validated['compose_title'],
            'target_type' => $validated['compose_target_type'],
            'message' => $validated['compose_message'],
            'recipients_count' => $recipients->count(),
            'sms_parts' => $parts,
            'total_credits_used' => 0,
            'status' => SmsCampaign::STATUS_PENDING,
        ]);

        try {
            $providerResponse = $beemSmsService->send($validated['compose_message'], $recipients->all(), $settings->sender_id);

            DB::transaction(function () use ($campaign, $recipients, $validated, $providerResponse, $walletService, $wallet, $requiredCredits): void {
                $walletService->deductCredits(
                    $wallet,
                    $requiredCredits,
                    __('messages.sms_campaign_ledger_description', ['campaign' => $campaign->title]),
                    Auth::user(),
                );

                foreach ($recipients->values() as $index => $recipient) {
                    SmsLog::create([
                        'sms_campaign_id' => $campaign->id,
                        'member_id' => $recipient['member_id'] ?? null,
                        'visitor_id' => $recipient['visitor_id'] ?? null,
                        'recipient_name' => $recipient['name'],
                        'phone_number' => $recipient['phone_number'],
                        'message' => $validated['compose_message'],
                        'status' => SmsLog::STATUS_SENT,
                        'beem_message_id' => $this->messageIdFromProviderResponse($providerResponse, $index),
                        'provider_response' => $providerResponse,
                    ]);
                }

                $campaign->update([
                    'status' => SmsCampaign::STATUS_SENT,
                    'total_credits_used' => $requiredCredits,
                    'beem_response' => $providerResponse,
                    'sent_at' => now(),
                ]);
            });
        } catch (RuntimeException $exception) {
            $campaign->update([
                'status' => SmsCampaign::STATUS_FAILED,
                'beem_response' => ['error' => $exception->getMessage()],
            ]);

            foreach ($recipients as $recipient) {
                SmsLog::create([
                    'sms_campaign_id' => $campaign->id,
                    'member_id' => $recipient['member_id'] ?? null,
                    'visitor_id' => $recipient['visitor_id'] ?? null,
                    'recipient_name' => $recipient['name'],
                    'phone_number' => $recipient['phone_number'],
                    'message' => $validated['compose_message'],
                    'status' => SmsLog::STATUS_FAILED,
                    'error_message' => $exception->getMessage(),
                ]);
            }

            $this->addError('compose_wallet_id', $exception->getMessage());

            return;
        }

        $this->resetComposeForm();
        $this->dispatch('sms-campaign-sent');
    }

    public function retryCampaign(int $campaignId): void
    {
        abort_unless($this->canComposeSms(), 403);

        $walletService = app(SmsWalletService::class);
        $beemSmsService = app(BeemSmsService::class);
        $campaign = SmsCampaign::query()
            ->with(['wallet', 'logs' => fn ($query) => $query->where('status', SmsLog::STATUS_FAILED)])
            ->findOrFail($campaignId);

        $this->authorizeWalletAccess($campaign->wallet);

        $failedLogs = $campaign->logs;
        if ($failedLogs->isEmpty()) {
            return;
        }

        $requiredCredits = $failedLogs->count() * max((int) $campaign->sms_parts, 1);
        $wallet = $campaign->wallet->refresh();
        $settings = SmsSetting::current();

        if ($wallet->balance < $requiredCredits) {
            $this->addError('compose_wallet_id', __('messages.sms_insufficient_balance_with_required', [
                'required' => $requiredCredits,
                'balance' => $wallet->balance,
            ]));

            return;
        }

        if (! $settings->sending_enabled) {
            $this->addError('compose_wallet_id', __('messages.sms_sending_disabled'));

            return;
        }

        $recipients = $failedLogs->values()->map(fn (SmsLog $log): array => [
            'name' => $log->recipient_name,
            'phone_number' => $log->phone_number,
            'member_id' => $log->member_id,
            'visitor_id' => $log->visitor_id,
        ]);

        try {
            $providerResponse = $beemSmsService->send($campaign->message, $recipients->all(), $settings->sender_id);

            DB::transaction(function () use ($campaign, $failedLogs, $providerResponse, $walletService, $wallet, $requiredCredits): void {
                $walletService->deductCredits(
                    $wallet,
                    $requiredCredits,
                    __('messages.sms_campaign_retry_ledger_description', ['campaign' => $campaign->title]),
                    Auth::user(),
                );

                foreach ($failedLogs->values() as $index => $log) {
                    $log->update([
                        'status' => SmsLog::STATUS_SENT,
                        'beem_message_id' => $this->messageIdFromProviderResponse($providerResponse, $index),
                        'error_message' => null,
                        'provider_response' => $providerResponse,
                    ]);
                }

                $campaign->update([
                    'total_credits_used' => (int) $campaign->total_credits_used + $requiredCredits,
                    'status' => SmsCampaign::STATUS_SENT,
                    'beem_response' => $providerResponse,
                    'sent_at' => now(),
                ]);
            });
        } catch (RuntimeException $exception) {
            $campaign->update([
                'status' => SmsCampaign::STATUS_FAILED,
                'beem_response' => ['error' => $exception->getMessage()],
            ]);

            $failedLogs->each(fn (SmsLog $log) => $log->update([
                'error_message' => $exception->getMessage(),
            ]));

            $this->addError('compose_wallet_id', $exception->getMessage());

            return;
        }

        $this->dispatch('sms-campaign-retried');
    }

    public function render(): View
    {
        $user = Auth::user();
        $scope = UserDataScope::for($user);
        $walletService = app(SmsWalletService::class);
        $resolver = app(SmsRecipientResolver::class);
        $counter = app(SmsMessageCounter::class);
        $targetOptions = $this->targetOptions($scope);

        if ($this->canManageSmsWallets()) {
            $walletService->ensureChurchWallet();
            Department::query()->where('is_active', true)->get()->each(fn (Department $department) => $walletService->ensureDepartmentWallet($department));
        } elseif ($scope->departmentIds() !== []) {
            Department::query()
                ->whereIn('id', $scope->departmentIds())
                ->get()
                ->each(fn (Department $department) => $walletService->ensureDepartmentWallet($department));
        }

        if (! array_key_exists($this->compose_target_type, $targetOptions) && $targetOptions !== []) {
            $this->compose_target_type = array_key_first($targetOptions);
        }

        $visibleWallets = $this->visibleWallets();
        $activeWallets = (clone $visibleWallets)->where('is_active', true)->get();
        $selectedWallet = $activeWallets->firstWhere('id', (int) ($this->compose_wallet_id ?: $this->purchase_wallet_id)) ?? $activeWallets->first();

        if ($this->compose_wallet_id === '' && $selectedWallet) {
            $this->compose_wallet_id = (string) $selectedWallet->id;
        }

        if ($this->purchase_wallet_id === '' && $selectedWallet) {
            $this->purchase_wallet_id = (string) $selectedWallet->id;
        }

        $walletIds = $activeWallets->pluck('id');

        return view('livewire.sms.index', [
            'section' => $this->section,
            'smsSections' => $this->smsSections(),
            'settings' => SmsSetting::current(),
            'wallets' => $visibleWallets->with(['department', 'user'])->orderBy('owner_type')->orderBy('name')->get(),
            'activeWallets' => $activeWallets,
            'departmentOptions' => $this->departmentOptions($scope),
            'recipientMembers' => $this->recipientMemberOptions($scope),
            'targetOptions' => $targetOptions,
            'users' => User::query()->whereHas('member')->orderBy('name')->get(),
            'purchases' => SmsPurchase::query()
                ->with(['wallet.department', 'wallet.user', 'requestedBy', 'approvedBy'])
                ->when(! $this->canApproveSms(), fn ($query) => $query->whereIn('sms_wallet_id', $walletIds))
                ->latest()
                ->limit(25)
                ->get(),
            'campaigns' => SmsCampaign::query()
                ->with(['wallet.department', 'wallet.user', 'sentBy', 'department', 'logs'])
                ->whereIn('sms_wallet_id', $walletIds)
                ->latest()
                ->limit(25)
                ->get(),
            'transactions' => SmsTransaction::query()
                ->with(['wallet', 'performedBy'])
                ->whereIn('sms_wallet_id', $walletIds)
                ->latest()
                ->limit(25)
                ->get(),
            'failedSmsCount' => SmsLog::query()
                ->whereHas('campaign', fn ($query) => $query->whereIn('sms_wallet_id', $walletIds))
                ->where('status', SmsLog::STATUS_FAILED)
                ->count(),
            'reportSummary' => $this->reportSummary($walletIds, $activeWallets),
            'walletUsageSummary' => $this->walletUsageSummary($activeWallets),
            'preview' => $this->composePreview($resolver, $counter),
            'canBuySms' => $this->canBuySms(),
            'canComposeSms' => $this->canComposeSms(),
            'canApproveSms' => $this->canApproveSms(),
            'canManageSmsWallets' => $this->canManageSmsWallets(),
            'canManageSmsSettings' => $this->canManageSmsSettings(),
        ]);
    }

    private function loadSettings(): void
    {
        $settings = SmsSetting::current();

        $this->setting_price_per_sms = (string) $settings->price_per_sms;
        $this->setting_low_balance_threshold = (string) $settings->low_balance_threshold;
        $this->setting_sender_id = $settings->sender_id ?? '';
        $this->setting_sending_enabled = $settings->sending_enabled;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCompose(): array
    {
        return $this->validate([
            'compose_wallet_id' => ['required', 'integer', Rule::exists('sms_wallets', 'id')],
            'compose_title' => ['required', 'string', 'max:255'],
            'compose_target_type' => ['required', Rule::in(['all_members', 'visitors', 'department_members', 'custom_members'])],
            'compose_department_id' => ['nullable', 'required_if:compose_target_type,department_members', 'integer', Rule::exists('departments', 'id')],
            'compose_member_ids' => [Rule::requiredIf($this->compose_target_type === 'custom_members'), 'array', 'max:500'],
            'compose_member_ids.*' => ['integer', Rule::exists('members', 'id')],
            'compose_message' => ['required', 'string', 'max:2000'],
        ]);
    }

    private function resetComposeForm(): void
    {
        $this->reset(['compose_title', 'compose_message', 'compose_department_id', 'compose_member_ids']);
        $this->compose_target_type = 'all_members';
        $this->resetErrorBag();
    }

    private function authorizeWalletAccess(SmsWallet $wallet): void
    {
        if (! $this->canUseWallet($wallet)) {
            throw new RuntimeException(__('messages.sms_wallet_scope_denied'));
        }
    }

    private function canUseWallet(SmsWallet $wallet): bool
    {
        $scope = UserDataScope::for(Auth::user());

        return match ($wallet->owner_type) {
            SmsWallet::OWNER_CHURCH => $scope->isChurchWide(),
            SmsWallet::OWNER_USER => $wallet->user_id === Auth::id(),
            SmsWallet::OWNER_DEPARTMENT => $scope->isChurchWide() || in_array((int) $wallet->department_id, $scope->departmentIds(), true),
            default => false,
        };
    }

    private function visibleWallets()
    {
        $scope = UserDataScope::for(Auth::user());

        return SmsWallet::query()
            ->when(! $scope->isChurchWide(), function ($query) use ($scope): void {
                $query->where(function ($query) use ($scope): void {
                    $query->where(function ($query) use ($scope): void {
                        $query->where('owner_type', SmsWallet::OWNER_DEPARTMENT)
                            ->whereIn('department_id', $scope->departmentIds());
                    })->orWhere(function ($query): void {
                        $query->where('owner_type', SmsWallet::OWNER_USER)
                            ->where('user_id', Auth::id());
                    });
                });
            });
    }

    /**
     * @return array<string, string>
     */
    private function targetOptions(UserDataScope $scope): array
    {
        $options = [];

        if ($scope->isChurchWide()) {
            $options['all_members'] = __('messages.sms_target_all_members');
            $options['visitors'] = __('messages.sms_target_visitors');
        }

        if ($scope->isChurchWide() || $scope->departmentIds() !== []) {
            $options['department_members'] = __('messages.sms_target_department_members');
            $options['custom_members'] = __('messages.sms_target_custom_members');
        }

        return $options;
    }

    private function departmentOptions(UserDataScope $scope)
    {
        return Department::query()
            ->where('is_active', true)
            ->when(! $scope->isChurchWide(), fn ($query) => $query->whereIn('id', $scope->departmentIds()))
            ->orderBy('name')
            ->get();
    }

    private function recipientMemberOptions(UserDataScope $scope)
    {
        return $scope->applyMemberScope(Member::query())
            ->where('membership_status', 'active')
            ->whereNotNull('phone_number')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(500)
            ->get();
    }

    /**
     * @return array{recipients_count: int, sms_parts: int, credits_required: int, balance_before: int, balance_after: int}
     */
    private function composePreview(SmsRecipientResolver $resolver, SmsMessageCounter $counter): array
    {
        try {
            return $this->campaignPreview($resolver, $counter);
        } catch (RuntimeException) {
            return [
                'recipients_count' => 0,
                'sms_parts' => $counter->parts($this->compose_message),
                'credits_required' => 0,
                'balance_before' => SmsWallet::find((int) $this->compose_wallet_id)?->balance ?? 0,
                'balance_after' => SmsWallet::find((int) $this->compose_wallet_id)?->balance ?? 0,
            ];
        }
    }

    /**
     * @return array{recipients_count: int, sms_parts: int, credits_required: int, balance_before: int, balance_after: int}
     */
    private function campaignPreview(SmsRecipientResolver $resolver, SmsMessageCounter $counter): array
    {
        $wallet = SmsWallet::find((int) $this->compose_wallet_id);
        $recipients = $this->resolveRecipients($resolver);
        $parts = $counter->parts($this->compose_message);
        $credits = $recipients->count() * $parts;
        $balance = (int) ($wallet?->balance ?? 0);

        return [
            'recipients_count' => $recipients->count(),
            'sms_parts' => $parts,
            'credits_required' => $credits,
            'balance_before' => $balance,
            'balance_after' => max($balance - $credits, 0),
        ];
    }

    private function resolveRecipients(SmsRecipientResolver $resolver)
    {
        return $resolver->resolve(
            Auth::user(),
            $this->compose_target_type,
            $this->compose_department_id !== '' ? (int) $this->compose_department_id : null,
            $this->compose_member_ids,
        );
    }

    private function reportSummary($walletIds, $activeWallets): array
    {
        return [
            'wallets_count' => $activeWallets->count(),
            'current_balance' => (int) $activeWallets->sum('balance'),
            'credits_purchased' => (int) SmsTransaction::query()
                ->whereIn('sms_wallet_id', $walletIds)
                ->where('transaction_type', SmsTransaction::TYPE_PURCHASE)
                ->sum('credits_in'),
            'credits_used' => (int) SmsTransaction::query()
                ->whereIn('sms_wallet_id', $walletIds)
                ->where('transaction_type', SmsTransaction::TYPE_USAGE)
                ->sum('credits_out'),
            'paid_revenue' => (int) SmsPurchase::query()
                ->whereIn('sms_wallet_id', $walletIds)
                ->where('status', SmsPurchase::STATUS_PAID)
                ->sum('total_amount'),
            'pending_value' => (int) SmsPurchase::query()
                ->whereIn('sms_wallet_id', $walletIds)
                ->whereIn('status', [SmsPurchase::STATUS_PENDING, SmsPurchase::STATUS_APPROVED])
                ->sum('total_amount'),
        ];
    }

    private function walletUsageSummary($activeWallets)
    {
        $highestUsage = max((int) $activeWallets->max('credits_used'), 1);

        return $activeWallets
            ->sortByDesc('credits_used')
            ->map(fn (SmsWallet $wallet): array => [
                'name' => $wallet->name,
                'owner' => $wallet->ownerLabel(),
                'purchased' => (int) $wallet->credits_purchased,
                'used' => (int) $wallet->credits_used,
                'balance' => (int) $wallet->balance,
                'usage_percent' => round(((int) $wallet->credits_used / $highestUsage) * 100),
            ])
            ->values();
    }

    private function messageIdFromProviderResponse(array $providerResponse, int $recipientIndex): ?string
    {
        $paths = [
            "messages.$recipientIndex.message_id",
            "messages.$recipientIndex.messageId",
            "recipients.$recipientIndex.message_id",
            "recipients.$recipientIndex.messageId",
            "data.$recipientIndex.message_id",
            "data.$recipientIndex.messageId",
            'message_id',
            'messageId',
        ];

        foreach ($paths as $path) {
            $value = data_get($providerResponse, $path);

            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    private function canAccessSection(string $section): bool
    {
        return match ($section) {
            'buy' => $this->canBuySms(),
            'compose' => $this->canComposeSms(),
            'wallets' => $this->canManageSmsWallets(),
            'approvals' => $this->canApproveSms(),
            'reports' => $this->canViewSmsReports(),
            'settings' => $this->canManageSmsSettings(),
            default => $this->canViewSms(),
        };
    }

    private function canViewSms(): bool
    {
        return $this->permissionsAreUnseeded() || (Auth::user()?->can('sms.view') ?? false);
    }

    private function canBuySms(): bool
    {
        return $this->permissionsAreUnseeded() || (Auth::user()?->can('sms.buy') ?? false);
    }

    private function canComposeSms(): bool
    {
        return $this->permissionsAreUnseeded() || (Auth::user()?->can('sms.compose') ?? false);
    }

    private function canApproveSms(): bool
    {
        return $this->permissionsAreUnseeded() || (Auth::user()?->can('sms.approve') ?? false);
    }

    private function canManageSmsWallets(): bool
    {
        return $this->permissionsAreUnseeded() || (Auth::user()?->can('sms.wallets') ?? false);
    }

    private function canViewSmsReports(): bool
    {
        return $this->permissionsAreUnseeded() || (Auth::user()?->can('sms.reports') ?? false);
    }

    private function canManageSmsSettings(): bool
    {
        return $this->permissionsAreUnseeded() || (Auth::user()?->can('sms.settings') ?? false);
    }

    /**
     * @return array<string, string>
     */
    private function smsSections(): array
    {
        return collect([
            'dashboard' => __('messages.sms_dashboard'),
            'buy' => __('messages.sms_buy_credits'),
            'compose' => __('messages.sms_compose'),
            'campaigns' => __('messages.sms_campaign_history'),
            'wallets' => __('messages.sms_wallets_management'),
            'approvals' => __('messages.sms_purchase_approval'),
            'reports' => __('messages.sms_reports'),
            'settings' => __('messages.sms_settings'),
        ])
            ->filter(fn (string $label, string $section): bool => $this->canAccessSection($section))
            ->all();
    }
}
