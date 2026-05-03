<?php

namespace App\Services;

/**
 * NoteCalculationService — service unifié de calcul de moyennes (BTS + LMD).
 *
 * Centralise toute la logique mathématique de calcul des moyennes :
 *  - Moyenne d'un étudiant pour une matière (à partir de ses notes d'évaluations).
 *  - Moyenne générale d'un étudiant (pondérée par coefficients matière).
 *  - Moyenne classe pour une évaluation (toutes notes des étudiants).
 *  - Moyenne classe pour une matière (à partir des moyennes étudiants).
 *  - Moyenne LMD d'une UE / d'un semestre (pondérée par crédits ECTS).
 *  - Crédits validés (LMD).
 *  - Mention CAMES standard.
 *
 * Garanties algorithmiques (toutes les méthodes) :
 *  1. Les notes 0 légitimes sont INCLUSES (anti-bug "filter > 0").
 *  2. Les notes sont normalisées sur 20 par leur barème (`(note / bareme) * 20`).
 *  3. Les absences (`is_absent`) sont EXCLUES du calcul (n'affectent ni numérateur ni dénominateur).
 *  4. Garde-fou : barème <= 0 ou coefficient <= 0 = entrée ignorée silencieusement.
 *  5. Arrondi systématique à 2 décimales sur le résultat final.
 *  6. Aucune entrée valide => retourne 0.0 (jamais d'exception, jamais de division par zéro).
 *
 * Service stateless (aucun champ de classe), donc safe en singleton via DI Laravel.
 *
 * Voir docs/architecture/note-calculation-service.md pour les points de migration.
 */
class NoteCalculationService
{
    /**
     * Seuil de validation par défaut (CAMES) pour qu'une note acquière des crédits.
     */
    public const DEFAULT_VALIDATION_THRESHOLD = 10.0;

    /**
     * Calcule la moyenne d'un étudiant pour une matière à partir de ses notes
     * d'évaluations (déjà filtrées sur la matière concernée).
     *
     * Formule : Σ((note / bareme) * 20 * coefficient) / Σ coefficient
     *
     * @param  array<int, array{note?: float|int|string|null, bareme?: float|int|string|null, coefficient?: float|int|string|null, is_absent?: bool}>  $notes
     * @return float Moyenne sur 20 (arrondie à 2 décimales). 0.0 si aucune note exploitable.
     */
    public function studentMatiereAverage(array $notes): float
    {
        $totalPoints = 0.0;
        $totalCoeffs = 0.0;

        foreach ($notes as $n) {
            if (! is_array($n)) {
                continue;
            }
            if (! empty($n['is_absent'])) {
                continue;
            }
            $bareme = (float) ($n['bareme'] ?? 20);
            if ($bareme <= 0) {
                continue;
            }
            $coef = (float) ($n['coefficient'] ?? 1);
            if ($coef <= 0) {
                continue;
            }
            $note = (float) ($n['note'] ?? 0);
            $normalized = ($note / $bareme) * 20.0;
            $totalPoints += $normalized * $coef;
            $totalCoeffs += $coef;
        }

        return $totalCoeffs > 0
            ? round($totalPoints / $totalCoeffs, 2)
            : 0.0;
    }

    /**
     * Moyenne générale pondérée d'un étudiant à partir des moyennes par matière
     * (déjà calculées et déjà normalisées sur 20).
     *
     * Formule : Σ(moyenne_matiere × coefficient_matiere) / Σ coefficient_matiere
     *
     * @param  array<int, array{moyenne?: float|int|string|null, coefficient?: float|int|string|null}>  $matieres
     * @return float Moyenne sur 20 (arrondie à 2 décimales). 0.0 si aucune matière exploitable.
     */
    public function studentGeneralAverage(array $matieres): float
    {
        $totalPoints = 0.0;
        $totalCoeffs = 0.0;

        foreach ($matieres as $m) {
            if (! is_array($m)) {
                continue;
            }
            $coef = (float) ($m['coefficient'] ?? 1);
            if ($coef <= 0) {
                continue;
            }
            $moyenne = (float) ($m['moyenne'] ?? 0);
            $totalPoints += $moyenne * $coef;
            $totalCoeffs += $coef;
        }

        return $totalCoeffs > 0
            ? round($totalPoints / $totalCoeffs, 2)
            : 0.0;
    }

