<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sms_wallet_id',
    'requested_by_user_id',
    'approved_by_user_id',
    'sms_quantity',
    'price_per_sms',
    'total_amount',
    'status',
    'payment_reference',
    'notes',
    'decided_at',
    'paid_at',
])]
class SmsPurchase extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PAID = 'paid';

    protected function casts(): array
    {
        return [
            'sms_quantity' => 'integer',
            'price_per_sms' => 'integer',
            'total_amount' => 'integer',
            'decided_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(SmsWallet::class, 'sms_wallet_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
