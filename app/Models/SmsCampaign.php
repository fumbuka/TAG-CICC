<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sms_wallet_id',
    'sent_by_user_id',
    'scheduled_by_user_id',
    'department_id',
    'sms_template_id',
    'title',
    'target_type',
    'message',
    'personalization_enabled',
    'recipients_count',
    'sms_parts',
    'total_credits_used',
    'status',
    'beem_response',
    'scheduled_at',
    'last_attempted_at',
    'sent_at',
])]
class SmsCampaign extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_SENT = 'sent';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    public const STATUS_BLOCKED = 'blocked';

    protected function casts(): array
    {
        return [
            'recipients_count' => 'integer',
            'sms_parts' => 'integer',
            'total_credits_used' => 'integer',
            'personalization_enabled' => 'boolean',
            'beem_response' => 'array',
            'scheduled_at' => 'datetime',
            'last_attempted_at' => 'datetime',
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

    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class, 'sms_template_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }
}
