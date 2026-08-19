<?php

namespace App\Services;

use App\Helpers\SettingsHelper;

class TenantScolariteSettings
{
    public const SPLIT_ROLES = 'scolarite.split_roles';
    public const PRINT_REQUIRES_APPROVAL = 'documents.print_requires_approval';

    public function splitRolesEnabled(): bool
    {
        return $this->flag(self::SPLIT_ROLES);
    }

    public function printRequiresApproval(): bool
    {
        return $this->flag(self::PRINT_REQUIRES_APPROVAL);
    }

    private function flag(string $key): bool
    {
        $value = SettingsHelper::get($key, '0');

        return $value === true || $value === 1 || $value === '1';
    }
}