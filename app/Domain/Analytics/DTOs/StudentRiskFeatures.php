<?php

namespace App\Domain\Analytics\DTOs;

/**
 * Features financières extraites d'une inscription pour scoring défaut.
 * Toutes les valeurs financières en FCFA. ratioPaye ∈ [0,1].
 */
final class StudentRiskFeatures
{
    public function __construct(
        public readonly int $inscriptionId,
        public readonly int $etudiantId,
        public readonly string $etudiantNom,
        public readonly int $classeId,
        public readonly string $classeNom,
        public readonly float $totalAttendu,
        public readonly float $totalPaye,
        public readonly float $soldeRestant,
        public readonly float $ratioPaye,
        public readonly int $joursRetard,
        public readonly int $nbPaiements,
    ) {}

    public function isPaid(): bool
    {
        return $this->soldeRestant <= 0.0;
    }

    public function toArray(): array
    {
        return [
            'inscription_id' => $this->inscriptionId,
            'etudiant_id' => $this->etudiantId,
            'etudiant_nom' => $this->etudiantNom,
            'classe_id' => $this->classeId,
            'classe_nom' => $this->classeNom,
            'total_attendu' => $this->totalAttendu,
            'total_paye' => $this->totalPaye,
            'solde_restant' => $this->soldeRestant,
            'ratio_paye' => $this->ratioPaye,
            'jours_retard' => $this->joursRetard,
            'nb_paiements' => $this->nbPaiements,
        ];
    }
}
