<?php

namespace App\Http\Controllers;

use App\Models\SmsLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsDeliveryCallbackController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorizeCallback($request);

        $payload = $request->all();
        $messageId = $this->firstPayloadValue($payload, [
            'message_id',
            'messageId',
            'messageid',
            'beem_message_id',
            'data.message_id',
            'data.messageId',
            'message.message_id',
            'message.messageId',
            'results.0.message_id',
            'results.0.messageId',
        ]);
        $phone = $this->normalizePhone($this->firstPayloadValue($payload, [
            'dest_addr',
            'phone_number',
            'phone',
            'recipient',
            'data.dest_addr',
            'data.phone_number',
            'message.dest_addr',
            'results.0.dest_addr',
        ]));
        $providerStatus = $this->firstPayloadValue($payload, [
            'status',
            'delivery_status',
            'message_status',
            'data.status',
            'message.status',
            'results.0.status',
        ]) ?: 'updated';

        $query = SmsLog::query();

        if ($messageId) {
            $query->where('beem_message_id', $messageId);
        } elseif ($phone) {
            $query->where('phone_number', $phone)
                ->latest();
        } else {
            return response()->json(['ok' => false, 'message' => 'No message id or phone number supplied.'], 422);
        }

        $log = $query->first();

        if (! $log) {
            return response()->json(['ok' => false, 'message' => 'SMS log not found.'], 404);
        }

        $mappedStatus = $this->mapStatus((string) $providerStatus);

        $log->update([
            'status' => $mappedStatus,
            'provider_status' => (string) $providerStatus,
            'provider_status_updated_at' => now(),
            'delivered_at' => $mappedStatus === SmsLog::STATUS_DELIVERED ? now() : $log->delivered_at,
            'provider_response' => $payload,
        ]);

        return response()->json(['ok' => true]);
    }

    private function authorizeCallback(Request $request): void
    {
        $expectedToken = config('sms.beem.callback_token');

        if (! $expectedToken) {
            return;
        }

        $providedToken = $request->bearerToken()
            ?: $request->header('X-Beem-Token')
            ?: $request->query('token');

        abort_unless(is_string($providedToken) && hash_equals((string) $expectedToken, $providedToken), 403);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $paths
     */
    private function firstPayloadValue(array $payload, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    private function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'delivered', 'delivery_success', 'success', 'successful' => SmsLog::STATUS_DELIVERED,
            'failed', 'failure', 'undelivered', 'expired', 'rejected', 'delivery_failed' => SmsLog::STATUS_UNDELIVERED,
            'pending', 'queued' => SmsLog::STATUS_PENDING,
            default => SmsLog::STATUS_SENT,
        };
    }

    private function normalizePhone(?string $phone): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);

        if ($phone === '') {
            return null;
        }

        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            return '255'.substr($phone, 1);
        }

        return $phone;
    }
}
