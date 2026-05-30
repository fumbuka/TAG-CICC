<?php

namespace Tests\Feature;

use App\Services\ImportReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class ImportReportExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_report_excel_contains_successful_and_failed_rows_in_one_sheet(): void
    {
        $this->travelTo('2026-05-30 09:15:00');

        $report = app(ImportReportExportService::class)->create([
            'module' => 'members',
            'total_rows' => 2,
            'imported_count' => 1,
            'rejected_count' => 1,
            'successful_rows' => [
                [
                    'row_number' => 2,
                    'record' => 'Neema Adam',
                    'row' => ['first_name' => 'Neema', 'last_name' => 'Adam'],
                ],
            ],
            'failed_rows' => [
                [
                    'row_number' => 3,
                    'record' => 'Baraka Juma',
                    'reasons' => ['The gender field is invalid.'],
                    'row' => ['first_name' => 'Baraka', 'gender' => 'other'],
                ],
            ],
        ], 'en');

        $reader = new Reader;
        $reader->open($report['path']);
        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }

        $reader->close();

        $this->assertSame('tag-cicc-members-import-report-20260530-091500.xlsx', $report['filename']);
        $this->assertContains(['Successful', 2, 'Neema Adam', '', 'first_name=Neema; last_name=Adam'], $rows);
        $this->assertContains(['Rejected', 3, 'Baraka Juma', 'The gender field is invalid.', 'first_name=Baraka; gender=other'], $rows);
    }
}
