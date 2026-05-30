<?php

namespace App\Services;

use App\Models\DepartmentEventReport;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;

class DepartmentEventReportPdfService
{
    public const CONTENT_TYPE = 'application/pdf';

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
            'downloadedBy' => $downloadedBy,
            'downloadedAt' => $downloadedAt,
            'downloadedByName' => $this->userDisplayName($downloadedBy),
            'submittedByName' => $this->userDisplayName($report->submittedBy),
            'reviewedByName' => $report->reviewedBy ? $this->userDisplayName($report->reviewedBy) : null,
            'localChurchLogo' => $this->imageDataUri(public_path('images/tag-cicc-logo.png')),
            'motherChurchLogo' => $this->imageDataUri(public_path('images/tag-cicc-icon.png')),
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

    private function userDisplayName(?User $user): string
    {
        if (! $user) {
            return __('messages.not_recorded');
        }

        $memberName = $user->member
            ? trim(collect([
                $user->member->first_name,
                $user->member->middle_name,
                $user->member->last_name,
            ])->filter()->join(' '))
            : '';

        return $memberName !== '' ? $memberName : $user->name;
    }

    private function imageDataUri(string $path): ?string
    {
        if (! File::exists($path)) {
            return null;
        }

        $contents = File::get($path);
        $mimeType = File::mimeType($path) ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }
}