    /**
     * Moyenne d'une classe pour une évaluation (toutes notes des étudiants).
     *
     * Pour chaque note non absente et non null, on normalise sur 20 puis
     * on fait la moyenne arithmétique simple.
     *
     * @param  array<int, array{note?: float|int|string|null, is_absent?: bool}>  $notes
     * @param  float  $bareme  Barème commun de l'évaluation
     * @return float Moyenne sur 20 (arrondie à 2 décimales). 0.0 si pas de note ou barème invalide.
     */
    public function classEvaluationAverage(array $notes, float $bareme): float
    {
        if ($bareme <= 0) {
            return 0.0;
        }

        $total = 0.0;
        $count = 0;

        foreach ($notes as $n) {
            if (! is_array($n)) {
                continue;
            }
            if (! empty($n['is_absent'])) {
                continue;
            }
            if (! array_key_exists('note', $n) || $n['note'] === null) {
                continue;
            }
            $total += ((float) $n['note'] / $bareme) * 20.0;
            $count++;
        }

        return $count > 0
            ? round($total / $count, 2)
            : 0.0;
    }

    /**
     * Moyenne classe pour une matière à partir des moyennes étudiants déjà calculées.
     *
     * Moyenne arithmétique simple sur les valeurs numériques non-null.
     *
     * @param  array<int, float|int|string|null>  $studentAverages
     * @return float Moyenne sur 20 (arrondie à 2 décimales). 0.0 si aucune moyenne valide.
     */
    public function classMatiereAverage(array $studentAverages): float
    {
        $valid = array_filter(
            $studentAverages,
            fn ($v) => $v !== null && is_numeric($v)
        );

        if (empty($valid)) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($valid as $v) {
            $sum += (float) $v;
        }

        return round($sum / count($valid), 2);
    }

    /**
     * LMD : moyenne d'une UE pondérée par les crédits ECTS de ses ECUE.
     *
     * Formule : Σ(moyenne_ecue × credits_ecue) / Σ credits_ecue
     *
     * @param  array<int, array{moyenne?: float|int|string|null, credits?: int|float|string|null}>  $ecues
     * @return float Moyenne sur 20 (arrondie à 2 décimales). 0.0 si aucun crédit valide.
     */
    public function lmdUEAverage(array $ecues): float
    {
        $totalPoints = 0.0;
        $totalCredits = 0;

        foreach ($ecues as $e) {
            if (! is_array($e)) {
                continue;
            }
            $credits = (int) ($e['credits'] ?? 0);
            if ($credits <= 0) {
                continue;
            }
            $moyenne = (float) ($e['moyenne'] ?? 0);
            $totalPoints += $moyenne * $credits;
            $totalCredits += $credits;
        }

        return $totalCredits > 0
            ? round($totalPoints / $totalCredits, 2)
            : 0.0;
    }

    /**
     * LMD : moyenne semestrielle d'un étudiant pondérée par crédits des UE.
     *
     * Formule identique à `lmdUEAverage` (Σ moy*credits / Σ credits) — alias
     * sémantique pour clarifier l'intention dans les bulletins.
     *
     * @param  array<int, array{moyenne?: float|int|string|null, credits?: int|float|string|null}>  $ues
     * @return float Moyenne sur 20 (arrondie à 2 décimales).
     */
    public function lmdSemesterAverage(array $ues): float
    {
        return $this->lmdUEAverage($ues);
    }

    /**
     * LMD : nombre de crédits validés (acquis) à un seuil donné.
     *
     * Une UE est validée si sa moyenne >= seuil (par défaut 10/20).
     * Tous les crédits de cette UE sont alors comptabilisés.
     *
     * @param  array<int, array{moyenne?: float|int|string|null, credits?: int|float|string|null}>  $ues
     * @param  float  $threshold  Seuil de validation (défaut : 10.0)
     */
    public function lmdCreditsValidated(array $ues, float $threshold = self::DEFAULT_VALIDATION_THRESHOLD): int
    {
        $sum = 0;
        foreach ($ues as $ue) {
            if (! is_array($ue)) {
                continue;
            }
            $credits = (int) ($ue['credits'] ?? 0);
            if ($credits <= 0) {
                continue;
            }
            $moyenne = (float) ($ue['moyenne'] ?? 0);
            if ($moyenne >= $threshold) {
                $sum += $credits;
            }
        }
        return $sum;
    }

    /**
     * Mention CAMES standard à partir d'une moyenne sur 20.
     *
     * Seuils :
     *  - >= 16     : "Très Bien"
     *  - >= 14     : "Bien"
     *  - >= 12     : "Assez Bien"
     *  - >= 10     : "Passable"
     *  - sinon     : "Insuffisant"
     */
    public function getMention(float $moyenne): string
    {
        if ($moyenne >= 16) {
            return 'Très Bien';
        }
        if ($moyenne >= 14) {
            return 'Bien';
        }
        if ($moyenne >= 12) {
            return 'Assez Bien';
        }
        if ($moyenne >= 10) {
            return 'Passable';
        }
        return 'Insuffisant';
    }

    /**
     * Alias de `getMention()` pour compatibilité avec la convention
     * `BulletinService::getAppreciation()` historique.
     */
    public function getAppreciation(float $moyenne): string
    {
        return $this->getMention($moyenne);
    }
}
