<?php

namespace App\Services;

use App\Models\DepartmentEventReport;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;

class DepartmentEventReportPdfService
{
    public const CONTENT_TYPE = 'application/pdf';

    public function __construct(private readonly OperationalReportPdfAssets $assets) {}

    /**
     * @return array{path: string, filename: string}
     */
    public function create(DepartmentEventReport $report, User $downloadedBy): array
    {
        $report->loadMissing([
            'calendarEvent',
            'department',
            'submittedBy.member',
            'reviewedBy.member',
        ]);

        $downloadedAt = now();
        $filename = sprintf(
            'tag-cicc-event-report-%s-%s.pdf',
            $report->id,
            $downloadedAt->format('Ymd-His'),
        );
        $directory = storage_path('app/private/report-downloads');
        $path = $directory.DIRECTORY_SEPARATOR.$filename;

        File::ensureDirectoryExists($directory);

        $pdf = Pdf::loadView('pdf.department-event-report', [
            'report' => $report,
            ...$this->assets->branding($downloadedBy, $downloadedAt),
            'submittedByName' => $this->assets->userDisplayName($report->submittedBy),
            'reviewedByName' => $report->reviewedBy ? $this->assets->userDisplayName($report->reviewedBy) : null,
            'metrics' => $this->metrics($report),
        ])
            ->setPaper('a4')
            ->setOption('defaultFont', 'DejaVu Sans');

        $pdf->save($path);

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    /**
     * @return array{completion_rate: int, review_rate: int, filled_sections: int, total_sections: int}
     */
    private function metrics(DepartmentEventReport $report): array
    {
        $filledSections = collect([
            $report->summary,
            $report->achievements,
            $report->challenges,
            $report->recommendations,
        ])->filter(fn (?string $value): bool => filled($value))->count();

        $reviewRate = match ($report->status) {
            'approved' => 100,
            'returned' => 35,
            default => 65,
        };

        return [
            'completion_rate' => (int) round(($filledSections / 4) * 100),
            'review_rate' => $reviewRate,
            'filled_sections' => $filledSections,
            'total_sections' => 4,
        ];
    }
}
