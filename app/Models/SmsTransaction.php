<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sms_wallet_id',
    'transaction_type',
    'credits_in',
    'credits_out',
    'balance_before',
    'balance_after',
    'description',
    'performed_by_user_id',
])]
class SmsTransaction extends Model
{
    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_USAGE = 'usage';

    public const TYPE_REFUND = 'refund';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected function casts(): array
    {
        return [
            'credits_in' => 'integer',
            'credits_out' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(SmsWallet::class, 'sms_wallet_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
