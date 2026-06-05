<?php

namespace App\Services\Sms;

class SmsMessageRenderer
{
    /**
     * @param  array<string, mixed>  $recipient
     */
    public function render(string $message, array $recipient): string
    {
        $replacements = [
            '{name}' => $recipient['name'] ?? '',
            '{first_name}' => $recipient['first_name'] ?? $this->firstName((string) ($recipient['name'] ?? '')),
            '{last_name}' => $recipient['last_name'] ?? $this->lastName((string) ($recipient['name'] ?? '')),
            '{phone}' => $recipient['phone_number'] ?? '',
            '{department}' => $recipient['department_names'] ?? '',
            '{zone}' => $recipient['zone_name'] ?? '',
        ];

        return strtr($message, $replacements);
    }

    public function hasPlaceholders(string $message): bool
    {
        return preg_match('/\{(name|first_name|last_name|phone|department|zone)\}/', $message) === 1;
    }

    private function firstName(string $name): string
    {
        return trim(strtok($name, ' ') ?: $name);
    }

    private function lastName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return count($parts) > 1 ? (string) end($parts) : '';
    }
}
