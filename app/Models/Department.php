<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'description', 'is_age_based', 'minimum_age', 'maximum_age', 'gender_rule', 'is_active'])]
class Department extends Model
{
    use HasFactory;

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_departments')
            ->withPivot(['assigned_by_user_id', 'assignment_source', 'started_at', 'ended_at', 'is_active'])
            ->withTimestamps();
    }
}
