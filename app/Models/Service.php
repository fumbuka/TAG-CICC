<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'service_type_id',
    'department_id',
    'zone_id',
    'title',
    'service_date',
    'starts_at',
    'ends_at',
    'speaker',
    'topic',
    'attendance_count',
    'notes',
])]
class Service extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
        ];
    }
}
