<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'income_category_id',
    'service_id',
    'department_id',
    'zone_id',
    'recorded_by_user_id',
    'amount',
    'transaction_date',
    'reference_number',
    'notes',
])]
class FinancialTransaction extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }
}
