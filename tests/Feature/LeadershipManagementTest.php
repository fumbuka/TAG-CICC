<?php

namespace Tests\Feature;

use App\Livewire\Leadership\Index as LeadershipIndex;
use App\Models\Department;
use App\Models\LeadershipTitle;
use App\Models\Member;
use App\Models\MemberLeadershipAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeadershipManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_leadership_page_requires_permission(): void
    {
        $user = User::factory()->create();

        Permission::create([
            'name' => 'leadership.manage',
            'guard_name' => 'web',
        ]);

        $this->actingAs($user)
            ->get('/leadership')
            ->assertForbidden();

        $user->givePermissionTo('leadership.manage');

        $this->actingAs($user)
            ->get('/leadership')
            ->assertOk();
    }

    public function test_leadership_title_can_be_created_and_assigned(): void
    {
        $user = User::factory()->create();
        $member = Member::create([
            'first_name' => 'Neema',
            'last_name' => 'Adam',
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
        ]);
        $department = Department::create([
            'name' => 'Maendeleo',
            'slug' => 'maendeleo',
        ]);

        Livewire::actingAs($user)
            ->test(LeadershipIndex::class)
            ->set('title_name', 'Mkurugenzi wa Idara')
            ->set('title_scope', 'department')
            ->call('saveTitle')
            ->assertHasNoErrors()
            ->assertDispatched('title-created');

        $title = LeadershipTitle::query()->where('name', 'Mkurugenzi wa Idara')->firstOrFail();

        Livewire::actingAs($user)
            ->test(LeadershipIndex::class)
            ->set('member_id', $member->id)
            ->set('leadership_title_id', $title->id)
            ->set('department_id', $department->id)
            ->set('started_at', '2026-05-28')
            ->call('saveAssignment')
            ->assertHasNoErrors()
            ->assertDispatched('assignment-created');

        $this->assertDatabaseHas('member_leadership_assignments', [
            'member_id' => $member->id,
            'leadership_title_id' => $title->id,
            'department_id' => $department->id,
            'assigned_by_user_id' => $user->id,
        ]);
    }

    public function test_department_level_title_requires_department(): void
    {
        $user = User::factory()->create();
        $member = Member::create([
            'first_name' => 'Neema',
            'last_name' => 'Adam',
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
        ]);
        $title = LeadershipTitle::create([
            'name' => 'Katibu wa Idara',
            'slug' => 'katibu-wa-idara',
            'scope' => 'department',
        ]);

        Livewire::actingAs($user)
            ->test(LeadershipIndex::class)
            ->set('member_id', $member->id)
            ->set('leadership_title_id', $title->id)
            ->call('saveAssignment')
            ->assertHasErrors('department_id');

        $this->assertSame(0, MemberLeadershipAssignment::count());
    }
}
