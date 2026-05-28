<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'department_id',
    'zone_id',
    'title',
    'event_date',
    'starts_at',
    'ends_at',
    'description',
    'is_important',
    'is_active',
])]
class CalendarEvent extends Model
{
    use HasFactory;

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_important' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
