<?php

namespace App\Services\Sms;

use App\Models\SmsCampaign;
use App\Models\SmsLog;
use App\Models\SmsSetting;
use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SmsCampaignDispatcher
{
    public function __construct(
        private readonly BeemSmsService $beemSmsService,
        private readonly SmsMessageCounter $counter,
        private readonly SmsMessageRenderer $renderer,
        private readonly SmsWalletService $walletService,
    ) {}

    /**
     * @param  Collection<int, array<string, mixed>>  $recipients
     * @return array{recipients_count: int, sms_parts: int, credits_required: int}
     */
    public function estimate(string $message, Collection $recipients, bool $personalize): array
    {
        $rows = $this->recipientMessageRows($message, $recipients, $personalize);

        return [
            'recipients_count' => $rows->count(),
            'sms_parts' => (int) max($rows->max('sms_parts') ?: 0, 0),
            'credits_required' => (int) $rows->sum('sms_parts'),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $recipients
     */
    public function create(
        SmsWallet $wallet,
        User $user,
        string $title,
        string $targetType,
        string $message,
        Collection $recipients,
        ?int $departmentId = null,
        ?int $templateId = null,
        bool $personalize = false,
        ?CarbonInterface $scheduledAt = null,
    ): SmsCampaign {
        $rows = $this->recipientMessageRows($message, $recipients, $personalize);

        if ($rows->isEmpty()) {
            throw new RuntimeException(__('messages.sms_no_recipients'));
        }

        $campaign = DB::transaction(function () use ($wallet, $user, $title, $targetType, $message, $rows, $departmentId, $templateId, $personalize, $scheduledAt): SmsCampaign {
            $isScheduled = $scheduledAt && $scheduledAt->isFuture();
            $campaign = SmsCampaign::create([
                'sms_wallet_id' => $wallet->id,
                'sent_by_user_id' => $user->id,
                'scheduled_by_user_id' => $isScheduled ? $user->id : null,
                'department_id' => $departmentId,
                'sms_template_id' => $templateId,
                'title' => $title,
                'target_type' => $targetType,
                'message' => $message,
                'personalization_enabled' => $personalize,
                'recipients_count' => $rows->count(),
                'sms_parts' => (int) max($rows->max('sms_parts') ?: 1, 1),
                'total_credits_used' => 0,
                'status' => $isScheduled ? SmsCampaign::STATUS_SCHEDULED : SmsCampaign::STATUS_PENDING,
                'scheduled_at' => $isScheduled ? $scheduledAt : null,
            ]);

            $rows->each(fn (array $row): SmsLog => SmsLog::create([
                'sms_campaign_id' => $campaign->id,
                'member_id' => $row['member_id'] ?? null,
                'visitor_id' => $row['visitor_id'] ?? null,
                'recipient_name' => $row['name'],
                'phone_number' => $row['phone_number'],
                'message' => $row['message'],
                'status' => SmsLog::STATUS_PENDING,
            ]));

            return $campaign;
        });

        if (! $campaign->scheduled_at) {
            return $this->sendPendingCampaign($campaign, $user);
        }

        return $campaign;
    }

    public function sendPendingCampaign(SmsCampaign $campaign, ?User $performedBy = null): SmsCampaign
    {
        $logs = $campaign->logs()
            ->where('status', SmsLog::STATUS_PENDING)
            ->orderBy('id')
            ->get();

        return $this->sendLogs(
            $campaign,
            $logs,
            $performedBy,
            __('messages.sms_campaign_ledger_description', ['campaign' => $campaign->title]),
        );
    }

    public function retryFailedCampaign(SmsCampaign $campaign, ?User $performedBy = null): SmsCampaign
    {
        $logs = $campaign->logs()
            ->where('status', SmsLog::STATUS_FAILED)
            ->orderBy('id')
            ->get();

        return $this->sendLogs(
            $campaign,
            $logs,
            $performedBy,
            __('messages.sms_campaign_retry_ledger_description', ['campaign' => $campaign->title]),
        );
    }

    public function sendDueScheduledCampaigns(int $limit = 50): int
    {
        $sent = 0;

        SmsCampaign::query()
            ->with(['scheduledBy', 'wallet'])
            ->where('status', SmsCampaign::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get()
            ->each(function (SmsCampaign $campaign) use (&$sent): void {
                try {
                    $this->sendPendingCampaign($campaign, $campaign->scheduledBy ?? $campaign->sentBy);
                    $sent++;
                } catch (Throwable $exception) {
                    $campaign->update([
                        'status' => SmsCampaign::STATUS_FAILED,
                        'beem_response' => ['error' => $exception->getMessage()],
                        'last_attempted_at' => now(),
                    ]);

                    $campaign->logs()
                        ->where('status', SmsLog::STATUS_PENDING)
                        ->update([
                            'status' => SmsLog::STATUS_FAILED,
                            'provider_status' => SmsLog::STATUS_FAILED,
                            'provider_status_updated_at' => now(),
                            'error_message' => $exception->getMessage(),
                        ]);
                }
            });

        return $sent;
    }

    /**
     * @param  Collection<int, SmsLog>  $logs
     */
    private function sendLogs(SmsCampaign $campaign, Collection $logs, ?User $performedBy, string $ledgerDescription): SmsCampaign
    {
        if ($logs->isEmpty()) {
            return $campaign->refresh();
        }

        $settings = SmsSetting::current();

        if (! $settings->sending_enabled) {
            throw new RuntimeException(__('messages.sms_sending_disabled'));
        }

        $requiredCredits = (int) $logs->sum(fn (SmsLog $log): int => $this->counter->parts($log->message));
        $wallet = $campaign->wallet->refresh();

        if ($wallet->balance < $requiredCredits) {
            throw new RuntimeException(__('messages.sms_insufficient_balance_with_required', [
                'required' => $requiredCredits,
                'balance' => $wallet->balance,
            ]));
        }

        $responses = [];
        $creditsUsed = 0;

        $campaign->update(['last_attempted_at' => now()]);

        foreach ($logs->groupBy('message') as $message => $messageLogs) {
            try {
                $recipients = $messageLogs
                    ->values()
                    ->map(fn (SmsLog $log): array => [
                        'name' => $log->recipient_name,
                        'phone_number' => $log->phone_number,
                    ])
                    ->all();

                $providerResponse = $this->beemSmsService->send((string) $message, $recipients, $settings->sender_id);
                $groupCredits = (int) $messageLogs->sum(fn (SmsLog $log): int => $this->counter->parts($log->message));

                DB::transaction(function () use ($campaign, $messageLogs, $providerResponse, $wallet, $groupCredits, $performedBy, $ledgerDescription, &$creditsUsed): void {
                    $this->walletService->deductCredits(
                        $wallet,
                        $groupCredits,
                        $ledgerDescription,
                        $performedBy,
                        SmsTransaction::TYPE_USAGE,
                    );

                    $messageLogs->values()->each(fn (SmsLog $log, int $index): bool => $log->update([
                        'status' => SmsLog::STATUS_SENT,
                        'provider_status' => SmsLog::STATUS_SENT,
                        'provider_status_updated_at' => now(),
                        'beem_message_id' => $this->messageIdFromProviderResponse($providerResponse, $index),
                        'error_message' => null,
                        'provider_response' => $providerResponse,
                    ]));

                    $creditsUsed += $groupCredits;
                    $campaign->increment('total_credits_used', $groupCredits);
                });

                $responses[] = ['message' => $message, 'response' => $providerResponse];
            } catch (RuntimeException $exception) {
                $messageLogs->each(fn (SmsLog $log): bool => $log->update([
                    'status' => SmsLog::STATUS_FAILED,
                    'provider_status' => SmsLog::STATUS_FAILED,
                    'provider_status_updated_at' => now(),
                    'error_message' => $exception->getMessage(),
                ]));

                $responses[] = ['message' => $message, 'error' => $exception->getMessage()];
            }
        }

        $sentCount = $campaign->logs()->whereIn('status', [SmsLog::STATUS_SENT, SmsLog::STATUS_DELIVERED])->count();
        $failedCount = $campaign->logs()->where('status', SmsLog::STATUS_FAILED)->count();

        $campaign->update([
            'status' => match (true) {
                $sentCount > 0 && $failedCount > 0 => SmsCampaign::STATUS_PARTIAL,
                $sentCount > 0 => SmsCampaign::STATUS_SENT,
                default => SmsCampaign::STATUS_FAILED,
            },
            'beem_response' => $responses,
            'sent_at' => $sentCount > 0 ? now() : $campaign->sent_at,
        ]);

        return $campaign->refresh();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $recipients
     * @return Collection<int, array<string, mixed>>
     */
    private function recipientMessageRows(string $message, Collection $recipients, bool $personalize): Collection
    {
        return $recipients
            ->values()
            ->map(function (array $recipient) use ($message, $personalize): array {
                $renderedMessage = $personalize
                    ? $this->renderer->render($message, $recipient)
                    : $message;

                return [
                    ...$recipient,
                    'message' => $renderedMessage,
                    'sms_parts' => $this->counter->parts($renderedMessage),
                ];
            });
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
}
