<?php

namespace App\Services;

use Illuminate\Support\Str;
use InvalidArgumentException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class BulkImportTemplateService
{
    /**
     * @return array{path: string, filename: string}
     */
    public function create(string $type, string $locale): array
    {
        $definition = $this->definition($type, $locale);
        $path = $this->temporaryPath($definition['filename']);

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName($definition['sheet']);
        $writer->addRow(Row::fromValues([$definition['title']]));
        $writer->addRow(Row::fromValues($definition['headers']));
        $writer->addRow(Row::fromValues($definition['sample']));
        $writer->close();

        return [
            'path' => $path,
            'filename' => $definition['filename'],
        ];
    }

    /**
     * @return array{title: string, sheet: string, filename: string, headers: array<int, string>, sample: array<int, string>}
     */
    private function definition(string $type, string $locale): array
    {
        $swahili = $locale === 'sw';

        return match ($type) {
            'members' => [
                'title' => $swahili ? 'TAG-CICC - Kiolezo cha Kupakia Washirika' : 'TAG-CICC - Members Import Template',
                'sheet' => $swahili ? 'Washirika' : 'Members',
                'filename' => 'tag-cicc-members-template.xlsx',
                'headers' => $swahili
                    ? ['jina_la_kwanza', 'jina_la_kati', 'jina_la_mwisho', 'jinsia', 'tarehe_ya_kuzaliwa', 'simu', 'barua_pepe', 'eneo', 'kanda', 'idara']
                    : ['first_name', 'middle_name', 'last_name', 'gender', 'date_of_birth', 'phone_number', 'email', 'residential_area', 'zone', 'departments'],
                'sample' => $swahili
                    ? ['Neema', 'Grace', 'Adam', 'mwanamke', '1990-01-01', '0654849299', 'neema.adam@tag-cicc.or.tz', 'Changombe', 'Changombe', 'Maendeleo; Sala na Maombezi']
                    : ['Neema', 'Grace', 'Adam', 'female', '1990-01-01', '0654849299', 'neema.adam@tag-cicc.or.tz', 'Changombe', 'Changombe', 'Maendeleo; Prayer'],
            ],
            'departments' => [
                'title' => $swahili ? 'TAG-CICC - Kiolezo cha Kupakia Idara' : 'TAG-CICC - Departments Import Template',
                'sheet' => $swahili ? 'Idara' : 'Departments',
                'filename' => 'tag-cicc-departments-template.xlsx',
                'headers' => $swahili
                    ? ['jina_la_idara', 'maelezo', 'inategemea_umri', 'umri_wa_chini', 'umri_wa_juu', 'jinsia']
                    : ['name', 'description', 'is_age_based', 'minimum_age', 'maximum_age', 'gender_rule'],
                'sample' => $swahili
                    ? ['Media', 'Idara ya matangazo na mawasiliano', 'hapana', '', '', '']
                    : ['Media', 'Announcements and communications department', 'no', '', '', ''],
            ],
            'zones' => [
                'title' => $swahili ? 'TAG-CICC - Kiolezo cha Kupakia Kanda' : 'TAG-CICC - Zones Import Template',
                'sheet' => $swahili ? 'Kanda' : 'Zones',
                'filename' => 'tag-cicc-zones-template.xlsx',
                'headers' => $swahili
                    ? ['jina_la_kanda', 'maelezo']
                    : ['name', 'description'],
                'sample' => $swahili
                    ? ['Mbagala', 'Washirika wa eneo la Mbagala']
                    : ['Mbagala', 'Members living around Mbagala'],
            ],
            default => throw new InvalidArgumentException('Unknown import template type.'),
        };
    }

    private function temporaryPath(string $filename): string
    {
        $directory = storage_path('app/private/import-templates');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory.'/'.Str::uuid().'-'.$filename;
    }
}
