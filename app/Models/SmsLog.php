<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sms_campaign_id',
    'member_id',
    'visitor_id',
    'recipient_name',
    'phone_number',
    'message',
    'status',
    'provider_status',
    'provider_status_updated_at',
    'delivered_at',
    'beem_message_id',
    'error_message',
    'provider_response',
])]
class SmsLog extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_UNDELIVERED = 'undelivered';

    public const STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'provider_response' => 'array',
            'provider_status_updated_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SmsCampaign::class, 'sms_campaign_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }
}
