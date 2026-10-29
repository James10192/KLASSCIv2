<?php

namespace App\Domain\Analytics\Aging;

use App\Domain\Analytics\DTOs\StudentRiskFeatures;

/**
 * Anciennete des impayes : combien est en retard, et depuis combien de temps.
 *
 * Fonction pure sur les etats financiers deja calcules par
 * StudentRiskRepository (meme source que le score de risque, pour que les
 * deux lectures ne se contredisent jamais).
 *
 * Tranches classiques de recouvrement : a jour, 1-30, 31-60, 61-90, plus de
 * 90 jours. Sans echeancier configure (mode degrade), chaque etudiant n'a
 * qu'une echeance unique : l'anciennete mesure alors l'age de l'inscription
 * plus que celui de la dette, et la reponse le dit.
 */
class ReceivablesAging
{
    public const BUCKETS = [
        'a_jour' => ['label' => 'À jour', 'min' => null, 'max' => 0],
        '1_30' => ['label' => '1 à 30 jours', 'min' => 1, 'max' => 30],
        '31_60' => ['label' => '31 à 60 jours', 'min' => 31, 'max' => 60],
        '61_90' => ['label' => '61 à 90 jours', 'min' => 61, 'max' => 90],
        'plus_90' => ['label' => 'Plus de 90 jours', 'min' => 91, 'max' => null],
    ];

    /**
     * @param StudentRiskFeatures[] $students
     */
    public function build(array $students, bool $fallbackMode): array
    {
        $buckets = [];
        foreach (self::BUCKETS as $key => $def) {
            $buckets[$key] = ['tranche' => $key, 'label' => $def['label'], 'etudiants' => 0, 'montant_en_retard' => 0.0];
        }

        $totalRestant = 0.0;
        foreach ($students as $student) {
            $totalRestant += max(0.0, $student->soldeRestant);
            $key = $this->bucketFor($student);
            $buckets[$key]['etudiants']++;
            $buckets[$key]['montant_en_retard'] += $key === 'a_jour' ? 0.0 : max(0.0, $student->overdueAmount);
        }

        $totalEnRetard = array_sum(array_column($buckets, 'montant_en_retard'));

        return [
            'tranches' => array_values($buckets),
            'etudiants' => count($students),
            'total_restant_du' => round($totalRestant, 2),
            'total_en_retard' => round($totalEnRetard, 2),
            'part_plus_90_pct' => $totalEnRetard > 0 ? round(100 * $buckets['plus_90']['montant_en_retard'] / $totalEnRetard, 1) : 0.0,
            'mode_degrade' => $fallbackMode,
            'avertissement' => $fallbackMode
                ? "Aucun échéancier n'est configuré : chaque étudiant n'a qu'une échéance unique. Le retard compte donc depuis cette échéance, pas depuis les tranches réelles de paiement, et les montants tombent vite dans « plus de 90 jours »."
                : null,
        ];
    }

    private function bucketFor(StudentRiskFeatures $student): string
    {
        if ($student->overdueAmount <= 0 || $student->joursRetard <= 0) {
            return 'a_jour';
        }
        foreach (self::BUCKETS as $key => $def) {
            if ($def['min'] !== null && $student->joursRetard >= $def['min'] && ($def['max'] === null || $student->joursRetard <= $def['max'])) {
                return $key;
            }
        }

        return 'plus_90';
    }
}
