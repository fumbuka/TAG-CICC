<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Member;
use App\Models\User;

class MemberDepartmentAssignmentService
{
    public function assignDefaultDepartments(Member $member, ?User $assignedBy = null): void
    {
        $age = $member->age();

        if ($age === null) {
            return;
        }

        $departmentSlugs = match (true) {
            $age < 18 => ['watoto'],
            $age >= 18 && $age <= 25 => ['vijana'],
            $age > 25 && $member->gender === 'female' => ['wamama'],
            $age > 25 && $member->gender === 'male' => ['wababa'],
            default => [],
        };

        Department::query()
            ->whereIn('slug', $departmentSlugs)
            ->get()
            ->each(function (Department $department) use ($member, $assignedBy): void {
                $member->departments()->syncWithoutDetaching([
                    $department->id => [
                        'assigned_by_user_id' => $assignedBy?->id,
                        'assignment_source' => 'automatic',
                        'started_at' => now()->toDateString(),
                        'is_active' => true,
                    ],
                ]);
            });
    }
}
