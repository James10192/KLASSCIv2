<?php

namespace App\Domain\Evaluations;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

/**
 * Règles de création d'une évaluation, partagées entre l'écran de création
 * (ESBTPEvaluationController::store) et l'API CLI (CLIEvaluationCreationController).
 *
 * Une seule source : les bornes, les types, les périodes propres à la classe,
 * le statut initial et la durée par défaut ne sont écrits qu'ici.
 */
final class ReglesCreationEvaluation
{
    /**
     * Règles des champs partagés. L'appelant y ajoute ses propres champs
     * (titre, matière, heures, etc.).
     *
     * @return array<string, mixed>
     */
    public static function champs(?ESBTPClasse $classe): array
    {
        return [
            'type' => ['required', 'string', Rule::in(ESBTPEvaluation::typesSaisissables())],
            'bareme' => 'required|numeric|min:'.ESBTPEvaluation::BAREME_MIN.'|max:'.ESBTPEvaluation::BAREME_MAX,
            'coefficient' => 'required|numeric|min:'.ESBTPEvaluation::COEFFICIENT_MIN.'|max:'.ESBTPEvaluation::COEFFICIENT_MAX,
            'periode' => ['required', Rule::in(array_keys(ESBTPEvaluation::periodesPourClasse($classe)))],
        ];
    }

    /**
     * Statut initial : une évaluation publiée prend son statut automatique,
     * une brouillon reste en brouillon.
     */
    public static function statut(bool $publiee, ESBTPEvaluation $evaluation): string
    {
        return $publiee
            ? $evaluation->determineAutomaticStatus(null, false)
            : ESBTPEvaluation::STATUS_DRAFT;
    }

    /**
     * Durée saisie si elle est positive, sinon celle des heures de l'épreuve.
     */
    public static function dureeMinutes(Carbon $debut, Carbon $fin, mixed $saisie = null): int
    {
        $calculee = (int) $fin->diffInMinutes($debut);
        $duree = $saisie !== null && $saisie !== '' ? (int) $saisie : $calculee;

        return $duree > 0 ? $duree : $calculee;
    }
}
