<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ModuleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_modules_require_their_permissions(): void
    {
        $user = User::factory()->create();

        collect([
            'members.view',
            'departments.manage',
            'zones.manage',
            'services.manage',
            'sms.view',
        ])->each(fn (string $permission) => Permission::create([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        foreach ([
            '/members' => 'members.view',
            '/departments' => 'departments.manage',
            '/zones' => 'zones.manage',
            '/services' => 'services.manage',
            '/sms' => 'sms.view',
        ] as $path => $permission) {
            $this->actingAs($user)->get($path)->assertForbidden();

            $user->givePermissionTo($permission);
            $this->actingAs($user)->get($path)->assertOk();
            $user->revokePermissionTo($permission);
        }
    }

    public function test_bulk_import_templates_require_matching_module_permission(): void
    {
        $user = User::factory()->create();

        collect([
            'members.import',
            'departments.manage',
            'zones.manage',
        ])->each(fn (string $permission) => Permission::create([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        $user->givePermissionTo('members.import');

        $this->actingAs($user)
            ->get(route('bulk-import-templates.download', 'members'))
            ->assertOk()
            ->assertDownload('tag-cicc-members-template.xlsx');

        $this->actingAs($user)
            ->get(route('bulk-import-templates.download', 'departments'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('bulk-import-templates.download', 'zones'))
            ->assertForbidden();
    }
}
