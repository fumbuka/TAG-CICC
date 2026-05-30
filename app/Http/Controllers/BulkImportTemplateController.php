<?php

namespace App\Http\Controllers;

use App\Services\BulkImportTemplateService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BulkImportTemplateController extends Controller
{
    public function __invoke(string $type, BulkImportTemplateService $templates): BinaryFileResponse
    {
        abort_unless(in_array($type, ['members', 'departments', 'zones'], true), 404);
        abort_unless(Gate::allows($this->requiredPermission($type)), 403);

        $template = $templates->create($type, app()->getLocale());

        return response()
            ->download($template['path'], $template['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }

    private function requiredPermission(string $type): string
    {
        return match ($type) {
            'members' => 'members.import',
            'departments' => 'departments.manage',
            'zones' => 'zones.manage',
        };
    }
}
