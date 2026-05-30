<?php

namespace App\Support;

use App\Models\LeadershipTitle;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LeadershipSystemAccess
{
    private const TITLE_ROLE_MAP = [
        'mchungaji-kiongozi' => 'Mchungaji Kiongozi',
        'katibu-wa-kanisa' => 'Katibu wa Kanisa',
        'mhasibu-wa-kanisa' => 'Mhasibu wa Kanisa',
        'mkurugenzi-wa-idara' => 'Mkurugenzi wa Idara',
        'makamu-mkurugenzi-wa-idara' => 'Makamu Mkurugenzi wa Idara',
        'katibu-wa-idara' => 'Katibu wa Idara',
        'makamu-katibu-wa-idara' => 'Makamu Katibu wa Idara',
        'mweka-hazina-wa-idara' => 'Mweka Hazina wa Idara',
        'kiongozi-wa-kanda' => 'Kiongozi wa Kanda',
    ];

    private const ROLE_PERMISSIONS = [
        'Mchungaji Kiongozi' => [
            'dashboard.view',
            'members.view',
            'visitors.manage',
            'departments.manage',
            'zones.manage',
            'finance.view',
            'calendar.manage',
            'leadership.manage',
            'reports.view',
            'reports.approve',
        ],
        'Katibu wa Kanisa' => [
            'dashboard.view',
            'members.view',
            'members.create',
            'members.update',
            'members.import',
            'visitors.manage',
            'departments.manage',
            'zones.manage',
            'calendar.manage',
            'leadership.manage',
            'reports.view',
            'users.manage',
        ],
        'Mhasibu wa Kanisa' => [
            'dashboard.view',
            'finance.view',
            'finance.record',
        ],
        'Mkurugenzi wa Idara' => [
            'dashboard.view',
            'members.view',
            'services.manage',
            'reports.submit',
        ],
        'Makamu Mkurugenzi wa Idara' => [
            'dashboard.view',
            'members.view',
            'reports.submit',
        ],
        'Katibu wa Idara' => [
            'dashboard.view',
            'members.view',
            'calendar.submit',
            'reports.submit',
        ],
        'Makamu Katibu wa Idara' => [
            'dashboard.view',
            'members.view',
            'calendar.submit',
            'reports.submit',
        ],
        'Mweka Hazina wa Idara' => [
            'dashboard.view',
            'finance.view',
            'finance.record',
            'reports.submit',
        ],
        'Kiongozi wa Kanda' => [
            'dashboard.view',
            'members.view',
            'services.manage',
            'finance.record',
            'reports.submit',
        ],
        'Mshirika' => [
            'dashboard.view',
        ],
    ];

    /**
     * @return array{user: User, role_name: string, email: string, phone_number: ?string, password: ?string, created: bool}
     */
    public function grant(Member $member, LeadershipTitle $title): array
    {
        $roleName = $this->roleNameForTitle($title);
        $role = $this->ensureRole($roleName, $title);
        $user = $member->user;
        $temporaryPassword = null;
        $created = false;

        if (! $user) {
            $temporaryPassword = $this->temporaryPassword();
            $user = User::create([
                'name' => $this->memberName($member),
                'email' => $this->emailFor($member),
                'phone_number' => $this->phoneFor($member),
                'password' => Hash::make($temporaryPassword),
                'is_active' => true,
            ]);

            $member->update([
                'user_id' => $user->id,
                'email' => $member->email ?: $user->email,
                'phone_number' => $member->phone_number ?: $user->phone_number,
            ]);

            $created = true;
        } else {
            $attributes = [
                'name' => $this->memberName($member),
                'is_active' => true,
            ];

            $phone = $this->phoneFor($member);
            if ($phone && $user->phone_number !== $phone) {
                $attributes['phone_number'] = $phone;
            }

            $user->update($attributes);
        }

        $user->assignRole($role);

        return [
            'user' => $user,
            'role_name' => $roleName,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'password' => $temporaryPassword,
            'created' => $created,
        ];
    }

    public function roleNameForTitle(LeadershipTitle $title): string
    {
        return self::TITLE_ROLE_MAP[$title->slug] ?? $title->name;
    }

    private function ensureRole(string $roleName, LeadershipTitle $title): Role
    {
        $role = Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        collect($this->permissionsFor($roleName, $title))
            ->each(fn (string $permission) => Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]));

        $role->givePermissionTo($this->permissionsFor($roleName, $title));

        return $role;
    }

    /**
     * @return array<int, string>
     */
    private function permissionsFor(string $roleName, LeadershipTitle $title): array
    {
        if (array_key_exists($roleName, self::ROLE_PERMISSIONS)) {
            return self::ROLE_PERMISSIONS[$roleName];
        }

        return match ($title->scope) {
            'church' => ['dashboard.view', 'members.view', 'reports.view'],
            'department' => ['dashboard.view', 'members.view', 'reports.submit'],
            'zone' => ['dashboard.view', 'members.view', 'reports.submit'],
            default => self::ROLE_PERMISSIONS['Mshirika'],
        };
    }

    private function emailFor(Member $member): string
    {
        $memberEmail = trim((string) $member->email);

        if ($memberEmail !== '' && ! $this->emailExists($memberEmail, $member->user_id)) {
            return $memberEmail;
        }

        $base = Str::of($this->memberName($member))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/i', '.')
            ->trim('.')
            ->value() ?: 'member';

        $candidate = "{$base}@tag-cicc.or.tz";
        if (! $this->emailExists($candidate, $member->user_id)) {
            return $candidate;
        }

        $candidate = "{$base}.{$member->id}@tag-cicc.or.tz";
        if (! $this->emailExists($candidate, $member->user_id)) {
            return $candidate;
        }

        $suffix = 2;
        while ($this->emailExists("{$base}.{$member->id}.{$suffix}@tag-cicc.or.tz", $member->user_id)) {
            $suffix++;
        }

        return "{$base}.{$member->id}.{$suffix}@tag-cicc.or.tz";
    }

    private function phoneFor(Member $member): ?string
    {
        $phone = trim((string) $member->phone_number);

        if ($phone === '') {
            return null;
        }

        return User::query()
            ->where('phone_number', $phone)
            ->when($member->user_id, fn ($query) => $query->whereKeyNot($member->user_id))
            ->exists()
                ? null
                : $phone;
    }

    private function emailExists(string $email, ?int $ignoreUserId = null): bool
    {
        return User::query()
            ->where('email', $email)
            ->when($ignoreUserId, fn ($query) => $query->whereKeyNot($ignoreUserId))
            ->exists();
    }

    private function temporaryPassword(): string
    {
        return 'TagCicc#'.Str::upper(Str::random(8));
    }

    private function memberName(Member $member): string
    {
        return collect([$member->first_name, $member->middle_name, $member->last_name])
            ->filter()
            ->join(' ');
    }
}
