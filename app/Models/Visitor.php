<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected function casts(): array
    {
        return [
            'visited_at' => 'date',
        ];
    }
}
