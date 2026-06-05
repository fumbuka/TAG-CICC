<?php

namespace App\Services\Sms;

use App\Models\Department;
use App\Models\Member;
use App\Models\User;
use App\Models\Visitor;
use App\Support\UserDataScope;
use Illuminate\Support\Collection;
use RuntimeException;

class SmsRecipientResolver
{
    /**
     * @return Collection<int, array{name: string, phone_number: string, member_id?: int|null, visitor_id?: int|null}>
     */
    public function resolve(User $user, string $targetType, ?int $departmentId = null, array $memberIds = [], string $manualRecipients = ''): Collection
    {
        $scope = UserDataScope::for($user);

        return match ($targetType) {
            'all_members' => $this->resolveAllMembers($user, $scope),
            'visitors' => $this->resolveVisitors($user, $scope),
            'department_members' => $this->resolveDepartmentMembers($scope, $departmentId),
            'single_member' => $this->resolveCustomMembers($memberIds),
            'custom_members' => $this->resolveCustomMembers($memberIds),
            'manual_recipients' => $this->resolveManualRecipients($manualRecipients),
            default => collect(),
        };
    }

    private function resolveAllMembers(User $user, UserDataScope $scope): Collection
    {
        if (! $scope->isChurchWide()) {
            throw new RuntimeException(__('messages.sms_recipient_scope_denied'));
        }

        return $this->memberRows(Member::query()
            ->with(['departments', 'zone'])
            ->where('membership_status', 'active')
            ->whereNotNull('phone_number')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get());
    }

    private function resolveVisitors(User $user, UserDataScope $scope): Collection
    {
        if (! $scope->isChurchWide()) {
            throw new RuntimeException(__('messages.sms_recipient_scope_denied'));
        }

        return Visitor::query()
            ->whereNotNull('phone_number')
            ->latest('visited_at')
            ->get()
            ->map(fn (Visitor $visitor): array => [
                'name' => $visitor->full_name,
                'first_name' => strtok((string) $visitor->full_name, ' ') ?: $visitor->full_name,
                'last_name' => '',
                'phone_number' => $this->normalizePhone($visitor->phone_number),
                'member_id' => null,
                'visitor_id' => $visitor->id,
                'department_names' => '',
                'zone_name' => '',
            ])
            ->filter(fn (array $row): bool => $row['phone_number'] !== '')
            ->unique('phone_number')
            ->values();
    }

    private function resolveDepartmentMembers(UserDataScope $scope, ?int $departmentId): Collection
    {
        if (! $departmentId) {
            throw new RuntimeException(__('messages.sms_department_required'));
        }

        if (! $scope->isChurchWide() && ! in_array($departmentId, $scope->departmentIds(), true)) {
            throw new RuntimeException(__('messages.sms_recipient_scope_denied'));
        }

        Department::query()->findOrFail($departmentId);

        return $this->memberRows(Member::query()
            ->with(['departments', 'zone'])
            ->where('membership_status', 'active')
            ->whereNotNull('phone_number')
            ->whereHas('departments', function ($query) use ($departmentId): void {
                $query->where('departments.id', $departmentId)
                    ->where('member_departments.is_active', true);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get());
    }

    /**
     * @param  array<int, int|string>  $memberIds
     * @return Collection<int, array{name: string, phone_number: string, member_id: int}>
     */
    private function resolveCustomMembers(array $memberIds): Collection
    {
        $memberIds = collect($memberIds)
            ->map(fn (int|string $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($memberIds->isEmpty()) {
            throw new RuntimeException(__('messages.sms_custom_recipients_required'));
        }

        $accessibleMembersQuery = Member::query()
            ->with(['departments', 'zone'])
            ->whereKey($memberIds->all())
            ->where('membership_status', 'active');

        return $this->memberRows($accessibleMembersQuery
            ->whereNotNull('phone_number')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get());
    }

    /**
     * @return Collection<int, array{name: string, phone_number: string, member_id: null, visitor_id: null}>
     */
    private function resolveManualRecipients(string $manualRecipients): Collection
    {
        $rows = collect(preg_split('/\R+/', trim($manualRecipients)) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->map(function (string $line): ?array {
                preg_match('/(\+?255\d{9}|0\d{9})/', $line, $matches);

                $rawPhone = $matches[1] ?? $line;
                $phone = $this->normalizePhone($rawPhone);

                if ($phone === '') {
                    return null;
                }

                $name = trim(str_replace($rawPhone, '', $line), " \t\n\r\0\x0B,-:");

                return [
                    'name' => $name !== '' ? $name : __('messages.sms_manual_recipient'),
                    'first_name' => $name !== '' ? (strtok($name, ' ') ?: $name) : '',
                    'last_name' => '',
                    'phone_number' => $phone,
                    'member_id' => null,
                    'visitor_id' => null,
                    'department_names' => '',
                    'zone_name' => '',
                ];
            })
            ->filter()
            ->unique('phone_number')
            ->values();

        if ($rows->isEmpty()) {
            throw new RuntimeException(__('messages.sms_manual_recipients_required'));
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Member>  $members
     * @return Collection<int, array{name: string, phone_number: string, member_id: int}>
     */
    private function memberRows(Collection $members): Collection
    {
        return $members
            ->map(fn (Member $member): array => [
                'name' => $member->fullName(),
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'phone_number' => $this->normalizePhone($member->phone_number),
                'member_id' => $member->id,
                'visitor_id' => null,
                'department_names' => $member->departments
                    ->where('pivot.is_active', true)
                    ->pluck('name')
                    ->join(', '),
                'zone_name' => $member->zone?->name ?? '',
            ])
            ->filter(fn (array $row): bool => $row['phone_number'] !== '')
            ->unique('phone_number')
            ->values();
    }

    private function normalizePhone(?string $phone): string
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);

        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            return '255'.substr($phone, 1);
        }

        if (str_starts_with($phone, '255')) {
            return $phone;
        }

        return $phone;
    }
}
