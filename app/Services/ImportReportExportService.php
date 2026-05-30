<?php

namespace App\Services;

use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class ImportReportExportService
{
    public const CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * @param  array<string, mixed>  $report
     * @return array{path: string, filename: string}
     */
    public function create(array $report, string $locale): array
    {
        $module = (string) ($report['module'] ?? 'import');
        $filename = sprintf(
            'tag-cicc-%s-import-report-%s.xlsx',
            Str::slug($module),
            now()->format('Ymd-His'),
        );
        $path = $this->temporaryPath($filename);
        $labels = $this->labels($locale);

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName($labels['sheet']);
        $writer->addRow(Row::fromValues(['TAG-CICC']));
        $writer->addRow(Row::fromValues([$labels['title']]));
        $writer->addRow(Row::fromValues([$labels['generated_at'], now()->format('Y-m-d H:i:s')]));
        $writer->addRow(Row::fromValues([$labels['module'], $module]));
        $writer->addRow(Row::fromValues([$labels['total_rows'], (int) ($report['total_rows'] ?? 0)]));
        $writer->addRow(Row::fromValues([$labels['successful'], (int) ($report['imported_count'] ?? 0)]));
        $writer->addRow(Row::fromValues([$labels['failed'], (int) ($report['rejected_count'] ?? 0)]));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues([
            $labels['status'],
            $labels['row_number'],
            $labels['record'],
            $labels['reason'],
            $labels['source_data'],
        ]));

        foreach ($this->rows($report, $labels) as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  array<string, string>  $labels
     * @return array<int, array<int, mixed>>
     */
    private function rows(array $report, array $labels): array
    {
        $successfulRows = collect($report['successful_rows'] ?? [])
            ->map(fn (array $row): array => [
                $labels['uploaded'],
                $row['row_number'] ?? '',
                $row['record'] ?? '',
                '',
                $this->rowData($row['row'] ?? []),
            ]);

        $failedRows = collect($report['failed_rows'] ?? [])
            ->map(fn (array $row): array => [
                $labels['rejected'],
                $row['row_number'] ?? '',
                $row['record'] ?? '',
                collect($row['reasons'] ?? [])->join('; '),
                $this->rowData($row['row'] ?? []),
            ]);

        return $successfulRows
            ->merge($failedRows)
            ->sortBy(fn (array $row): int => (int) $row[1])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowData(array $row): string
    {
        return collect($row)
            ->map(fn (mixed $value, string $key): string => $key.'='.$this->formatValue($value))
            ->join('; ');
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return (string) $value;
    }

    /**
     * @return array<string, string>
     */
    private function labels(string $locale): array
    {
        if ($locale === 'sw') {
            return [
                'sheet' => 'Ripoti',
                'title' => 'Ripoti ya Upload',
                'generated_at' => 'Muda wa ripoti',
                'module' => 'Moduli',
                'total_rows' => 'Jumla ya rows',
                'successful' => 'Zilizofanikiwa',
                'failed' => 'Zilizokataliwa',
                'status' => 'Hali',
                'row_number' => 'Row',
                'record' => 'Taarifa',
                'reason' => 'Sababu',
                'source_data' => 'Data ya row',
                'uploaded' => 'Imefanikiwa',
                'rejected' => 'Imekataliwa',
            ];
        }

        return [
            'sheet' => 'Report',
            'title' => 'Upload Report',
            'generated_at' => 'Report time',
            'module' => 'Module',
            'total_rows' => 'Total rows',
            'successful' => 'Successful',
            'failed' => 'Rejected',
            'status' => 'Status',
            'row_number' => 'Row',
            'record' => 'Record',
            'reason' => 'Reason',
            'source_data' => 'Source row data',
            'uploaded' => 'Successful',
            'rejected' => 'Rejected',
        ];
    }

    private function temporaryPath(string $filename): string
    {
        $directory = storage_path('app/private/import-reports');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory.'/'.Str::uuid().'-'.$filename;
    }
}
