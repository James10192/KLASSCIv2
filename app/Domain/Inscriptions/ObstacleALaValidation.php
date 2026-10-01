<?php

namespace App\Domain\Inscriptions;

use App\Models\ESBTPInscription;
use App\Services\InscriptionWorkflowService;

/**
 * Ce qui empêche de valider une inscription, avant de l'essayer.
 *
 * Une seule lecture, partagée par la CLI (une inscription, ou en groupe) et par
 * Nanan, alignée sur la validation groupée de l'écran
 * (ESBTPInscriptionService::processBulkValidation) :
 *  - une inscription ANNULÉE ne se revalide jamais, même avec un versement
 *    validé (cas courant après un avoir) : la valider réactiverait l'élève et
 *    son compte ;
 *  - un élève qui a déjà une AUTRE inscription active la même année n'en reçoit
 *    pas une seconde (même requête que l'écran) ;
 *  - un versement EN ATTENTE ne vaut jamais un versement validé, et une
 *    inscription sans versement validé n'est jamais validée (rule inscriptions.md) ;
 *  - une classe pleine refuse, avec la même dérogation que l'écran
 *    (InscriptionWorkflowService::checkClassAvailability).
 */
class ObstacleALaValidation
{
    public const DEJA_VALIDEE = 'already_validated';
    public const ANNULEE = 'inscription_annulee';
    public const AUTRE_INSCRIPTION_ACTIVE = 'autre_inscription_active';
    public const PAIEMENT_EN_ATTENTE = 'paiement_en_attente';
    public const SANS_PAIEMENT = 'sans_paiement';
    public const CLASSE_PLEINE = 'classe_pleine';

    public function __construct(private InscriptionWorkflowService $workflow)
    {
    }

    public function pour(ESBTPInscription $inscription): ?string
    {
        return $this->horsPlaces($inscription)
            ?? ($this->placesRestantes($inscription) === 0 ? self::CLASSE_PLEINE : null);
    }

    /** Tous les obstacles sauf la capacité de la classe, qui se compte en lot. */
    public function horsPlaces(ESBTPInscription $inscription): ?string
    {
        if ($inscription->status === 'active' && $inscription->workflow_step === 'etudiant_cree') {
            return self::DEJA_VALIDEE;
        }
        if (in_array($inscription->status, ESBTPInscription::STATUTS_ANNULES, true)) {
            return self::ANNULEE;
        }

        $autre = ESBTPInscription::where('etudiant_id', $inscription->etudiant_id)
            ->where('annee_universitaire_id', $inscription->annee_universitaire_id)
            ->where('status', 'active')
            ->where('id', '!=', $inscription->id)
            ->exists();
        if ($autre) {
            return self::AUTRE_INSCRIPTION_ACTIVE;
        }

        if ($inscription->paiements()->where('status', 'validé')->exists()) {
            return null;
        }

        return $inscription->paiements()->where('status', 'en_attente')->exists()
            ? self::PAIEMENT_EN_ATTENTE
            : self::SANS_PAIEMENT;
    }

    /**
     * Places encore libres dans la classe de l'inscription, au sens de l'écran :
     * null = pas de limite (ou dérogation accordée), 0 = classe pleine.
     */
    public function placesRestantes(ESBTPInscription $inscription): ?int
    {
        $dispo = $this->workflow->checkClassAvailability($inscription->classe_id);
        if (! ($dispo['available'] ?? false)) {
            return 0;
        }
        if ($dispo['warning'] ?? false) {
            return null;
        }

        return isset($dispo['places_restantes']) ? max(0, (int) $dispo['places_restantes']) : null;
    }

    public static function libelle(string $obstacle): string
    {
        return match ($obstacle) {
            self::DEJA_VALIDEE => 'déjà validée',
            self::ANNULEE => 'inscription annulée',
            self::AUTRE_INSCRIPTION_ACTIVE => 'l’élève a déjà une autre inscription active cette année',
            self::PAIEMENT_EN_ATTENTE => 'versement encore en attente de validation',
            self::CLASSE_PLEINE => 'classe pleine',
            default => 'aucun versement validé',
        };
    }
}
