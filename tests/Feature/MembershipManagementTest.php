<?php

namespace Tests\Feature;

use App\Livewire\Departments\Index as DepartmentsIndex;
use App\Livewire\Members\Index as MembersIndex;
use App\Livewire\Zones\Index as ZonesIndex;
use App\Models\Department;
use App\Models\Member;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MembershipManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_page_requires_authentication(): void
    {
        $this->get('/members')->assertRedirect('/login');
    }

    public function test_members_page_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/members')
            ->assertOk()
            ->assertSee('Washirika');
    }

    public function test_member_can_be_registered_with_zone_and_department_assignments(): void
    {
        $user = User::factory()->create();

        $zone = Zone::create([
            'name' => 'Changombe',
            'slug' => 'changombe',
        ]);

        Department::create([
            'name' => 'Wamama',
            'slug' => 'wamama',
        ]);

        $maendeleo = Department::create([
            'name' => 'Maendeleo',
            'slug' => 'maendeleo',
        ]);

        Livewire::actingAs($user)
            ->test(MembersIndex::class)
            ->set('first_name', 'Neema')
            ->set('middle_name', 'Grace')
            ->set('last_name', 'Adam')
            ->set('gender', 'female')
            ->set('date_of_birth', '1990-01-01')
            ->set('phone_number', '0654849299')
            ->set('email', 'neema.adam@tag-cicc.or.tz')
            ->set('residential_area', 'Changombe')
            ->set('zone_id', $zone->id)
            ->set('department_ids', [$maendeleo->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('member-created');

        $member = Member::query()->where('phone_number', '0654849299')->firstOrFail();

        $this->assertSame($zone->id, $member->zone_id);
        $this->assertTrue($member->departments()->where('slug', 'wamama')->exists());
        $this->assertTrue($member->departments()->where('slug', 'maendeleo')->exists());
    }

    public function test_department_can_be_created(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DepartmentsIndex::class)
            ->set('name', 'Media')
            ->set('description', 'Idara ya matangazo na media.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('department-created');

        $this->assertDatabaseHas('departments', [
            'name' => 'Media',
            'slug' => 'media',
        ]);
    }

    public function test_zone_can_be_created(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ZonesIndex::class)
            ->set('name', 'Mbagala')
            ->set('description', 'Washirika wa eneo la Mbagala.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('zone-created');

        $this->assertDatabaseHas('zones', [
            'name' => 'Mbagala',
            'slug' => 'mbagala',
        ]);
    }
}
