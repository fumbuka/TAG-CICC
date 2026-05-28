<?php

namespace Tests\Feature;

use App\Livewire\Calendar\Index as CalendarIndex;
use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\Member;
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

        $this->actingAs($user)
            ->get('/calendar')
            ->assertForbidden();

        $user->givePermissionTo('calendar.manage');

        $this->actingAs($user)
            ->get('/calendar')
            ->assertOk();
    }

    public function test_calendar_event_can_be_created_edited_and_deleted(): void
    {
        $user = User::factory()->create();
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
        $user = User::factory()->create();
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
}
