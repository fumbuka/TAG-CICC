<?php

namespace App\Livewire\Reports;

use App\Models\CalendarEvent;
use App\Models\DepartmentEventReport;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public ?int $editingReportId = null;

    public ?int $calendar_event_id = null;

    public string $report_date = '';

    public ?int $attendance_count = null;

    public string $summary = '';

    public string $achievements = '';

    public string $challenges = '';

    public string $recommendations = '';

    /**
     * @var array<int, int>
     */
    public array $submissionDepartmentIds = [];

    public function mount(): void
    {
        $this->submissionDepartmentIds = $this->departmentIdsAllowedForSubmission();
        $this->report_date = now()->toDateString();
    }

    public function saveReport(): void
    {
        abort_unless($this->canSubmitReports(), 403);

        $validated = $this->validate([
            'calendar_event_id' => ['required', 'integer', Rule::exists('calendar_events', 'id')],
            'report_date' => ['required', 'date'],
            'attendance_count' => ['nullable', 'integer', 'min:0'],
            'summary' => ['required', 'string', 'max:3000'],
            'achievements' => ['nullable', 'string', 'max:3000'],
            'challenges' => ['nullable', 'string', 'max:3000'],
            'recommendations' => ['nullable', 'string', 'max:3000'],
        ]);

        $event = CalendarEvent::query()
            ->whereNotNull('department_id')
            ->findOrFail($validated['calendar_event_id']);

        abort_unless($this->canSubmitForDepartment($event->department_id), 403);

        $existingReport = DepartmentEventReport::query()
            ->where('calendar_event_id', $event->id)
            ->when($this->editingReportId, fn ($query) => $query->whereKeyNot($this->editingReportId))
            ->first();

        if ($existingReport) {
            $this->addError('calendar_event_id', __('messages.report_already_exists'));

            return;
        }

        $attributes = [
            'calendar_event_id' => $event->id,
            'department_id' => $event->department_id,
            'submitted_by_user_id' => Auth::id(),
            'report_date' => $validated['report_date'],
            'attendance_count' => $validated['attendance_count'],
            'status' => 'submitted',
            'summary' => $validated['summary'],
            'achievements' => $validated['achievements'] ?: null,
            'challenges' => $validated['challenges'] ?: null,
            'recommendations' => $validated['recommendations'] ?: null,
            'reviewed_by_user_id' => null,
            'review_notes' => null,
            'reviewed_at' => null,
        ];

        $wasEditing = $this->editingReportId !== null;

        if ($wasEditing) {
            $report = DepartmentEventReport::findOrFail($this->editingReportId);
            abort_unless($this->canEditReport($report), 403);

            $report->update($attributes);
        } else {
            DepartmentEventReport::create($attributes);
        }

        $this->resetReportForm();

        $this->dispatch($wasEditing ? 'report-updated' : 'report-created');
    }

    public function editReport(int $reportId): void
    {
        $report = DepartmentEventReport::findOrFail($reportId);

        abort_unless($this->canEditReport($report), 403);

        $this->editingReportId = $report->id;
        $this->calendar_event_id = $report->calendar_event_id;
        $this->report_date = $report->report_date?->toDateString() ?? now()->toDateString();
        $this->attendance_count = $report->attendance_count;
        $this->summary = $report->summary;
        $this->achievements = $report->achievements ?? '';
        $this->challenges = $report->challenges ?? '';
        $this->recommendations = $report->recommendations ?? '';
    }

    public function cancelReportEdit(): void
    {
        $this->resetReportForm();
    }

    public function deleteReport(int $reportId): void
    {
        $report = DepartmentEventReport::findOrFail($reportId);

        abort_unless($this->canDeleteReport($report), 403);

        $report->delete();

        $this->dispatch('report-deleted');
    }

    public function approveReport(int $reportId): void
    {
        abort_unless($this->canApproveReports(), 403);

        DepartmentEventReport::findOrFail($reportId)->update([
            'status' => 'approved',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $this->dispatch('report-approved');
    }

    public function returnReport(int $reportId): void
    {
        abort_unless($this->canApproveReports(), 403);

        DepartmentEventReport::findOrFail($reportId)->update([
            'status' => 'returned',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $this->dispatch('report-returned');
    }

    public function render(): View
    {
        $plannedEventsQuery = $this->scopedDepartmentEventsQuery();
        $reportsQuery = $this->scopedReportsQuery();

        $plannedEventsCount = (clone $plannedEventsQuery)->count();
        $submittedReportsCount = (clone $reportsQuery)->count();
        $approvedReportsCount = (clone $reportsQuery)->where('status', 'approved')->count();

        return view('livewire.reports.index', [
            'plannedEventsCount' => $plannedEventsCount,
            'submittedReportsCount' => $submittedReportsCount,
            'approvedReportsCount' => $approvedReportsCount,
            'implementationRate' => $plannedEventsCount > 0 ? round(($submittedReportsCount / $plannedEventsCount) * 100) : 0,
            'approvalRate' => $plannedEventsCount > 0 ? round(($approvedReportsCount / $plannedEventsCount) * 100) : 0,
            'reportableEvents' => $this->reportableEvents(),
            'reports' => (clone $reportsQuery)
                ->with(['calendarEvent', 'department', 'submittedBy', 'reviewedBy'])
                ->latest('report_date')
                ->latest()
                ->get(),
            'canSubmitReports' => $this->canSubmitReports(),
            'canApproveReports' => $this->canApproveReports(),
            'submissionDepartmentIds' => $this->submissionDepartmentIds,
        ]);
    }

    private function resetReportForm(): void
    {
        $this->reset([
            'editingReportId',
            'calendar_event_id',
            'attendance_count',
            'summary',
            'achievements',
            'challenges',
            'recommendations',
        ]);
        $this->report_date = now()->toDateString();
        $this->resetErrorBag();
    }

    private function reportableEvents()
    {
        return $this->scopedDepartmentEventsQuery()
            ->with(['department', 'departmentEventReport'])
            ->orderByDesc('event_date')
            ->get()
            ->filter(fn (CalendarEvent $event): bool => ! $event->departmentEventReport || $event->departmentEventReport->id === $this->editingReportId)
            ->values();
    }

    private function scopedDepartmentEventsQuery()
    {
        return CalendarEvent::query()
            ->where('is_active', true)
            ->whereNotNull('department_id')
            ->when(! $this->canViewAllReports(), fn ($query) => $query->whereIn('department_id', $this->submissionDepartmentIds));
    }

    private function scopedReportsQuery()
    {
        return DepartmentEventReport::query()
            ->when(! $this->canViewAllReports(), fn ($query) => $query->whereIn('department_id', $this->submissionDepartmentIds));
    }

    private function canViewAllReports(): bool
    {
        return Auth::user()?->can('reports.view') || $this->canApproveReports();
    }

    private function canSubmitReports(): bool
    {
        return Auth::user()?->can('reports.submit') && ($this->canViewAllReports() || count($this->submissionDepartmentIds) > 0);
    }

    private function canApproveReports(): bool
    {
        return Auth::user()?->can('reports.approve') ?? false;
    }

    private function canSubmitForDepartment(?int $departmentId): bool
    {
        if (! $departmentId || ! Auth::user()?->can('reports.submit')) {
            return false;
        }

        return $this->canViewAllReports() || in_array($departmentId, $this->submissionDepartmentIds, true);
    }

    private function canEditReport(DepartmentEventReport $report): bool
    {
        return $report->status !== 'approved' && $this->canSubmitForDepartment($report->department_id);
    }

    private function canDeleteReport(DepartmentEventReport $report): bool
    {
        return $this->canApproveReports() || $this->canEditReport($report);
    }

    /**
     * @return array<int, int>
     */
    private function departmentIdsAllowedForSubmission(): array
    {
        $member = Auth::user()?->member;

        if (! $member) {
            return [];
        }

        return $member->leadershipAssignments()
            ->where('is_active', true)
            ->whereNotNull('department_id')
            ->whereHas('leadershipTitle', fn ($query) => $query->where('scope', 'department'))
            ->pluck('department_id')
            ->unique()
            ->values()
            ->all();
    }
}
