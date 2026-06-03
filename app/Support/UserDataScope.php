<?php

namespace App\Support;

use App\Models\Department;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Builder;

class UserDataScope
{
    /**
     * @param  array<int, int>  $departmentIds
     * @param  array<int, int>  $zoneIds
     */
    private function __construct(
        private readonly User $user,
        private readonly ?int $memberId,
        private readonly array $departmentIds,
        private readonly array $zoneIds,
        private readonly bool $churchWide,
        private readonly bool $financeChurchWide,
    ) {}

    public static function for(User $user): self
    {
        $today = now()->startOfDay();
        $member = $user->member;
        $assignments = $member
            ? $member->leadershipAssignments()
                ->with('leadershipTitle')
                ->where('is_active', true)
                ->where(function (Builder $query) use ($today): void {
                    $query->whereNull('started_at')
                        ->orWhereDate('started_at', '<=', $today->toDateString());
                })
                ->where(function (Builder $query) use ($today): void {
                    $query->whereNull('ended_at')
                        ->orWhereDate('ended_at', '>=', $today->toDateString());
                })
                ->get()
            : collect();

        $departmentIds = $assignments
            ->pluck('department_id')
            ->filter()
            ->unique()
            ->values()
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $zoneIds = $assignments
            ->pluck('zone_id')
            ->filter()
            ->unique()
            ->values()
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $hasChurchLeadership = $assignments->contains(function ($assignment): bool {
            return $assignment->leadershipTitle?->scope === 'church'
                && in_array($assignment->leadershipTitle?->slug, [
                    'mchungaji-kiongozi',
                    'katibu-wa-kanisa',
                ], true);
        });

        $hasChurchFinance = $assignments->contains(function ($assignment): bool {
            return $assignment->leadershipTitle?->scope === 'church'
                && $assignment->leadershipTitle?->slug === 'mhasibu-wa-kanisa';
        });

        $churchWide = $hasChurchLeadership || $user->hasAnyRole([
            'Super Admin',
            'Mchungaji Kiongozi',
            'Katibu wa Kanisa',
        ]) || $user->can('users.manage')
            || $user->can('leadership.manage');

        $financeChurchWide = $churchWide
            || $hasChurchFinance
            || $user->hasRole('Mhasibu wa Kanisa')
            || (($user->can('finance.view') || $user->can('finance.record'))
                && $departmentIds === []
                && $zoneIds === []);

        return new self(
            user: $user,
            memberId: $member?->id,
            departmentIds: $departmentIds,
            zoneIds: $zoneIds,
            churchWide: $churchWide,
            financeChurchWide: $financeChurchWide,
        );
    }

    public function label(): string
    {
        if ($this->churchWide) {
            return __('messages.dashboard_scope_all_church');
        }

        $parts = [];

        if ($this->departmentIds !== []) {
            $parts[] = __('messages.dashboard_scope_departments', [
                'departments' => Department::query()
                    ->whereIn('id', $this->departmentIds)
                    ->orderBy('name')
                    ->pluck('name')
                    ->join(', '),
            ]);
        }

        if ($this->zoneIds !== []) {
            $parts[] = __('messages.dashboard_scope_zones', [
                'zones' => Zone::query()
                    ->whereIn('id', $this->zoneIds)
                    ->orderBy('name')
                    ->pluck('name')
                    ->join(', '),
            ]);
        }

        return $parts !== [] ? implode(' | ', $parts) : __('messages.dashboard_scope_personal');
    }

    public function canSeeFinance(): bool
    {
        return $this->user->can('finance.view') || $this->user->can('finance.record');
    }

    public function isChurchWide(): bool
    {
        return $this->churchWide;
    }

    /**
     * @return array<int, int>
     */
    public function departmentIds(): array
    {
        return $this->departmentIds;
    }

    /**
     * @return array<int, int>
     */
    public function zoneIds(): array
    {
        return $this->zoneIds;
    }

