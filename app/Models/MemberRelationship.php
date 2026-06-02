<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_id',
    'related_member_id',
    'relationship_type',
    'created_by_user_id',
    'notes',
])]
class MemberRelationship extends Model
{
    use HasFactory;

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function relatedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'related_member_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
