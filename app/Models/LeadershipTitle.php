<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'scope', 'description', 'is_active'])]
class LeadershipTitle extends Model
{
    use HasFactory;

    public function assignments(): HasMany
    {
        return $this->hasMany(MemberLeadershipAssignment::class);
    }
}
