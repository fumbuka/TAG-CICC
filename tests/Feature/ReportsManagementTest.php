<?php

namespace Tests\Feature;

use App\Livewire\Reports\Index as ReportsIndex;
use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\DepartmentEventReport;
use App\Models\LeadershipTitle;
use App\Models\Member;
use App\Models\MemberLeadershipAssignment;
use App\Models\User;
use App\Services\DepartmentEventReportPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReportsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_page_requires_permission(): void
    {
        $user = User::factory()->create();

        Permission::create([
            'name' => 'reports.submit',
            'guard_name' => 'web',
        ]);

        $this->actingAs($user)
            ->get('/reports')
            ->assertForbidden();

        $user->givePermissionTo('reports.submit');

        $this->actingAs($user)
            ->get('/reports')
            ->assertOk();
    }

    public function test_department_leader_can_submit_and_edit_report_for_own_department(): void
    {
        [$user, $department, $event] = $this->departmentReporter();
        $otherDepartment = Department::create([
            'name' => 'Maendeleo',
            'slug' => 'maendeleo',
        ]);
        $otherEvent = CalendarEvent::create([
            'department_id' => $otherDepartment->id,
            'title' => 'Kikao cha Maendeleo',
            'event_date' => '2026-06-16',
            'starts_at' => '10:00',
            'ends_at' => '12:00',
        ]);

        Livewire::actingAs($user)
            ->test(ReportsIndex::class)
            ->set('calendar_event_id', $event->id)
            ->set('report_date', '2026-06-15')
            ->set('attendance_count', 42)
            ->set('summary', 'Tukio limefanyika kwa mafanikio.')
            ->set('achievements', 'Watu wapya wamefikiwa.')
            ->call('saveReport')
            ->assertHasNoErrors()
            ->assertDispatched('report-created');

        $report = DepartmentEventReport::query()->where('calendar_event_id', $event->id)->firstOrFail();

        $this->assertSame($department->id, $report->department_id);
        $this->assertSame($user->id, $report->submitted_by_user_id);
        $this->assertSame('submitted', $report->status);

        Livewire::actingAs($user)
            ->test(ReportsIndex::class)
            ->call('editReport', $report->id)
            ->set('summary', 'Tukio limefanyika na ripoti imeboreshwa.')
            ->call('saveReport')
            ->assertHasNoErrors()
            ->assertDispatched('report-updated');

        $this->assertDatabaseHas('department_event_reports', [
            'id' => $report->id,
            'summary' => 'Tukio limefanyika na ripoti imeboreshwa.',
        ]);

        Livewire::actingAs($user)
            ->test(ReportsIndex::class)
            ->set('calendar_event_id', $otherEvent->id)
            ->set('report_date', '2026-06-16')
            ->set('summary', 'Jaribio la idara nyingine.')
            ->call('saveReport')
            ->assertForbidden();
    }

    public function test_event_report_can_be_approved_and_locked(): void
    {
        [$reporter, , $event] = $this->departmentReporter();
        $approver = User::factory()->create();

        Permission::firstOrCreate([
            'name' => 'reports.approve',
            'guard_name' => 'web',
        ]);
        $approver->givePermissionTo('reports.approve');

        $report = DepartmentEventReport::create([
            'calendar_event_id' => $event->id,
            'department_id' => $event->department_id,
            'submitted_by_user_id' => $reporter->id,
            'report_date' => '2026-06-15',
            'status' => 'submitted',
            'summary' => 'Ripoti ya utekelezaji.',
        ]);

        Livewire::actingAs($approver)
            ->test(ReportsIndex::class)
            ->call('approveReport', $report->id)
            ->assertDispatched('report-approved');

        $report->refresh();

        $this->assertSame('approved', $report->status);
        $this->assertSame($approver->id, $report->reviewed_by_user_id);
        $this->assertNotNull($report->reviewed_at);

        Livewire::actingAs($reporter)
            ->test(ReportsIndex::class)
            ->call('editReport', $report->id)
            ->assertForbidden();
    }

    public function test_department_event_report_can_be_downloaded_as_pdf(): void
    {
        $this->travelTo('2026-05-30 12:15:00');

        [$reporter, , $event] = $this->departmentReporter();

        $report = DepartmentEventReport::create([
            'calendar_event_id' => $event->id,
            'department_id' => $event->department_id,
            'submitted_by_user_id' => $reporter->id,
            'report_date' => '2026-06-15',
            'attendance_count' => 42,
            'status' => 'submitted',
            'summary' => 'Ripoti ya utekelezaji.',
            'achievements' => 'Watu wapya wamefikiwa.',
        ]);

        Livewire::actingAs($reporter)
            ->test(ReportsIndex::class)
            ->call('downloadReport', $report->id)
            ->assertFileDownloaded(
                'tag-cicc-event-report-'.$report->id.'-20260530-121500.pdf',
                contentType: DepartmentEventReportPdfService::CONTENT_TYPE,
            );
    }

    /**
     * @return array{0: User, 1: Department, 2: CalendarEvent}
     */
    private function departmentReporter(): array
    {
        $user = User::factory()->create();
        Permission::firstOrCreate([
            'name' => 'reports.submit',
            'guard_name' => 'web',
        ]);
        $user->givePermissionTo('reports.submit');

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

        $event = CalendarEvent::create([
            'department_id' => $department->id,
            'title' => 'Semina ya Uinjilishaji',
            'event_date' => '2026-06-15',
            'starts_at' => '10:00',
            'ends_at' => '12:00',
        ]);

        return [$user, $department, $event];
    }
}
