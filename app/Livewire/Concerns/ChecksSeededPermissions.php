<?php

namespace App\Livewire\Concerns;

use Spatie\Permission\Models\Permission;

trait ChecksSeededPermissions
{
    protected function permissionsAreUnseeded(): bool
    {
        return ! Permission::query()->exists();
    }
}
