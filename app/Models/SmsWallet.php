<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'owner_type',
    'department_id',
    'user_id',
    'name',
    'credits_purchased',
    'credits_used',
    'balance',
    'is_active',
])]
class SmsWallet extends Model
{
    public const OWNER_CHURCH = 'church';

    public const OWNER_DEPARTMENT = 'department';

    public const OWNER_USER = 'user';

    protected function casts(): array
    {
        return [
            'credits_purchased' => 'integer',
            'credits_used' => 'integer',
            'balance' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(SmsPurchase::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(SmsCampaign::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SmsTransaction::class);
    }

    public function ownerLabel(): string
    {
        return match ($this->owner_type) {
            self::OWNER_DEPARTMENT => $this->department?->name ?? __('messages.department'),
            self::OWNER_USER => $this->user?->name ?? __('messages.user'),
            default => __('messages.church_scope'),
        };
    }
}
