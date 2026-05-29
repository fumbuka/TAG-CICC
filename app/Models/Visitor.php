<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'converted_member_id',
    'full_name',
    'phone_number',
    'residential_area',
    'visited_at',
    'invited_by',
    'follow_up_status',
    'assigned_to_user_id',
    'notes',
])]
class Visitor extends Model
{
    use HasFactory;

    public function convertedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'converted_member_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    protected function casts(): array
    {
        return [
            'visited_at' => 'date',
        ];
    }
}
