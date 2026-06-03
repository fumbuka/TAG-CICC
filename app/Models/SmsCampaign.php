<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sms_wallet_id',
    'sent_by_user_id',
    'department_id',
    'title',
    'target_type',
    'message',
    'recipients_count',
    'sms_parts',
    'total_credits_used',
    'status',
    'beem_response',
    'sent_at',
])]
class SmsCampaign extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_BLOCKED = 'blocked';

    protected function casts(): array
    {
        return [
            'recipients_count' => 'integer',
            'sms_parts' => 'integer',
            'total_credits_used' => 'integer',
            'beem_response' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(SmsWallet::class, 'sms_wallet_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }
}
