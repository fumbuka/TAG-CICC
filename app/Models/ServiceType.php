<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'allows_tithe', 'is_active'])]
class ServiceType extends Model
{
    use HasFactory;

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
