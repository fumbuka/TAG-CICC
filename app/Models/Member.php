<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'zone_id',
    'first_name',
    'middle_name',
    'last_name',
    'gender',
    'date_of_birth',
    'phone_number',
    'alternative_phone_number',
    'email',
    'residential_area',
    'marital_status',
    'baptism_status',
    'membership_status',
    'joined_at',
    'source',
    'emergency_contact_name',
    'emergency_contact_phone',
    'notes',
])]
class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joined_at' => 'date',
        ];
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'member_departments')
            ->withPivot(['assigned_by_user_id', 'assignment_source', 'started_at', 'ended_at', 'is_active'])
            ->withTimestamps();
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function leadershipAssignments(): HasMany
    {
        return $this->hasMany(MemberLeadershipAssignment::class);
    }

    public function age(): ?int
    {
        if (! $this->date_of_birth instanceof CarbonInterface) {
            return null;
        }

        return $this->date_of_birth->age;
    }
}
