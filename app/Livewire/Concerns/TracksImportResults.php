<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Throwable;

trait TracksImportResults
{
    /**
     * @var array<string, mixed>
     */
    public array $importReport = [];

    protected function startImportReport(string $module, int $totalRows): void
    {
        $this->importReport = [
            'module' => $module,
            'total_rows' => $totalRows,
            'imported_count' => 0,
            'rejected_count' => 0,
            'successful_rows' => [],
            'failed_rows' => [],
        ];
    }

    protected function recordImportedRow(int $rowNumber, string $record): void
    {
        $this->importReport['successful_rows'][] = [
            'row_number' => $rowNumber,
            'record' => $record,
        ];

        $this->importReport['imported_count'] = count($this->importReport['successful_rows']);
    }

    /**
     * @param  array<int, string>|string  $reasons
     */
    protected function recordRejectedRow(int $rowNumber, string $record, array|string $reasons): void
    {
        $this->importReport['failed_rows'][] = [
            'row_number' => $rowNumber,
            'record' => $record !== '' ? $record : __('messages.unknown_record'),
            'reasons' => Arr::wrap($reasons),
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
}
