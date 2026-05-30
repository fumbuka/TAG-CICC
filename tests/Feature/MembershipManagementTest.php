<?php

namespace Tests\Feature;

use App\Livewire\Departments\Index as DepartmentsIndex;
use App\Livewire\Members\Index as MembersIndex;
use App\Livewire\Zones\Index as ZonesIndex;
use App\Models\Department;
use App\Models\LeadershipTitle;
use App\Models\Member;
use App\Models\MemberLeadershipAssignment;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MembershipManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_page_requires_authentication(): void
    {
        $this->get('/members')->assertRedirect('/login');
    }

    public function test_bulk_import_templates_can_be_downloaded(): void
    {
        $user = User::factory()->create();
        collect(['members.import', 'departments.manage', 'zones.manage'])->each(fn (string $permission) => Permission::create([
            'name' => $permission,
            'guard_name' => 'web',
        ]));
        $user->givePermissionTo(['members.import', 'departments.manage', 'zones.manage']);

        foreach (['members', 'departments', 'zones'] as $type) {
            $this->actingAs($user)
                ->get(route('bulk-import-templates.download', $type))
                ->assertOk()
                ->assertDownload("tag-cicc-{$type}-template.xlsx");
        }
    }

    public function test_members_page_can_be_rendered(): void
    {
        $user = User::factory()->create();
        Permission::create([
            'name' => 'members.view',
            'guard_name' => 'web',
        ]);
        $user->givePermissionTo('members.view');

        $this->actingAs($user)
            ->get('/members')
            ->assertOk()
            ->assertSee('Washirika');
    }

    public function test_department_leader_members_list_is_limited_to_assigned_departments(): void
    {
        $user = User::factory()->create();
        Permission::create([
            'name' => 'members.view',
            'guard_name' => 'web',
        ]);
        $user->givePermissionTo('members.view');

        $leader = Member::create([
            'user_id' => $user->id,
            'first_name' => 'Kiongozi',
            'last_name' => 'Wababa',
            'gender' => 'male',
        ]);
        $men = Department::create([
            'name' => 'Wababa',
            'slug' => 'wababa',
        ]);
        $women = Department::create([
            'name' => 'Wamama',
            'slug' => 'wamama',
        ]);
        $title = LeadershipTitle::create([
            'name' => 'Mkurugenzi wa Idara',
            'slug' => 'mkurugenzi-wa-idara',
            'scope' => 'department',
        ]);

        MemberLeadershipAssignment::create([
            'member_id' => $leader->id,
            'leadership_title_id' => $title->id,
            'department_id' => $men->id,
            'is_active' => true,
        ]);

        $menMember = Member::create([
            'first_name' => 'Adam',
            'last_name' => 'Mzee',
            'gender' => 'male',
        ]);
        $womenMember = Member::create([
            'first_name' => 'Neema',
            'last_name' => 'Mama',
            'gender' => 'female',
        ]);

        $menMember->departments()->attach($men->id, ['is_active' => true]);
        $womenMember->departments()->attach($women->id, ['is_active' => true]);

        Livewire::actingAs($user)
            ->test(MembersIndex::class)
            ->assertSee('Adam')
            ->assertSee('Wababa')
            ->assertDontSee('Neema')
            ->assertDontSee('Wamama');
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

    public function test_members_can_be_imported_in_bulk(): void
    {
        $user = User::factory()->create();

        Department::create([
            'name' => 'Wamama',
            'slug' => 'wamama',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'members.csv',
            "TAG-CICC - Kiolezo cha Kupakia Washirika\nfirst_name,last_name,gender,date_of_birth,phone_number,zone,departments\nNeema,Adam,female,1990-01-01,0654849299,Changombe,Maendeleo\n",
        );

        Livewire::actingAs($user)
            ->test(MembersIndex::class)
            ->set('memberImport', $file)
            ->call('import')
            ->assertHasNoErrors()
            ->assertSet('importReport.total_rows', 1)
            ->assertSet('importReport.imported_count', 1)
            ->assertSet('importReport.rejected_count', 0)
            ->assertDispatched('members-imported');

        $member = Member::query()->where('phone_number', '0654849299')->firstOrFail();

        $this->assertSame('Changombe', $member->zone?->name);
        $this->assertTrue($member->departments()->where('slug', 'wamama')->exists());
        $this->assertTrue($member->departments()->where('slug', 'maendeleo')->exists());
    }

    public function test_members_bulk_import_reports_successes_and_rejections(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'members.csv',
            "TAG-CICC - Kiolezo cha Kupakia Washirika\nfirst_name,last_name,gender,date_of_birth,phone_number,zone,departments\nRehema,John,female,,0711111111,Changombe,\nBaraka,Juma,other,2000-01-01,0722222222,,\n,NoFirst,male,,0733333333,,\n",
        );

        Livewire::actingAs($user)
            ->test(MembersIndex::class)
            ->set('memberImport', $file)
            ->call('import')
            ->assertHasNoErrors()
            ->assertSet('importReport.total_rows', 3)
            ->assertSet('importReport.imported_count', 1)
            ->assertSet('importReport.rejected_count', 2)
            ->assertDispatched('members-imported');

        $this->assertDatabaseHas('members', [
            'phone_number' => '0711111111',
            'date_of_birth' => null,
        ]);

        $this->assertDatabaseMissing('members', [
            'phone_number' => '0722222222',
        ]);
        $this->assertDatabaseMissing('members', [
            'phone_number' => '0733333333',
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

    public function test_departments_can_be_imported_in_bulk(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'departments.csv',
            "TAG-CICC - Kiolezo cha Kupakia Idara\nname,description,is_age_based,minimum_age,maximum_age,gender_rule\nMedia,Idara ya matangazo,no,,,\n",
        );

        Livewire::actingAs($user)
            ->test(DepartmentsIndex::class)
            ->set('departmentImport', $file)
            ->call('import')
            ->assertHasNoErrors()
            ->assertSet('importReport.total_rows', 1)
            ->assertSet('importReport.imported_count', 1)
            ->assertSet('importReport.rejected_count', 0)
            ->assertDispatched('departments-imported');

        $this->assertDatabaseHas('departments', [
            'name' => 'Media',
            'slug' => 'media',
        ]);
    }

    public function test_departments_bulk_import_reports_successes_and_rejections(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'departments.csv',
            "TAG-CICC - Kiolezo cha Kupakia Idara\nname,description,is_age_based,minimum_age,maximum_age,gender_rule\nMedia,Idara ya matangazo,no,,,\n,,no,,,\nWazee,Idara ya wazee,yes,80,40,\n",
        );

        Livewire::actingAs($user)
            ->test(DepartmentsIndex::class)
            ->set('departmentImport', $file)
            ->call('import')
            ->assertHasNoErrors()
            ->assertSet('importReport.total_rows', 3)
            ->assertSet('importReport.imported_count', 1)
            ->assertSet('importReport.rejected_count', 2)
            ->assertDispatched('departments-imported');

        $this->assertDatabaseHas('departments', [
            'name' => 'Media',
            'slug' => 'media',
        ]);
        $this->assertDatabaseMissing('departments', [
            'name' => 'Wazee',
            'slug' => 'wazee',
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

    public function test_zones_can_be_imported_in_bulk(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'zones.csv',
            "TAG-CICC - Kiolezo cha Kupakia Kanda\nname,description\nMbagala,Washirika wa Mbagala\nKongowe,Washirika wa Kongowe\n",
        );

        Livewire::actingAs($user)
            ->test(ZonesIndex::class)
            ->set('zoneImport', $file)
            ->call('import')
            ->assertHasNoErrors()
            ->assertSet('importReport.total_rows', 2)
            ->assertSet('importReport.imported_count', 2)
            ->assertSet('importReport.rejected_count', 0)
            ->assertDispatched('zones-imported');

        $this->assertDatabaseHas('zones', [
            'name' => 'Mbagala',
            'slug' => 'mbagala',
        ]);
        $this->assertDatabaseHas('zones', [
            'name' => 'Kongowe',
            'slug' => 'kongowe',
        ]);
    }

    public function test_zones_bulk_import_reports_successes_and_rejections(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'zones.csv',
            "TAG-CICC - Kiolezo cha Kupakia Kanda\nname,description\nMbagala,Washirika wa Mbagala\n,Ukanda bila jina\n",
        );

        Livewire::actingAs($user)
            ->test(ZonesIndex::class)
            ->set('zoneImport', $file)
            ->call('import')
            ->assertHasNoErrors()
            ->assertSet('importReport.total_rows', 2)
            ->assertSet('importReport.imported_count', 1)
            ->assertSet('importReport.rejected_count', 1)
            ->assertDispatched('zones-imported');

        $this->assertDatabaseHas('zones', [
            'name' => 'Mbagala',
            'slug' => 'mbagala',
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
