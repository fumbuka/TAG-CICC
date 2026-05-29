<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'calendar_event_id',
    'department_id',
    'submitted_by_user_id',
    'reviewed_by_user_id',
    'report_date',
    'attendance_count',
    'status',
    'summary',
    'achievements',
    'challenges',
    'recommendations',
    'review_notes',
    'reviewed_at',
])]
class DepartmentEventReport extends Model
{
    use HasFactory;

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'attendance_count' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }
}
