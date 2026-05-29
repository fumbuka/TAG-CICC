<?php

namespace Tests\Feature;

use App\Livewire\Visitors\Index as VisitorsIndex;
use App\Models\Department;
use App\Models\Member;
use App\Models\User;
use App\Models\Visitor;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VisitorsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_page_requires_authentication(): void
    {
        $this->get('/visitors')->assertRedirect('/login');
    }

    public function test_visitors_page_can_be_rendered_by_authorized_user(): void
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'visitors.manage']);
        $user->givePermissionTo('visitors.manage');

        $this->actingAs($user)
            ->get('/visitors')
            ->assertOk()
            ->assertSee('Wageni');
    }

    public function test_visitor_can_be_registered_edited_and_deleted(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(VisitorsIndex::class)
            ->set('full_name', 'Neema Adam')
            ->set('phone_number', '0654849299')
            ->set('residential_area', 'Changombe')
            ->set('visited_at', '2026-05-29')
            ->set('invited_by', 'Fumbuka Adam')
            ->set('follow_up_status', 'follow_up')
            ->set('assigned_to_user_id', $user->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('visitor-created');

        $visitor = Visitor::query()->where('phone_number', '0654849299')->firstOrFail();

        Livewire::actingAs($user)
            ->test(VisitorsIndex::class)
            ->call('edit', $visitor->id)
            ->set('full_name', 'Neema Grace Adam')
            ->set('follow_up_status', 'invited_to_membership')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('visitor-updated');

        $this->assertDatabaseHas('visitors', [
            'id' => $visitor->id,
            'full_name' => 'Neema Grace Adam',
            'follow_up_status' => 'invited_to_membership',
        ]);

        Livewire::actingAs($user)
            ->test(VisitorsIndex::class)
            ->call('delete', $visitor->id)
            ->assertDispatched('visitor-deleted');

        $this->assertDatabaseMissing('visitors', [
            'id' => $visitor->id,
        ]);
    }

    public function test_visitor_can_be_converted_to_member_with_default_departments(): void
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
        $visitor = Visitor::create([
            'full_name' => 'Neema Grace Adam',
            'phone_number' => '0654849299',
            'residential_area' => 'Changombe',
            'visited_at' => '2026-05-29',
            'follow_up_status' => 'new',
        ]);

        Livewire::actingAs($user)
            ->test(VisitorsIndex::class)
            ->call('prepareConversion', $visitor->id)
            ->set('convert_gender', 'female')
            ->set('convert_date_of_birth', '1990-01-01')
            ->set('convert_email', 'neema.adam@tag-cicc.or.tz')
            ->set('convert_zone_id', $zone->id)
            ->set('convert_department_ids', [$maendeleo->id])
            ->call('convertToMember')
            ->assertHasNoErrors()
            ->assertDispatched('visitor-converted');

        $visitor->refresh();
        $member = Member::query()->where('phone_number', '0654849299')->firstOrFail();

        $this->assertSame($member->id, $visitor->converted_member_id);
        $this->assertSame('converted', $visitor->follow_up_status);
        $this->assertSame('visitor', $member->source);
        $this->assertSame($zone->id, $member->zone_id);
        $this->assertTrue($member->departments()->where('slug', 'wamama')->exists());
        $this->assertTrue($member->departments()->where('slug', 'maendeleo')->exists());
    }
}
