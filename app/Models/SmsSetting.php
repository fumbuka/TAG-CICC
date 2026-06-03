<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'price_per_sms',
    'low_balance_threshold',
    'sender_id',
    'sending_enabled',
])]
class SmsSetting extends Model
{
    protected function casts(): array
    {
        return [
            'price_per_sms' => 'integer',
            'low_balance_threshold' => 'integer',
            'sending_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'price_per_sms' => 25,
            'low_balance_threshold' => 100,
            'sending_enabled' => false,
        ]);
    }
}
