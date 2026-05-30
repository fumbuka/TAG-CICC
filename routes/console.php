<?php

use App\Models\MemberLeadershipAssignment;
use App\Support\LeadershipSystemAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('leadership:grant-access', function (LeadershipSystemAccess $systemAccess): void {
    $today = now()->toDateString();
    $leaders = [];

    MemberLeadershipAssignment::query()
        ->with(['member.user', 'leadershipTitle'])
        ->where('is_active', true)
        ->where(function (Builder $query) use ($today): void {
            $query->whereNull('started_at')
                ->orWhereDate('started_at', '<=', $today);
        })
        ->where(function (Builder $query) use ($today): void {
            $query->whereNull('ended_at')
                ->orWhereDate('ended_at', '>=', $today);
        })
        ->get()
        ->each(function (MemberLeadershipAssignment $assignment) use ($systemAccess, &$leaders): void {
            if (! $assignment->member || ! $assignment->leadershipTitle) {
                return;
            }

            $credentials = $systemAccess->grant($assignment->member, $assignment->leadershipTitle);
            $key = $assignment->member->id;

            if (! isset($leaders[$key])) {
                $leaders[$key] = [
                    'name' => $credentials['user']->name,
                    'roles' => [],
                    'email' => $credentials['email'],
                    'phone_number' => $credentials['phone_number'] ?: '-',
                    'password' => $credentials['password'] ?: __('messages.existing_password_unchanged'),
                ];
            }

            $leaders[$key]['roles'][] = $credentials['role_name'];

            if ($credentials['password']) {
                $leaders[$key]['password'] = $credentials['password'];
            }
        });

    $rows = collect($leaders)
        ->map(fn (array $leader): array => [
            $leader['name'],
            collect($leader['roles'])->unique()->join(', '),
            $leader['email'],
            $leader['phone_number'],
            $leader['password'],
        ])
        ->values()
        ->all();

    $this->table(['Name', 'Roles', 'Email', 'Phone', 'Temporary password'], $rows);
    $this->info(count($rows).' active leader account(s) prepared.');
})->purpose('Create or update login access for active leadership assignments');
