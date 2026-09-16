<?php

namespace App\Domain\Scolarite;

final class MoyenneEcue
{
    /**
     * @param  list<array{valeur:?float,absent:bool,dispense:bool,famille:string,coefficient:float,bareme:float}>  $notes
     */
    public static function calculer(array $notes, float $poidsCc, float $poidsExamen, bool $absenceCompteZero = true): ?float
    {
        $parFamille = [
            FamilleEvaluation::CC => [],
            FamilleEvaluation::EXAMEN => [],
            FamilleEvaluation::AUTRE => [],
        ];

        foreach ($notes as $note) {
            $nature = NatureDeNote::classifier($note['valeur'] ?? null, (bool) ($note['absent'] ?? false), (bool) ($note['dispense'] ?? false));
            $valeur = NatureDeNote::valeurPourMoyenne($nature, $note['valeur'] ?? null, $absenceCompteZero);
            if ($valeur === null) {
                continue;
            }
            $bareme = ($note['bareme'] ?? 20) > 0 ? (float) $note['bareme'] : 20.0;
            $normalisee = ($valeur / $bareme) * 20;
            $coeff = ($note['coefficient'] ?? 1) > 0 ? (float) $note['coefficient'] : 1.0;
            $famille = $note['famille'] ?? FamilleEvaluation::AUTRE;
            if (! isset($parFamille[$famille])) {
                $famille = FamilleEvaluation::AUTRE;
            }
            $parFamille[$famille][] = ['points' => $normalisee * $coeff, 'coeff' => $coeff];
        }

        $cc = self::moyenneDe($parFamille[FamilleEvaluation::CC]);
        $examen = self::moyenneDe($parFamille[FamilleEvaluation::EXAMEN]);
        $autre = self::moyenneDe($parFamille[FamilleEvaluation::AUTRE]);

        if ($cc !== null && $examen !== null) {
            $total = $poidsCc + $poidsExamen;
            if ($total <= 0) {
                return null;
            }

            return round(($cc * $poidsCc + $examen * $poidsExamen) / $total, 2);
        }

        return $cc ?? $examen ?? $autre;
    }

    /** @param  list<array{points:float,coeff:float}>  $lignes */
    private static function moyenneDe(array $lignes): ?float
    {
        $coeff = 0.0;
        $points = 0.0;
        foreach ($lignes as $ligne) {
            $points += $ligne['points'];
            $coeff += $ligne['coeff'];
        }

        return $coeff > 0 ? round($points / $coeff, 2) : null;
    }
}
