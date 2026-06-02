<?php

namespace App\Livewire\Concerns;

use App\Models\ImportUpload;
use App\Services\ImportReportExportService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

trait TracksImportResults
{
    /**
     * @var array<string, mixed>
     */
    public array $importReport = [];

    protected function startImportReport(string $module, int $totalRows, ?string $originalFilename = null): void
    {
        $this->importReport = [
            'module' => $module,
            'original_filename' => $originalFilename,
            'total_rows' => $totalRows,
            'imported_count' => 0,
            'rejected_count' => 0,
            'successful_rows' => [],
            'failed_rows' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function recordImportedRow(int $rowNumber, string $record, array $row = []): void
    {
        $this->importReport['successful_rows'][] = [
            'row_number' => $rowNumber,
            'record' => $record,
            'row' => $row,
        ];

        $this->importReport['imported_count'] = count($this->importReport['successful_rows']);
    }

    /**
     * @param  array<int, string>|string  $reasons
     */
    /**
     * @param  array<int, string>|string  $reasons
     * @param  array<string, mixed>  $row
     */
    protected function recordRejectedRow(int $rowNumber, string $record, array|string $reasons, array $row = []): void
    {
        $this->importReport['failed_rows'][] = [
            'row_number' => $rowNumber,
            'record' => $record !== '' ? $record : __('messages.unknown_record'),
            'reasons' => Arr::wrap($reasons),
            'row' => $row,
        ];

        $this->importReport['rejected_count'] = count($this->importReport['failed_rows']);
    }

    /**
     * @return array<int, string>
     */
    protected function importFailureMessages(Throwable $exception): array
    {
        if ($exception instanceof ValidationException) {
            return collect($exception->errors())
                ->flatten()
                ->map(fn (mixed $message): string => (string) $message)
                ->values()
                ->all();
        }

        return [$exception->getMessage() ?: __('messages.import_failed_unexpectedly')];
    }

    protected function downloadImportReport(ImportReportExportService $exporter): BinaryFileResponse
    {
        $report = $exporter->create($this->importReport, app()->getLocale());

        ImportUpload::create([
            'uploaded_by_user_id' => Auth::id(),
            'module' => (string) ($this->importReport['module'] ?? 'import'),
            'original_filename' => $this->importReport['original_filename'] ?? null,
            'report_filename' => $report['filename'],
            'report_path' => $report['relative_path'],
            'total_rows' => (int) ($this->importReport['total_rows'] ?? 0),
            'imported_count' => (int) ($this->importReport['imported_count'] ?? 0),
            'rejected_count' => (int) ($this->importReport['rejected_count'] ?? 0),
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()
            ->download($report['path'], $report['filename'], [
                'Content-Type' => ImportReportExportService::CONTENT_TYPE,
            ]);
    }
}
