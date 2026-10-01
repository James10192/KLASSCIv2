<?php

namespace App\Domain\Inscriptions;

use App\Models\ESBTPInscription;

/**
 * Ce qui empêche de valider une inscription, avant de l'essayer.
 *
 * Une seule lecture, partagée par la CLI (une inscription, ou en groupe) et par
 * Nanan : un versement EN ATTENTE ne vaut jamais un versement validé, et une
 * inscription sans versement validé n'est jamais validée en groupe
 * (rule inscriptions.md). ESBTPInscriptionService::validerInscription()
 * revérifie le versement au moment d'écrire ; ce prédicat ne sert qu'à dire
 * POURQUOI avant d'y aller.
 */
class ObstacleALaValidation
{
    public const DEJA_VALIDEE = 'already_validated';
    public const PAIEMENT_EN_ATTENTE = 'paiement_en_attente';
    public const SANS_PAIEMENT = 'sans_paiement';

    public function pour(ESBTPInscription $inscription): ?string
    {
        if ($inscription->status === 'active' && $inscription->workflow_step === 'etudiant_cree') {
            return self::DEJA_VALIDEE;
        }

        if ($inscription->paiements()->where('status', 'validé')->exists()) {
            return null;
        }

        return $inscription->paiements()->where('status', 'en_attente')->exists()
            ? self::PAIEMENT_EN_ATTENTE
            : self::SANS_PAIEMENT;
    }

    public static function libelle(string $obstacle): string
    {
        return match ($obstacle) {
            self::DEJA_VALIDEE => 'déjà validée',
            self::PAIEMENT_EN_ATTENTE => 'versement encore en attente de validation',
            default => 'aucun versement validé',
        };
    }
}
