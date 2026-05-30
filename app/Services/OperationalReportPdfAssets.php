<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\File;

class OperationalReportPdfAssets
{
    /**
     * @return array<string, mixed>
     */
    public function branding(User $downloadedBy, mixed $downloadedAt): array
    {
        return [
            'downloadedBy' => $downloadedBy,
            'downloadedAt' => $downloadedAt,
            'downloadedByName' => $this->userDisplayName($downloadedBy),
            'localChurchLogo' => $this->imageDataUri(public_path('images/tag-cicc-logo.png')),
            'motherChurchLogo' => $this->imageDataUri($this->firstExistingLogoPath([
                public_path('images/tag-mother-logo.png'),
                public_path('images/tag-cicc-icon.png'),
                public_path('images/tag-cicc-logo.png'),
            ])),
        ];
    }

    public function userDisplayName(?User $user): string
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

    /**
     * @param  array<int, string>  $paths
     */
    private function firstExistingLogoPath(array $paths): string
    {
        foreach ($paths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }

        return '';
    }

    private function imageDataUri(string $path): ?string
    {
        if ($path === '' || ! File::exists($path)) {
            return null;
        }

        $contents = File::get($path);
        $mimeType = File::mimeType($path) ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }
}
