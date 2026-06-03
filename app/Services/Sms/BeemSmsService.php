<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class BeemSmsService
{
    /**
     * @param  array<int, array{name: string, phone_number: string}>  $recipients
     * @return array<string, mixed>
     */
    public function send(string $message, array $recipients, ?string $senderId = null): array
    {
        $apiKey = config('sms.beem.api_key');
        $secretKey = config('sms.beem.secret_key');
        $senderId = $senderId ?: config('sms.beem.sender_id');

        if (! $apiKey || ! $secretKey || ! $senderId) {
            throw new RuntimeException(__('messages.sms_beem_credentials_missing'));
        }

        $payload = [
            'source_addr' => $senderId,
            'encoding' => 0,
            'message' => $message,
            'recipients' => collect($recipients)
                ->values()
                ->map(fn (array $recipient, int $index): array => [
                    'recipient_id' => $index + 1,
                    'dest_addr' => $recipient['phone_number'],
                ])
                ->all(),
        ];

        $response = Http::withBasicAuth((string) $apiKey, (string) $secretKey)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->post(rtrim((string) config('sms.beem.base_url'), '/').'/v1/send', $payload);

        if ($response->failed()) {
            throw new RuntimeException($response->json('message') ?: $response->body() ?: __('messages.sms_beem_send_failed'));
        }

        return $response->json() ?: ['successful' => true];
    }
}
