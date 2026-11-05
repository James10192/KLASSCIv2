<?php

namespace App\Domain\Bulletins;

use App\Domain\BtsTroncCommun\BtsAnnualRankingPolicy;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use App\Services\RankingService;

/**
 * La moyenne et le rang d'une année BTS, tels que les impriment le bulletin et
 * la page des résultats d'un étudiant.
 *
 * La fiche étudiant faisait son propre calcul : la moyenne simple des
 * `moyenne_generale` de ses bulletins semestriels, et le rang du dernier
 * bulletin. Or `moyenne_generale` est la moyenne BRUTE (sans la note
 * d'assiduité), et la moyenne annuelle suit les poids réglés par l'école
 * (S1 ×1, S2 ×2 à ESBTP Abidjan) et la règle du tronc commun. Mesuré sur
 * esbtp-abidjan, sur un étudiant de première année : la fiche affichait 9,95
 * et le rang 34/47 (rang du semestre 2), le bulletin et les résultats 10,02
 * et le rang annuel 33.
 *
 * Ce calcul-ci est celui de `ESBTPResultatController::resultatEtudiant()` :
 * le snapshot annuel pour la moyenne, `RankingService` sur la classe de rang
 * choisie par l'école pour le rang. Une année incomplète prend le rang
 * CALCULÉ de son seul semestre, jamais un rang stocké sur un bulletin.
 */
final class BilanAnnuelBts
{
    private const ANNEE_COMPLETE = ['annual_complete', 'annual_complete_no_coefficients'];

    public function __construct(
        private BtsCurrentResultSnapshotService $snapshots,
        private RankingService $classement,
        private BtsAnnualRankingPolicy $classeDeRang,
    ) {
    }

    /**
     * @return array{
     *     moyenne: float|null,
     *     complet: bool,
     *     provisoire: bool,
     *     semestres: array{semestre1: float|null, semestre2: float|null},
     *     rang: int|null,
     *     effectif: int|null,
     *     snapshot: array
     * }
     */
    public function pour(int $etudiantId, int $classeId, int $anneeId): array
    {
        $snapshot = $this->snapshots->getAnnualSnapshot($etudiantId, $classeId, $anneeId);
        $etat = $snapshot['state'] ?? null;
        $complet = in_array($etat, self::ANNEE_COMPLETE, true);
        $carte = $snapshot['class_map'] ?? [];

        $position = null;
        if ($complet && ! empty($carte['semestre1_classe_id']) && ! empty($carte['semestre2_classe_id'])) {
            $position = $this->classement->calculerRangPourEtudiant(
                $etudiantId, $this->classeDeRang->classeDeRang($carte), $anneeId, 'annuel'
            );
        } elseif ($etat === 'annual_incomplete' && ($semestre = $snapshot['primary_semester'] ?? null)) {
            $position = $this->classement->calculerRangPourEtudiant(
                $etudiantId, (int) ($carte[$semestre.'_classe_id'] ?? $classeId), $anneeId, $semestre
            );
        }

        return [
            'moyenne' => $snapshot['effective_total'] ?? null,
            'complet' => $complet,
            'provisoire' => $etat === 'annual_incomplete',
            'semestres' => [
                'semestre1' => $snapshot['semester_snapshots']['semestre1']['effective_total'] ?? null,
                'semestre2' => $snapshot['semester_snapshots']['semestre2']['effective_total'] ?? null,
            ],
            'rang' => $position['rang'] ?? null,
            'effectif' => $position['total'] ?? null,
            // Le snapshot complet, pour que la fiche ne le recalcule pas.
            'snapshot' => $snapshot,
        ];
    }
}
