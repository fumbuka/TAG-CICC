<?php

namespace App\Http\Controllers;

use App\Models\ImportUpload;
use App\Services\ImportReportExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportUploadReportController extends Controller
{
    public function __invoke(ImportUpload $importUpload): BinaryFileResponse
    {
        abort_unless(is_file(storage_path('app/private/'.$importUpload->report_path)), 404);

        return response()->download(
            storage_path('app/private/'.$importUpload->report_path),
            $importUpload->report_filename,
            ['Content-Type' => ImportReportExportService::CONTENT_TYPE],
        );
    }
}
