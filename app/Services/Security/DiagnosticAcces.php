<?php

namespace App\Services\Security;

use App\Models\User;
use App\Services\NotesWindowGuard;
use App\Services\PermissionRegistry;

final class DiagnosticAcces
{
    public function __construct(
        private readonly PermissionRegistry $registry,
        private readonly NotesWindowGuard $fenetres,
    ) {}

    public function interpreter(
        bool $actif,
        bool $aLeDroit,
        string $nom,
        string $libelleDroit,
        bool $fenetreFermee = false,
        bool $configAbsente = false,
    ): VerdictAcces {
        if (! $actif) {
            return new VerdictAcces(
                false,
                VerdictAcces::COMPTE_INACTIF,
                $nom.' a un compte désactivé. Réactivez-le depuis le personnel.',
                $libelleDroit,
            );
        }

        if (! $aLeDroit) {
            return new VerdictAcces(
                false,
                VerdictAcces::PERMISSION_MANQUANTE,
                $nom.' n\'a pas le droit « '.$libelleDroit.' ».',
                $libelleDroit,
            );
        }

        if ($fenetreFermee) {
            return new VerdictAcces(
                false,
                VerdictAcces::FENETRE_FERMEE,
                'La fenêtre de saisie des notes est fermée pour cette classe.',
                $libelleDroit,
            );
        }

        if ($configAbsente) {
            return new VerdictAcces(
                false,
                VerdictAcces::CONFIG_ABSENTE,
                'Un réglage nécessaire n\'est pas encore posé. Ouvrez les paramètres de l\'établissement.',
                $libelleDroit,
            );
        }

        return new VerdictAcces(
            true,
            VerdictAcces::OK,
            $nom.' peut « '.$libelleDroit.' ».',
            $libelleDroit,
        );
    }

    public function pour(User $cible, string $permission, ?int $classeId = null): VerdictAcces
    {
        $canon = $this->registry->canonicalize($permission);
        $libelle = $this->registry->permissionMeta($canon)['label'] ?? 'cette action';
        $nom = trim($cible->name) !== '' ? $cible->name : 'Cette personne';
        $fenetreFermee = $classeId !== null
            && in_array($canon, ['notes.create', 'notes.edit'], true)
            && ! $this->fenetres->canWrite($cible, $classeId);

        return $this->interpreter(
            (bool) $cible->is_active,
            $cible->can($canon),
            $nom,
            $libelle,
            $fenetreFermee,
        );
    }
}