    public function applyMemberScope(Builder $query): Builder
    {
        if ($this->churchWide) {
            return $query;
        }

        if ($this->hasLeadershipScope()) {
            return $query->where(function (Builder $query): void {
                $query->when($this->departmentIds !== [], function (Builder $query): void {
                    $query->whereHas('departments', function (Builder $query): void {
                        $query->whereIn('departments.id', $this->departmentIds)
                            ->where('member_departments.is_active', true);
                    });
                });

                $query->when($this->zoneIds !== [], function (Builder $query): void {
                    $method = $this->departmentIds !== [] ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('zone_id', $this->zoneIds);
                });
            });
        }

        return $this->memberId
            ? $query->whereKey($this->memberId)
            : $this->emptyQuery($query);
    }

    public function applyDepartmentScope(Builder $query): Builder
    {
        if ($this->churchWide) {
            return $query;
        }

        return $this->departmentIds !== []
            ? $query->whereIn('id', $this->departmentIds)
            : $this->emptyQuery($query);
    }

    public function applyZoneScope(Builder $query): Builder
    {
        if ($this->churchWide) {
            return $query;
        }

        return $this->zoneIds !== []
            ? $query->whereIn('id', $this->zoneIds)
            : $this->emptyQuery($query);
    }

    public function applyServiceScope(Builder $query): Builder
    {
        if ($this->churchWide) {
            return $query;
        }

        if ($this->hasLeadershipScope()) {
            return $query->where(function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->whereNull('department_id')
                        ->whereNull('zone_id');
                });

                $query->when($this->departmentIds !== [], function (Builder $query): void {
                    $query->orWhereIn('department_id', $this->departmentIds);
                });

                $query->when($this->zoneIds !== [], function (Builder $query): void {
                    $query->orWhereIn('zone_id', $this->zoneIds);
                });
            });
        }

        return $this->emptyQuery($query);
    }

    public function applyFinanceScope(Builder $query): Builder
    {
        if ($this->financeChurchWide) {
            return $query;
        }

        if ($this->hasLeadershipScope()) {
            return $query->where(function (Builder $query): void {
                $query->when($this->departmentIds !== [], function (Builder $query): void {
                    $query->whereIn('department_id', $this->departmentIds);
                });

                $query->when($this->zoneIds !== [], function (Builder $query): void {
                    $method = $this->departmentIds !== [] ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('zone_id', $this->zoneIds);
                });
            });
        }

        return $this->emptyQuery($query);
    }

    public function applyFinanceDepartmentScope(Builder $query): Builder
    {
        if ($this->financeChurchWide) {
            return $query;
        }

        return $this->applyDepartmentScope($query);
    }

    public function applyFinanceZoneScope(Builder $query): Builder
    {
        if ($this->financeChurchWide) {
            return $query;
        }

        return $this->applyZoneScope($query);
    }

    public function applyFinanceMemberScope(Builder $query): Builder
    {
        if ($this->financeChurchWide) {
            return $query;
        }

        return $this->applyMemberScope($query);
    }

    public function applyFinanceServiceScope(Builder $query): Builder
    {
        if ($this->financeChurchWide) {
            return $query;
        }

        return $this->applyServiceScope($query);
    }

    public function applyPledgePaymentScope(Builder $query): Builder
    {
        if ($this->financeChurchWide) {
            return $query;
        }

        if ($this->hasLeadershipScope()) {
            return $query->whereHas('pledge', function (Builder $query): void {
                $this->applyFinanceScope($query);
            });
        }

        return $this->emptyQuery($query);
    }

    public function applyCalendarEventScope(Builder $query): Builder
    {
        if ($this->churchWide) {
            return $query;
        }

        if ($this->hasLeadershipScope()) {
            return $query->where(function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->whereNull('department_id')
                        ->whereNull('zone_id');
                });

                $query->when($this->departmentIds !== [], function (Builder $query): void {
                    $query->orWhereIn('department_id', $this->departmentIds);
                });

                $query->when($this->zoneIds !== [], function (Builder $query): void {
                    $query->orWhereIn('zone_id', $this->zoneIds);
                });
            });
        }

        return $query->whereNull('department_id')
            ->whereNull('zone_id');
    }

    private function hasLeadershipScope(): bool
    {
        return $this->departmentIds !== [] || $this->zoneIds !== [];
    }

    private function emptyQuery(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }
}
