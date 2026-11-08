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
 * Une seule source : la normalisation de la période, les bornes, les types, les
 * périodes propres à la classe, le remplissage de l'évaluation, le statut initial
 * et la durée par défaut ne sont écrits qu'ici.
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
     * « 1 » (forme de la fenêtre des notes LMD) devient « semestre1 ».
     */
    public static function normaliserPeriode(mixed $periode): mixed
    {
        // Un entier JSON (3) doit se comporter comme la chaîne « 3 ».
        if (is_int($periode)) {
            $periode = (string) $periode;
        }

        return ESBTPEvaluation::periodeSaisie($periode);
    }

    /**
     * Remplit une évaluation neuve à partir de données déjà validées. Les deux
     * chemins (écran, CLI) passent ici : une seule affectation des champs.
     *
     * @param  array{titre: string, description: ?string, type: string, debut: Carbon, fin: Carbon, duree_saisie: mixed, coefficient: float, bareme: float, classe_id: int, matiere_id: int, periode: string, is_published: bool}  $donnees
     */
    public static function remplir(ESBTPEvaluation $evaluation, array $donnees, int $anneeId, ?int $auteurId): void
    {
        $evaluation->titre = $donnees['titre'];
        $evaluation->description = $donnees['description'];
        $evaluation->type = $donnees['type'];
        $evaluation->date_evaluation = $donnees['debut'];
        $evaluation->coefficient = (float) $donnees['coefficient'];
        $evaluation->bareme = $donnees['bareme'];
        $evaluation->duree_minutes = self::dureeMinutes($donnees['debut'], $donnees['fin'], $donnees['duree_saisie']);
        $evaluation->classe_id = $donnees['classe_id'];
        $evaluation->matiere_id = $donnees['matiere_id'];
        $evaluation->periode = $donnees['periode'];
        $evaluation->annee_universitaire_id = $anneeId;
        $evaluation->created_by = $auteurId;
        $evaluation->is_published = $donnees['is_published'];
        $evaluation->status = self::statut((bool) $donnees['is_published'], $evaluation);
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
