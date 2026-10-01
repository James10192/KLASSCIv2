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
        if (self::estAnnulee($inscription)) {
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
     * Places encore libres dans la classe de l'inscription, sur SON année :
     * null = pas de limite ou dérogation (permission inscriptions.override_capacity),
     * 0 = classe pleine. Lu sans journaliser : seule l'écriture journalise.
     */
    public function placesRestantes(ESBTPInscription $inscription): ?int
    {
        $etat = $this->capacite($inscription);
        if ($etat === null) {
            return 0;
        }
        if ($etat['places'] === null || ($etat['pleine'] && $etat['derogation'])) {
            return null;
        }

        return max(0, $etat['places'] - $etat['inscrits']);
    }

    /** @return array{classe: \App\Models\ESBTPClasse, inscrits: int, places: ?int, pleine: bool, derogation: bool}|null */
    public function capacite(ESBTPInscription $inscription): ?array
    {
        return $this->workflow->etatDesPlaces($inscription->classe_id, $inscription->annee_universitaire_id);
    }

    /**
     * La règle « une inscription annulée ne se revalide jamais », seule source :
     * les deux goulots d'écriture de l'écran (ESBTPInscriptionService::
     * validerInscription et InscriptionWorkflowService::validateInscription, par
     * où passent la validation unitaire, groupée et avec paiement) la lisent ici.
     */
    public static function estAnnulee(ESBTPInscription $inscription): bool
    {
        return in_array($inscription->status, ESBTPInscription::STATUTS_ANNULES, true);
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
