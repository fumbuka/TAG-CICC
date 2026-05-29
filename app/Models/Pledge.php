<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'member_id',
    'income_category_id',
    'service_id',
    'department_id',
    'zone_id',
    'recorded_by_user_id',
    'donor_name',
    'pledged_amount',
    'pledged_at',
    'due_date',
    'status',
    'notes',
])]
class Pledge extends Model
{
    use HasFactory;

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function incomeCategory(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PledgePayment::class);
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    public function paidAmount(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function balanceAmount(): float
    {
        return max((float) $this->pledged_amount - $this->paidAmount(), 0);
    }

    public function progressPercentage(): int
    {
        if ((float) $this->pledged_amount <= 0.0) {
            return 0;
        }

        return min((int) round(($this->paidAmount() / (float) $this->pledged_amount) * 100), 100);
    }

    protected function casts(): array
    {
        return [
            'pledged_amount' => 'decimal:2',
            'pledged_at' => 'date',
            'due_date' => 'date',
        ];
    }
}
