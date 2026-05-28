<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentPosition;
use App\Models\IncomeCategory;
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
                'reports.view',
            ],
            'Mhasibu wa Kanisa' => [
                'dashboard.view',
                'finance.view',
                'finance.record',
                'reports.view',
            ],
            'Mkurugenzi wa Idara' => [
                'dashboard.view',
                'members.view',
                'services.manage',
                'reports.view',
                'reports.submit',
            ],
            'Katibu wa Idara' => [
                'dashboard.view',
                'members.view',
                'reports.submit',
            ],
            'Mweka Hazina wa Idara' => [
                'dashboard.view',
                'finance.view',
                'finance.record',
                'reports.submit',
            ],
            'Kiongozi wa Zone' => [
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
            ['name' => 'Ibada Kuu ya Jumapili', 'slug' => 'ibada-kuu-ya-jumapili', 'allows_tithe' => true],
            ['name' => 'Ibada ya Jumatano', 'slug' => 'ibada-ya-jumatano'],
            ['name' => 'Ibada ya Ijumaa', 'slug' => 'ibada-ya-ijumaa'],
            ['name' => 'Ibada ya Idara', 'slug' => 'ibada-ya-idara'],
            ['name' => 'Ibada ya Zone', 'slug' => 'ibada-ya-zone'],
            ['name' => 'Tukio Maalum', 'slug' => 'tukio-maalum'],
        ])->each(fn (array $serviceType) => ServiceType::firstOrCreate(['slug' => $serviceType['slug']], $serviceType));

        collect([
            ['name' => 'Sadaka', 'slug' => 'sadaka'],
            ['name' => 'Zaka', 'slug' => 'zaka'],
            ['name' => 'Michango', 'slug' => 'michango'],
            ['name' => 'Ahadi', 'slug' => 'ahadi'],
            ['name' => 'Kapu la Wamama', 'slug' => 'kapu-la-wamama'],
            ['name' => 'Gunia la Wababa', 'slug' => 'gunia-la-wababa'],
            ['name' => 'Kumtegemeza Mchungaji', 'slug' => 'kumtegemeza-mchungaji'],
            ['name' => 'Sadaka ya Zone', 'slug' => 'sadaka-ya-zone'],
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
    }
}
