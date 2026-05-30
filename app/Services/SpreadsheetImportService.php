<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ODS\Reader as OdsReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class SpreadsheetImportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function rows(UploadedFile $file): array
    {
        return collect($this->rowsWithMetadata($file))
            ->pluck('data')
            ->all();
    }

    /**
     * @return array<int, array{row_number: int, data: array<string, mixed>}>
     */
    public function rowsWithMetadata(UploadedFile $file): array
    {
        $reader = $this->readerFor($file);
        $reader->open($file->getRealPath());

        $headers = [];
        $rows = [];
        $rowNumber = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowNumber++;
                $values = $row->toArray();

                if ($this->isEmptyRow($values)) {
                    continue;
                }

                if ($headers === []) {
                    if ($this->isTitleRow($values)) {
                        continue;
                    }

                    $headers = array_map(fn (mixed $header): string => $this->normalizeHeader((string) $header), $values);

                    continue;
                }

                $mapped = [];

                foreach ($headers as $index => $header) {
                    if ($header === '') {
                        continue;
                    }

                    $mapped[$header] = $values[$index] ?? null;
                }

                if (! $this->isEmptyRow($mapped)) {
                    $rows[] = [
                        'row_number' => $rowNumber,
                        'data' => $mapped,
                    ];
                }
            }

            break;
        }

        $reader->close();

        return $rows;
    }

    private function readerFor(UploadedFile $file): ReaderInterface
    {
        return match (Str::lower($file->getClientOriginalExtension())) {
            'csv', 'txt' => new CsvReader,
            'ods' => new OdsReader,
            default => new XlsxReader,
        };
    }

    private function normalizeHeader(string $header): string
    {
        return Str::of($header)
            ->trim()
            ->lower()
            ->replace([' ', '-'], '_')
            ->snake()
            ->toString();
    }

    /**
     * @param  array<mixed>  $values
     */
    private function isTitleRow(array $values): bool
    {
        $filledCells = collect($values)
            ->filter(fn (mixed $value): bool => trim((string) $value) !== '')
            ->count();

        return $filledCells === 1;
    }

    /**
     * @param  array<mixed>  $values
     */
    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if ($value instanceof DateTimeInterface) {
                return false;
            }

            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
