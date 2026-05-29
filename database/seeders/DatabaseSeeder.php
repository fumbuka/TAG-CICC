<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentPosition;
use App\Models\IncomeCategory;
use App\Models\LeadershipTitle;
use App\Models\Member;
use App\Models\ServiceRoutine;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = [
            'dashboard.view',
            'members.view',
            'members.create',
            'members.update',
            'members.import',
            'departments.manage',
            'zones.manage',
            'services.manage',
            'finance.view',
            'finance.record',
            'calendar.manage',
            'calendar.submit',
            'leadership.manage',
            'reports.view',
            'reports.submit',
            'reports.approve',
            'users.manage',
        ];

        collect($permissions)->each(fn (string $permission) => Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        $roles = [
            'Super Admin' => $permissions,
            'Mchungaji Kiongozi' => [
                'dashboard.view',
                'members.view',
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
            'Katibu wa Idara' => [
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

        collect($roles)->each(function (array $rolePermissions, string $roleName): void {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ])->syncPermissions($rolePermissions);
        });

        collect([
            ['name' => 'Watoto', 'slug' => 'watoto', 'is_age_based' => true, 'maximum_age' => 17],
            ['name' => 'Vijana', 'slug' => 'vijana', 'is_age_based' => true, 'minimum_age' => 18, 'maximum_age' => 25],
            ['name' => 'Wamama', 'slug' => 'wamama', 'minimum_age' => 26, 'gender_rule' => 'female'],
            ['name' => 'Wababa', 'slug' => 'wababa', 'minimum_age' => 26, 'gender_rule' => 'male'],
            ['name' => 'Maendeleo', 'slug' => 'maendeleo'],
            ['name' => 'Uinjilishaji', 'slug' => 'uinjilishaji'],
            ['name' => 'Sala na Maombezi', 'slug' => 'sala-na-maombezi'],
        ])->each(fn (array $department) => Department::firstOrCreate(['slug' => $department['slug']], $department));

        collect([
            ['name' => 'Mkurugenzi', 'slug' => 'mkurugenzi', 'sort_order' => 1],
            ['name' => 'Makamu Mkurugenzi', 'slug' => 'makamu-mkurugenzi', 'sort_order' => 2],
            ['name' => 'Katibu', 'slug' => 'katibu', 'sort_order' => 3],
            ['name' => 'Makamu Katibu', 'slug' => 'makamu-katibu', 'sort_order' => 4],
            ['name' => 'Mweka Hazina', 'slug' => 'mweka-hazina', 'sort_order' => 5],
        ])->each(fn (array $position) => DepartmentPosition::firstOrCreate(['slug' => $position['slug']], $position));

        collect([
            ['name' => 'Changombe', 'slug' => 'changombe'],
            ['name' => 'Kanisani', 'slug' => 'kanisani'],
            ['name' => 'Mbwanga', 'slug' => 'mbwanga'],
        ])->each(fn (array $zone) => Zone::firstOrCreate(['slug' => $zone['slug']], $zone));

        collect([
            ['name' => 'Mchungaji Kiongozi', 'slug' => 'mchungaji-kiongozi', 'scope' => 'church'],
            ['name' => 'Katibu wa Kanisa', 'slug' => 'katibu-wa-kanisa', 'scope' => 'church'],
            ['name' => 'Mhasibu wa Kanisa', 'slug' => 'mhasibu-wa-kanisa', 'scope' => 'church'],
            ['name' => 'Mkurugenzi wa Idara', 'slug' => 'mkurugenzi-wa-idara', 'scope' => 'department'],
            ['name' => 'Makamu Mkurugenzi wa Idara', 'slug' => 'makamu-mkurugenzi-wa-idara', 'scope' => 'department'],
            ['name' => 'Katibu wa Idara', 'slug' => 'katibu-wa-idara', 'scope' => 'department'],
            ['name' => 'Makamu Katibu wa Idara', 'slug' => 'makamu-katibu-wa-idara', 'scope' => 'department'],
            ['name' => 'Mweka Hazina wa Idara', 'slug' => 'mweka-hazina-wa-idara', 'scope' => 'department'],
            ['name' => 'Kiongozi wa Kanda', 'slug' => 'kiongozi-wa-kanda', 'scope' => 'zone'],
        ])->each(fn (array $title) => LeadershipTitle::firstOrCreate(['slug' => $title['slug']], $title));

        collect([
            ['name' => 'Ibada Kuu ya Jumapili', 'slug' => 'ibada-kuu-ya-jumapili', 'allows_tithe' => true],
            ['name' => 'Ibada ya Jumatano', 'slug' => 'ibada-ya-jumatano'],
            ['name' => 'Ibada ya Ijumaa', 'slug' => 'ibada-ya-ijumaa'],
            ['name' => 'Ibada ya Idara', 'slug' => 'ibada-ya-idara'],
            ['name' => 'Ibada ya Kanda', 'slug' => 'ibada-ya-zone'],
            ['name' => 'Tukio Maalum', 'slug' => 'tukio-maalum'],
        ])->each(fn (array $serviceType) => ServiceType::firstOrCreate(['slug' => $serviceType['slug']], $serviceType));

        collect([
            [
                'title' => 'Ibada Kuu ya Jumapili',
                'service_type_slug' => 'ibada-kuu-ya-jumapili',
                'day_of_week' => 0,
                'starts_at' => '09:00',
            ],
            [
                'title' => 'Ibada ya Jumatano',
                'service_type_slug' => 'ibada-ya-jumatano',
                'day_of_week' => 3,
                'starts_at' => '17:00',
            ],
            [
                'title' => 'Ibada ya Ijumaa',
                'service_type_slug' => 'ibada-ya-ijumaa',
                'day_of_week' => 5,
                'starts_at' => '17:00',
            ],
            [
                'title' => 'Ibada ya Wababa',
                'service_type_slug' => 'ibada-ya-idara',
                'department_slug' => 'wababa',
                'day_of_week' => 0,
                'starts_at' => '19:00',
            ],
            [
                'title' => 'Ibada ya Wamama',
                'service_type_slug' => 'ibada-ya-idara',
                'department_slug' => 'wamama',
                'day_of_week' => 2,
                'starts_at' => '16:00',
            ],
            [
                'title' => 'Ibada ya Kanda',
                'service_type_slug' => 'ibada-ya-zone',
                'day_of_week' => 6,
                'starts_at' => '07:00',
            ],
        ])->each(function (array $routine): void {
            ServiceRoutine::firstOrCreate(
                ['title' => $routine['title']],
                [
                    'service_type_id' => ServiceType::where('slug', $routine['service_type_slug'])->value('id'),
                    'department_id' => isset($routine['department_slug']) ? Department::where('slug', $routine['department_slug'])->value('id') : null,
                    'day_of_week' => $routine['day_of_week'],
                    'starts_at' => $routine['starts_at'],
                    'is_active' => true,
                ],
            );
        });

        collect([
            ['name' => 'Sadaka', 'slug' => 'sadaka'],
            ['name' => 'Zaka', 'slug' => 'zaka'],
            ['name' => 'Michango', 'slug' => 'michango'],
            ['name' => 'Ahadi', 'slug' => 'ahadi'],
            ['name' => 'Kapu la Wamama', 'slug' => 'kapu-la-wamama'],
            ['name' => 'Gunia la Wababa', 'slug' => 'gunia-la-wababa'],
            ['name' => 'Kumtegemeza Mchungaji', 'slug' => 'kumtegemeza-mchungaji'],
            ['name' => 'Sadaka ya Kanda', 'slug' => 'sadaka-ya-zone'],
            ['name' => 'Sadaka ya Idara', 'slug' => 'sadaka-ya-idara'],
        ])->each(fn (array $category) => IncomeCategory::firstOrCreate(['slug' => $category['slug']], $category));

        $admin = User::firstOrCreate(
            ['email' => 'admin@tag-cicc.or.tz'],
            [
                'name' => 'TAG-CICC Admin',
                'phone_number' => '0654000000',
                'password' => Hash::make('password'),
            ],
        );

        $admin->assignRole('Super Admin');

        Member::firstOrCreate(
            ['email' => 'admin@tag-cicc.or.tz'],
            [
                'user_id' => $admin->id,
                'first_name' => 'TAG-CICC',
                'last_name' => 'Admin',
                'gender' => 'male',
                'phone_number' => '0654000000',
                'source' => 'member',
                'joined_at' => now()->toDateString(),
            ],
        );
    }
}
