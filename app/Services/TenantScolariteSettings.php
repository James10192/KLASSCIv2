<?php

namespace App\Services;

use App\Helpers\SettingsHelper;

class TenantScolariteSettings
{
    public const SPLIT_ROLES = 'scolarite.split_roles';
    public const PRINT_REQUIRES_APPROVAL = 'documents.print_requires_approval';
    public const CASHIER_PRE_ENROLLMENT = 'caisse.pre_inscription.enabled';
    public const AGENT_INSCRIPTION_ROLE = 'inscriptions.split_role';

    public function splitRolesEnabled(): bool
    {
        return $this->flag(self::SPLIT_ROLES);
    }

    public function printRequiresApproval(): bool
    {
        return $this->flag(self::PRINT_REQUIRES_APPROVAL);
    }

    public function cashierPreEnrollmentEnabled(): bool
    {
        return $this->flag(self::CASHIER_PRE_ENROLLMENT, '1');
    }

    public function agentInscriptionRoleEnabled(): bool
    {
        return $this->flag(self::AGENT_INSCRIPTION_ROLE);
    }

    private function flag(string $key, string $default = '0'): bool
    {
        $value = SettingsHelper::get($key, $default);

        return $value === true || $value === 1 || $value === '1';
    }
}
