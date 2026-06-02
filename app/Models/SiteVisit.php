<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'visitor_hash',
    'ip_hash',
    'user_agent_hash',
    'route_name',
    'path',
    'referrer_host',
    'visited_at',
])]
class SiteVisit extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }
}
