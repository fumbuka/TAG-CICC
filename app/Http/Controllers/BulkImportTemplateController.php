<?php

namespace App\Http\Controllers;

use App\Services\BulkImportTemplateService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BulkImportTemplateController extends Controller
{
    public function __invoke(string $type, BulkImportTemplateService $templates): BinaryFileResponse
    {
        abort_unless(in_array($type, ['members', 'departments', 'zones'], true), 404);

        $template = $templates->create($type, app()->getLocale());

        return response()
            ->download($template['path'], $template['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }
}
