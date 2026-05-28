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

    public function test_member_can_be_edited_and_deleted(): void
    {
        $user = User::factory()->create();
        $member = Member::create([
            'first_name' => 'Neema',
            'last_name' => 'Adam',
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
            'phone_number' => '0654849299',
        ]);

        Livewire::actingAs($user)
            ->test(MembersIndex::class)
            ->call('edit', $member->id)
            ->set('first_name', 'Upendo')
            ->set('last_name', 'Adam')
            ->set('gender', 'female')
            ->set('date_of_birth', '1990-01-01')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('member-updated');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'first_name' => 'Upendo',
        ]);

        Livewire::actingAs($user)
            ->test(MembersIndex::class)
            ->call('delete', $member->id)
            ->assertDispatched('member-deleted');

        $this->assertSoftDeleted('members', [
            'id' => $member->id,
        ]);
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

    public function test_department_can_be_edited_and_deleted_when_empty(): void
    {
        $user = User::factory()->create();
        $department = Department::create([
            'name' => 'Medai',
            'slug' => 'medai',
        ]);

        Livewire::actingAs($user)
            ->test(DepartmentsIndex::class)
            ->call('edit', $department->id)
            ->set('name', 'Media')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('department-updated');

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Media',
            'slug' => 'media',
        ]);

        Livewire::actingAs($user)
            ->test(DepartmentsIndex::class)
            ->call('delete', $department->id)
            ->assertDispatched('department-deleted');

        $this->assertDatabaseMissing('departments', [
            'id' => $department->id,
        ]);
    }

    public function test_department_with_members_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $department = Department::create([
            'name' => 'Media',
            'slug' => 'media',
        ]);
        $member = Member::create([
            'first_name' => 'Neema',
            'last_name' => 'Adam',
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
        ]);

        $member->departments()->attach($department->id, [
            'assignment_source' => 'manual',
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(DepartmentsIndex::class)
            ->call('delete', $department->id)
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
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

    public function test_zone_can_be_edited_and_deleted_when_empty(): void
    {
        $user = User::factory()->create();
        $zone = Zone::create([
            'name' => 'Mbagara',
            'slug' => 'mbagara',
        ]);

        Livewire::actingAs($user)
            ->test(ZonesIndex::class)
            ->call('edit', $zone->id)
            ->set('name', 'Mbagala')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('zone-updated');

        $this->assertDatabaseHas('zones', [
            'id' => $zone->id,
            'name' => 'Mbagala',
            'slug' => 'mbagala',
        ]);

        Livewire::actingAs($user)
            ->test(ZonesIndex::class)
            ->call('delete', $zone->id)
            ->assertDispatched('zone-deleted');

        $this->assertDatabaseMissing('zones', [
            'id' => $zone->id,
        ]);
    }

    public function test_user_can_choose_a_language(): void
    {
        $user = User::factory()->create([
            'preferred_locale' => 'sw',
        ]);

        $this->actingAs($user)
            ->post('/language', ['locale' => 'en'])
            ->assertRedirect();

        $this->assertSame('en', $user->refresh()->preferred_locale);
        $this->assertSame('en', session('locale'));
    }
}
