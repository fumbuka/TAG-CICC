<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'week_start',
    'week_end',
    'elder_member_id',
    'deacon_member_id',
    'notes',
    'is_active',
])]
class WeeklyDuty extends Model
{
    use HasFactory;

    public function elder(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'elder_member_id');
    }

    public function deacon(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'deacon_member_id');
    }

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
