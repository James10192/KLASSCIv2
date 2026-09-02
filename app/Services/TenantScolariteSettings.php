<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPFraisCategory;

class TenantScolariteSettings
{
    public const SPLIT_ROLES = 'scolarite.split_roles';
    public const PRINT_REQUIRES_APPROVAL = 'documents.print_requires_approval';
    public const CLERK_LMD_ACCESS = 'scolarite.clerk_lmd_access';
    public const CLERK_PEDAGOGIE = 'scolarite.clerk_pedagogie';
    public const MANAGE_TEACHERS = 'scolarite.manage_teachers';
    public const CASHIER_PRE_ENROLLMENT = 'caisse.pre_inscription.enabled';
    public const AGENT_INSCRIPTION_ROLE = 'inscriptions.split_role';
    public const REINSCRIPTION_EN_LIGNE = 'reinscriptions.en_ligne.enabled';
    public const CONFIRMER_STATUT_ETABLISSEMENT = 'inscriptions.confirmer_statut_etablissement';

    public function splitRolesEnabled(): bool
    {
        return $this->flag(self::SPLIT_ROLES);
    }

    public function printRequiresApproval(): bool
    {
        return $this->flag(self::PRINT_REQUIRES_APPROVAL);
    }

    public function clerkLmdAccess(): bool
    {
        return $this->flag(self::CLERK_LMD_ACCESS);
    }

    public function clerkPedagogieAccess(): bool
    {
        return $this->flag(self::CLERK_PEDAGOGIE);
    }

    public function manageTeachers(): bool
    {
        return $this->flag(self::MANAGE_TEACHERS);
    }

    public function cashierPreEnrollmentEnabled(): bool
    {
        return $this->flag(self::CASHIER_PRE_ENROLLMENT, '1');
    }

    public function agentInscriptionRoleEnabled(): bool
    {
        return $this->flag(self::AGENT_INSCRIPTION_ROLE);
    }

    public function reinscriptionEnLigneEnabled(): bool
    {
        return $this->flag(self::REINSCRIPTION_EN_LIGNE);
    }

    public function confirmerStatutEtablissement(): bool
    {
        if ($this->flag(self::CONFIRMER_STATUT_ETABLISSEMENT)) {
            return true;
        }

        return ESBTPFraisCategory::query()
            ->where('is_active', true)
            ->where('audience', ESBTPFraisCategory::AUDIENCE_NOUVEAUX)
            ->exists();
    }

    private function flag(string $key, string $default = '0'): bool
    {
        $value = SettingsHelper::get($key, $default);

        return $value === true || $value === 1 || $value === '1';
    }
}
