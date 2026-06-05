<?php

namespace App\Livewire\Sms;

use App\Livewire\Concerns\ChecksSeededPermissions;
use App\Models\Department;
use App\Models\Member;
use App\Models\SmsCampaign;
use App\Models\SmsLog;
use App\Models\SmsPurchase;
use App\Models\SmsSetting;
use App\Models\SmsTemplate;
use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Models\User;
use App\Services\Sms\SmsCampaignDispatcher;
use App\Services\Sms\SmsMessageCounter;
use App\Services\Sms\SmsRecipientResolver;
use App\Services\Sms\SmsWalletService;
use App\Services\OperationalModuleReportPdfService;
use App\Support\UserDataScope;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public string $compose_member_id = '';

    /**
     * @var array<int, int|string>
     */
    public array $compose_member_ids = [];

    public string $compose_manual_recipients = '';

    public string $compose_message = '';

    public string $compose_template_id = '';

    public bool $compose_personalization_enabled = false;

    public string $compose_send_mode = 'now';

    public string $compose_scheduled_at = '';

    public ?int $editing_template_id = null;

    public string $template_title = '';

    public string $template_message = '';

    public bool $template_is_active = true;

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

    public function applyTemplate(): void
    {
        abort_unless($this->canComposeSms(), 403);

        if ($this->compose_template_id === '') {
            return;
        }

        $template = SmsTemplate::query()
            ->where('is_active', true)
            ->findOrFail((int) $this->compose_template_id);

        $this->compose_message = $template->message;
    }

    public function saveTemplate(): void
    {
        abort_unless($this->canManageSmsTemplates(), 403);

        $validated = $this->validate([
            'template_title' => ['required', 'string', 'max:255'],
            'template_message' => ['required', 'string', 'max:2000'],
            'template_is_active' => ['boolean'],
        ]);

        $attributes = [
            'created_by_user_id' => Auth::id(),
            'title' => $validated['template_title'],
            'message' => $validated['template_message'],
            'is_active' => $validated['template_is_active'],
        ];

        if ($this->editing_template_id) {
            SmsTemplate::findOrFail($this->editing_template_id)->update($attributes);
        } else {
            SmsTemplate::create($attributes);
        }

        $this->resetTemplateForm();
        $this->dispatch('sms-template-saved');
    }

    public function editTemplate(int $templateId): void
    {
        abort_unless($this->canManageSmsTemplates(), 403);

        $template = SmsTemplate::findOrFail($templateId);

        $this->editing_template_id = $template->id;
        $this->template_title = $template->title;
        $this->template_message = $template->message;
        $this->template_is_active = $template->is_active;
    }

    public function deleteTemplate(int $templateId): void
    {
        abort_unless($this->canManageSmsTemplates(), 403);

        SmsTemplate::findOrFail($templateId)->delete();

        $this->resetTemplateForm();
        $this->dispatch('sms-template-deleted');
    }

    public function cancelTemplateEdit(): void
    {
        $this->resetTemplateForm();
    }

    public function previewCampaign(): void
    {
        abort_unless($this->canComposeSms(), 403);

        $resolver = app(SmsRecipientResolver::class);
        $counter = app(SmsMessageCounter::class);
        $dispatcher = app(SmsCampaignDispatcher::class);
        $this->validateCompose();
        try {
            $this->campaignPreview($resolver, $counter, $dispatcher);
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
        $dispatcher = app(SmsCampaignDispatcher::class);
        $validated = $this->validateCompose();
        $wallet = SmsWallet::findOrFail($validated['compose_wallet_id']);
        abort_unless($this->canUseWallet($wallet), 403);

        try {
            $recipients = $this->resolveRecipients($resolver);
        } catch (RuntimeException $exception) {
            $this->addError('compose_target_type', $exception->getMessage());

            return;
        }

        $preview = $dispatcher->estimate(
            $validated['compose_message'],
            $recipients,
            (bool) $validated['compose_personalization_enabled'],
        );
        $requiredCredits = $preview['credits_required'];

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

        $scheduledAt = $this->scheduledAtFromInput($validated['compose_send_mode'], $validated['compose_scheduled_at'] ?? null);
        $settings = SmsSetting::current();

        if (! $scheduledAt && ! $settings->sending_enabled) {
            $this->addError('compose_wallet_id', __('messages.sms_sending_disabled'));

            return;
        }

        try {
            $campaign = $dispatcher->create(
                wallet: $wallet,
                user: Auth::user(),
                title: $validated['compose_title'],
                targetType: $validated['compose_target_type'],
                message: $validated['compose_message'],
                recipients: $recipients,
                departmentId: $validated['compose_target_type'] === 'department_members' ? (int) $validated['compose_department_id'] : null,
                templateId: $validated['compose_template_id'] ? (int) $validated['compose_template_id'] : null,
                personalize: (bool) $validated['compose_personalization_enabled'],
                scheduledAt: $scheduledAt,
            );
        } catch (RuntimeException $exception) {
            $this->addError('compose_wallet_id', $exception->getMessage());

            return;
        }

        $this->resetComposeForm();
        $this->dispatch($campaign->status === SmsCampaign::STATUS_SCHEDULED ? 'sms-campaign-scheduled' : 'sms-campaign-sent');
    }

    public function retryCampaign(int $campaignId): void
    {
        abort_unless($this->canComposeSms(), 403);

        $dispatcher = app(SmsCampaignDispatcher::class);
        $campaign = SmsCampaign::query()
            ->with(['wallet', 'logs' => fn ($query) => $query->where('status', SmsLog::STATUS_FAILED)])
            ->findOrFail($campaignId);

        $this->authorizeWalletAccess($campaign->wallet);

        $failedLogs = $campaign->logs;
        if ($failedLogs->isEmpty()) {
            return;
        }

        $counter = app(SmsMessageCounter::class);
        $requiredCredits = (int) $failedLogs->sum(fn (SmsLog $log): int => $counter->parts($log->message));
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

        try {
            $dispatcher->retryFailedCampaign($campaign, Auth::user());
        } catch (RuntimeException $exception) {
            $this->addError('compose_wallet_id', $exception->getMessage());

            return;
        }

        $this->dispatch('sms-campaign-retried');
    }

    public function downloadSmsReport(): BinaryFileResponse
    {
        abort_unless($this->canViewSmsReports(), 403);

        $download = app(OperationalModuleReportPdfService::class)->create('sms', Auth::user());

        return response()
            ->download($download['path'], $download['filename'], [
                'Content-Type' => OperationalModuleReportPdfService::CONTENT_TYPE,
            ])
            ->deleteFileAfterSend();
    }

    public function render(): View
    {
        $user = Auth::user();
        $scope = UserDataScope::for($user);
        $walletService = app(SmsWalletService::class);
        $resolver = app(SmsRecipientResolver::class);
        $counter = app(SmsMessageCounter::class);
        $dispatcher = app(SmsCampaignDispatcher::class);
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
            'recipientMembers' => $this->recipientMemberOptions(),
            'smsTemplates' => SmsTemplate::query()->where('is_active', true)->orderBy('title')->get(),
            'allSmsTemplates' => SmsTemplate::query()->with('createdBy')->latest()->get(),
            'targetOptions' => $targetOptions,
            'users' => User::query()->whereHas('member')->orderBy('name')->get(),
            'myPurchases' => SmsPurchase::query()
                ->with(['wallet.department', 'wallet.user', 'approvedBy'])
                ->where('requested_by_user_id', Auth::id())
                ->latest()
                ->limit(15)
                ->get(),
            'purchases' => SmsPurchase::query()
                ->with(['wallet.department', 'wallet.user', 'requestedBy', 'approvedBy'])
                ->when(! $this->canApproveSms(), fn ($query) => $query->whereIn('sms_wallet_id', $walletIds))
                ->latest()
                ->limit(25)
                ->get(),
            'campaigns' => SmsCampaign::query()
                ->with(['wallet.department', 'wallet.user', 'sentBy', 'scheduledBy', 'department', 'template', 'logs'])
                ->whereIn('sms_wallet_id', $walletIds)
                ->latest()
                ->limit(25)
                ->get(),
            'scheduledCampaigns' => SmsCampaign::query()
                ->with(['wallet.department', 'wallet.user', 'sentBy', 'scheduledBy', 'department', 'template', 'logs'])
                ->whereIn('sms_wallet_id', $walletIds)
                ->where('status', SmsCampaign::STATUS_SCHEDULED)
                ->orderBy('scheduled_at')
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
            'preview' => $this->composePreview($resolver, $counter, $dispatcher),
            'canBuySms' => $this->canBuySms(),
            'canComposeSms' => $this->canComposeSms(),
            'canApproveSms' => $this->canApproveSms(),
            'canManageSmsWallets' => $this->canManageSmsWallets(),
            'canManageSmsTemplates' => $this->canManageSmsTemplates(),
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
            'compose_target_type' => ['required', Rule::in(['all_members', 'visitors', 'department_members', 'single_member', 'custom_members', 'manual_recipients'])],
            'compose_department_id' => ['nullable', 'required_if:compose_target_type,department_members', 'integer', Rule::exists('departments', 'id')],
            'compose_member_id' => ['nullable', 'required_if:compose_target_type,single_member', 'integer', Rule::exists('members', 'id')],
            'compose_member_ids' => [Rule::requiredIf($this->compose_target_type === 'custom_members'), 'array', 'max:500'],
            'compose_member_ids.*' => ['integer', Rule::exists('members', 'id')],
            'compose_manual_recipients' => ['nullable', 'required_if:compose_target_type,manual_recipients', 'string', 'max:10000'],
            'compose_message' => ['required', 'string', 'max:2000'],
            'compose_template_id' => ['nullable', 'integer', Rule::exists('sms_templates', 'id')],
            'compose_personalization_enabled' => ['boolean'],
            'compose_send_mode' => ['required', Rule::in(['now', 'scheduled'])],
            'compose_scheduled_at' => ['nullable', 'required_if:compose_send_mode,scheduled', 'date', 'after:now'],
        ]);
    }

    private function resetComposeForm(): void
    {
        $this->reset(['compose_title', 'compose_message', 'compose_department_id', 'compose_member_id', 'compose_member_ids', 'compose_manual_recipients', 'compose_template_id', 'compose_scheduled_at']);
        $this->compose_target_type = 'all_members';
        $this->compose_personalization_enabled = false;
        $this->compose_send_mode = 'now';
        $this->resetErrorBag();
    }

    private function resetTemplateForm(): void
    {
        $this->reset(['editing_template_id', 'template_title', 'template_message']);
        $this->template_is_active = true;
        $this->resetErrorBag();
    }

    private function scheduledAtFromInput(string $sendMode, ?string $scheduledAt): ?Carbon
    {
        if ($sendMode !== 'scheduled' || ! $scheduledAt) {
            return null;
        }

        return Carbon::parse($scheduledAt, config('app.timezone'));
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
            $options['single_member'] = __('messages.sms_target_single_member');
            $options['custom_members'] = __('messages.sms_target_custom_members');
            $options['manual_recipients'] = __('messages.sms_target_manual_recipients');
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

    private function recipientMemberOptions()
    {
        return Member::query()
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
    private function composePreview(SmsRecipientResolver $resolver, SmsMessageCounter $counter, SmsCampaignDispatcher $dispatcher): array
    {
        try {
            return $this->campaignPreview($resolver, $counter, $dispatcher);
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
    private function campaignPreview(SmsRecipientResolver $resolver, SmsMessageCounter $counter, SmsCampaignDispatcher $dispatcher): array
    {
        $wallet = SmsWallet::find((int) $this->compose_wallet_id);
        $recipients = $this->resolveRecipients($resolver);
        $estimate = $dispatcher->estimate($this->compose_message, $recipients, $this->compose_personalization_enabled);
        $balance = (int) ($wallet?->balance ?? 0);

        return [
            'recipients_count' => $estimate['recipients_count'],
            'sms_parts' => $estimate['sms_parts'],
            'credits_required' => $estimate['credits_required'],
            'balance_before' => $balance,
            'balance_after' => max($balance - $estimate['credits_required'], 0),
        ];
    }

    private function resolveRecipients(SmsRecipientResolver $resolver)
    {
        return $resolver->resolve(
            Auth::user(),
            $this->compose_target_type,
            $this->compose_department_id !== '' ? (int) $this->compose_department_id : null,
            $this->compose_target_type === 'single_member'
                ? [$this->compose_member_id]
                : $this->compose_member_ids,
            $this->compose_manual_recipients,
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
            'templates' => $this->canManageSmsTemplates(),
            'scheduled' => $this->canViewScheduledSms(),
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

    private function canManageSmsTemplates(): bool
    {
        return $this->permissionsAreUnseeded() || (Auth::user()?->can('sms.templates') ?? false);
    }

    private function canViewScheduledSms(): bool
    {
        return $this->permissionsAreUnseeded()
            || (Auth::user()?->can('sms.scheduled') ?? false)
            || $this->canComposeSms();
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
            'templates' => __('messages.sms_templates'),
            'scheduled' => __('messages.sms_scheduled_campaigns'),
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
