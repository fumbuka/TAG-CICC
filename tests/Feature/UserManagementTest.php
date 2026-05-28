<?php

namespace Tests\Feature;

use App\Livewire\Users\Index as UsersIndex;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_page_requires_permission(): void
    {
        $user = User::factory()->create();

        Permission::create([
            'name' => 'users.manage',
            'guard_name' => 'web',
        ]);

        $this->actingAs($user)
            ->get('/users')
            ->assertForbidden();

        $user->givePermissionTo('users.manage');

        $this->actingAs($user)
            ->get('/users')
            ->assertOk();
    }

    public function test_user_can_be_created_with_role_and_linked_member(): void
    {
        $admin = User::factory()->create();
        $member = Member::create([
            'first_name' => 'Neema',
            'last_name' => 'Adam',
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
        ]);
        $role = Role::create([
            'name' => 'Katibu wa Kanisa',
            'guard_name' => 'web',
        ]);

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->set('member_id', $member->id)
            ->set('email', 'neema.adam@tag-cicc.or.tz')
            ->set('phone_number', '0654849299')
            ->set('password', 'password123')
            ->set('role_names', [$role->name])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('user-created');

        $user = User::query()->where('email', 'neema.adam@tag-cicc.or.tz')->firstOrFail();

        $this->assertTrue($user->hasRole('Katibu wa Kanisa'));
        $this->assertSame($user->id, $member->refresh()->user_id);
    }

    public function test_user_can_be_edited_and_current_user_cannot_deactivate_self(): void
    {
        $admin = User::factory()->create();
        $member = Member::create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'gender' => 'male',
            'date_of_birth' => '1988-01-01',
        ]);
        $target = User::factory()->create();
        $member->update([
            'user_id' => $target->id,
        ]);
        $role = Role::create([
            'name' => 'Mhasibu wa Kanisa',
            'guard_name' => 'web',
        ]);

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->call('edit', $target->id)
            ->set('email', 'new.name@tag-cicc.or.tz')
            ->set('role_names', [$role->name])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('user-updated');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'email' => 'new.name@tag-cicc.or.tz',
            'name' => 'Old Name',
        ]);

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->call('toggleActive', $admin->id)
            ->assertHasErrors('user_action');

        $this->assertTrue($admin->refresh()->is_active);
    }

    public function test_member_is_required_before_system_access_is_created(): void
    {
        $admin = User::factory()->create();
        $role = Role::create([
            'name' => 'Katibu wa Kanisa',
            'guard_name' => 'web',
        ]);

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->set('email', 'ghost@tag-cicc.or.tz')
            ->set('password', 'password123')
            ->set('role_names', [$role->name])
            ->call('save')
            ->assertHasErrors('member_id');
    }
}
