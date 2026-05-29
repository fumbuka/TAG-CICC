<?php

namespace Tests\Feature;

use App\Livewire\Calendar\Index as CalendarIndex;
use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\LeadershipTitle;
use App\Models\Member;
use App\Models\MemberLeadershipAssignment;
use App\Models\User;
use App\Models\WeeklyDuty;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CalendarManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_page_requires_permission(): void
    {
        $user = User::factory()->create();

        Permission::create([
            'name' => 'calendar.manage',
            'guard_name' => 'web',
        ]);
        Permission::create([
            'name' => 'calendar.submit',
            'guard_name' => 'web',
        ]);

        $this->actingAs($user)
            ->get('/calendar')
            ->assertForbidden();

        $user->givePermissionTo('calendar.manage');

        $this->actingAs($user)
            ->get('/calendar')
            ->assertOk();

        $user->syncPermissions(['calendar.submit']);

        $this->actingAs($user)
            ->get('/calendar')
            ->assertOk();
    }

    public function test_calendar_event_can_be_created_edited_and_deleted(): void
    {
        $user = $this->calendarManager();
        $department = Department::create([
            'name' => 'Wamama',
            'slug' => 'wamama',
        ]);
        $zone = Zone::create([
            'name' => 'Changombe',
            'slug' => 'changombe',
        ]);

        Livewire::actingAs($user)
            ->test(CalendarIndex::class)
            ->set('title', 'Sherehe ya Kina Mama')
            ->set('event_date', '2026-06-14')
            ->set('starts_at', '10:00')
            ->set('ends_at', '12:00')
            ->set('department_id', $department->id)
            ->set('zone_id', $zone->id)
            ->set('description', 'Tukio la idara ya wamama.')
            ->call('saveEvent')
            ->assertHasNoErrors()
            ->assertDispatched('event-created');

        $event = CalendarEvent::query()->where('title', 'Sherehe ya Kina Mama')->firstOrFail();

        Livewire::actingAs($user)
            ->test(CalendarIndex::class)
            ->call('editEvent', $event->id)
            ->set('title', 'Sherehe ya Kina Mama TAG-CICC')
            ->set('event_date', '2026-06-15')
            ->call('saveEvent')
            ->assertHasNoErrors()
            ->assertDispatched('event-updated');

        $event->refresh();

        $this->assertSame('Sherehe ya Kina Mama TAG-CICC', $event->title);
        $this->assertSame('2026-06-15', $event->event_date->toDateString());

        Livewire::actingAs($user)
            ->test(CalendarIndex::class)
            ->call('deleteEvent', $event->id)
            ->assertDispatched('event-deleted');

        $this->assertDatabaseMissing('calendar_events', [
            'id' => $event->id,
        ]);
    }

    public function test_weekly_duty_can_be_created_edited_and_deleted(): void
    {
        $user = $this->calendarManager();
        $elder = Member::create([
            'first_name' => 'Mzee',
            'last_name' => 'Baraka',
            'gender' => 'male',
            'date_of_birth' => '1970-01-01',
        ]);
        $deacon = Member::create([
            'first_name' => 'Shemasi',
            'last_name' => 'Neema',
            'gender' => 'female',
            'date_of_birth' => '1985-01-01',
        ]);

        Livewire::actingAs($user)
            ->test(CalendarIndex::class)
            ->set('week_start', '2026-06-01')
            ->set('week_end', '2026-06-07')
            ->set('elder_member_id', $elder->id)
            ->set('deacon_member_id', $deacon->id)
            ->set('duty_notes', 'Zamu ya ibada kuu.')
            ->call('saveDuty')
            ->assertHasNoErrors()
            ->assertDispatched('duty-created');

        $duty = WeeklyDuty::query()->where('elder_member_id', $elder->id)->firstOrFail();

        Livewire::actingAs($user)
            ->test(CalendarIndex::class)
            ->call('editDuty', $duty->id)
            ->set('week_end', '2026-06-08')
            ->set('duty_notes', 'Zamu ya wiki nzima.')
            ->call('saveDuty')
            ->assertHasNoErrors()
            ->assertDispatched('duty-updated');

        $duty->refresh();

        $this->assertSame('2026-06-08', $duty->week_end->toDateString());
        $this->assertSame('Zamu ya wiki nzima.', $duty->notes);

        Livewire::actingAs($user)
            ->test(CalendarIndex::class)
            ->call('deleteDuty', $duty->id)
            ->assertDispatched('duty-deleted');

        $this->assertDatabaseMissing('weekly_duties', [
            'id' => $duty->id,
        ]);
    }

    public function test_department_secretary_can_submit_department_event_without_time_collision(): void
    {
        $user = User::factory()->create();
        Permission::create([
            'name' => 'calendar.submit',
            'guard_name' => 'web',
        ]);
        $user->givePermissionTo('calendar.submit');

        $member = Member::create([
            'user_id' => $user->id,
            'first_name' => 'Asha',
            'last_name' => 'Katibu',
            'gender' => 'female',
            'date_of_birth' => '1992-01-01',
        ]);
        $department = Department::create([
            'name' => 'Uinjilishaji',
            'slug' => 'uinjilishaji',
        ]);
        $otherDepartment = Department::create([
            'name' => 'Maendeleo',
            'slug' => 'maendeleo',
        ]);
        $title = LeadershipTitle::create([
            'name' => 'Katibu wa Idara',
            'slug' => 'katibu-wa-idara',
            'scope' => 'department',
        ]);

        MemberLeadershipAssignment::create([
            'member_id' => $member->id,
            'leadership_title_id' => $title->id,
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        CalendarEvent::create([
            'department_id' => $otherDepartment->id,
            'title' => 'Semina ya Maendeleo',
            'event_date' => '2026-06-14',
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(CalendarIndex::class)
            ->set('title', 'Semina ya Uinjilishaji')
            ->set('event_date', '2026-06-14')
            ->set('starts_at', '11:00')
            ->set('ends_at', '13:00')
            ->set('department_id', $department->id)
            ->call('saveEvent')
            ->assertHasErrors('starts_at');

        Livewire::actingAs($user)
            ->test(CalendarIndex::class)
            ->set('title', 'Semina ya Uinjilishaji')
            ->set('event_date', '2026-06-14')
            ->set('starts_at', '12:00')
            ->set('ends_at', '13:00')
            ->set('department_id', $department->id)
            ->call('saveEvent')
            ->assertHasNoErrors()
            ->assertDispatched('event-created');

        $this->assertDatabaseHas('calendar_events', [
            'department_id' => $department->id,
            'created_by_user_id' => $user->id,
            'title' => 'Semina ya Uinjilishaji',
        ]);

        Livewire::actingAs($user)
            ->test(CalendarIndex::class)
            ->set('title', 'Tukio la idara nyingine')
            ->set('event_date', '2026-06-15')
            ->set('starts_at', '08:00')
            ->set('ends_at', '09:00')
            ->set('department_id', $otherDepartment->id)
            ->call('saveEvent')
            ->assertHasErrors('department_id');
    }

    private function calendarManager(): User
    {
        $user = User::factory()->create();

        Permission::firstOrCreate([
            'name' => 'calendar.manage',
            'guard_name' => 'web',
        ]);

        $user->givePermissionTo('calendar.manage');

        return $user;
    }
}
