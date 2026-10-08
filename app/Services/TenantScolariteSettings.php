<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;

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

    /**
     * Verification du contact (e-mail ou WhatsApp) des demandes deposees sur le
     * portail public. Desactivee par defaut : c'est une politique d'ecole.
     */
    public const VERIFICATION_CONTACT = 'inscriptions.portail.verification_contact';

    /**
     * Verification WhatsApp inversee : la famille envoie elle-meme le code au
     * numero de l'ecole depuis un lien wa.me, au lieu de le recevoir. Ecrire a
     * des inconnus est ce qui fait bloquer un numero WhatsApp Web ; ici la
     * conversation part de la famille. Desactivee par defaut, comme toute
     * politique d'ecole ; a defaut d'un numero propre a l'application, MailPulse
     * refuse et le code repart dans l'autre sens.
     */
    public const VERIFICATION_WHATSAPP_INVERSE = 'inscriptions.portail.verification_whatsapp_inverse';

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

        // Les anciennes bases peuvent encore porter l'audience sur la catégorie,
        // tandis que les nouvelles configurations la portent par combinaison.
        // Une seule portée restreinte suffit à rendre le statut nouveau/ancien
        // nécessaire au moment d'inscrire un étudiant.
        if (ESBTPFraisConfiguration::query()
            ->where('is_active', true)
            ->whereIn('audience', [
                ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
                ESBTPFraisCategory::AUDIENCE_ANCIENS,
            ])
            ->exists()) {
            return true;
        }

        return ESBTPFraisCategory::query()
            ->where('is_active', true)
            ->whereIn('audience', [
                ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
                ESBTPFraisCategory::AUDIENCE_ANCIENS,
            ])
            ->exists();
    }

    public function verificationContactActive(): bool
    {
        return $this->flag(self::VERIFICATION_CONTACT);
    }

    public function verificationWhatsappInverse(): bool
    {
        return $this->flag(self::VERIFICATION_WHATSAPP_INVERSE);
    }

    private function flag(string $key, string $default = '0'): bool
    {
        $value = SettingsHelper::get($key, $default);

        return $value === true || $value === 1 || $value === '1';
    }
}
