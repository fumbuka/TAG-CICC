<?php

namespace App\Services\Sms;

use Illuminate\Support\Str;

class SmsMessageCounter
{
    public function parts(string $message): int
    {
        $length = Str::length($message);

        if ($length === 0) {
            return 0;
        }

        if (! $this->isGsmText($message)) {
            return $length <= 70 ? 1 : (int) ceil($length / 67);
        }

        return $length <= 160 ? 1 : (int) ceil($length / 153);
    }

    private function isGsmText(string $message): bool
    {
        return preg_match('/^[\r\n A-Za-z0-9@£$¥èéùìòÇØøÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ!"#¤%&\'()*+,\-.\/:;<=>?¡ÄÖÑÜ§¿äöñüà^{}\\\\\[~\]|€]*$/u', $message) === 1;
    }
}
