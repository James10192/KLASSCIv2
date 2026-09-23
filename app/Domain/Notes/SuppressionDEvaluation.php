<?php

namespace App\Domain\Notes;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPSeanceCours;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Supprimer une évaluation, et en tirer les conséquences sur les moyennes.
 *
 * La suppression est douce : les notes restent en base, mais le recalcul ne
 * lit plus que celles d'une évaluation vivante. Sans recalcul, la moyenne
 * enregistrée — qui l'emporte sur les notes — gardait celles d'une évaluation
 * que plus personne ne voit. Les deux écrans qui suppriment une évaluation
 * (la liste des évaluations, et la suppression d'une séance de devoir)
 * passent par ici.
 *
 * Le recalcul part après la suppression, hors de toute transaction : un
 * recalcul en échec ne la défait pas, il est compté et dit.
 */
final class SuppressionDEvaluation
{
    /** @return array{avertissement: ?string, liens: array<int, array{libelle:string, url:string}>} */
    public static function supprimer(ESBTPEvaluation $evaluation, ?User $auteur): array
    {
        $evaluation->delete();

        return self::recalculerApres($evaluation, $auteur);
    }

    /**
     * Une séance, et son devoir de séance s'il y en a un : l'un ne part pas
     * sans l'autre.
     *
     * @return array{avertissement: ?string, liens: array<int, array{libelle:string, url:string}>}
     */
    public static function supprimerLaSeance(ESBTPSeanceCours $seance, ?User $auteur): array
    {
        $devoir = self::devoirDe($seance);

        if ($devoir === null) {
            $seance->delete();

            return ['avertissement' => null, 'liens' => []];
        }

        DB::transaction(function () use ($seance, $devoir) {
            $devoir->delete();
            $seance->delete();
        });

        return self::recalculerApres($devoir, $auteur);
    }

    /**
     * Le devoir qui retient la séance, ou null si elle peut partir.
     * Supprimer une séance emporte son devoir : la même règle
     * ({@see ESBTPEvaluation::isDeletable()}) s'applique.
     */
    public static function devoirQuiRetient(ESBTPSeanceCours $seance): ?ESBTPEvaluation
    {
        $devoir = self::devoirDe($seance);

        return $devoir !== null && ! $devoir->isDeletable() ? $devoir : null;
    }

    /** Le refus nomme le devoir, pour qu'on le retrouve dans la liste. */
    public static function refusDeLaSeance(ESBTPEvaluation $devoir): string
    {
        $date = $devoir->date_evaluation ? ' du '.$devoir->date_evaluation->format('d/m/Y') : '';

        return 'Le devoir « '.($devoir->titre ?: 'sans titre').' »'.$date.' est '.mb_strtolower($devoir->status_label, 'UTF-8')
            .' : annulez-le d\'abord depuis la liste des évaluations, puis supprimez la séance.';
    }

    private static function devoirDe(ESBTPSeanceCours $seance): ?ESBTPEvaluation
    {
        return $seance->type === ESBTPSeanceCours::TYPE_HOMEWORK ? $seance->homeworkEvaluation : null;
    }

    /** @return array{avertissement: ?string, liens: array<int, array{libelle:string, url:string}>} */
    private static function recalculerApres(ESBTPEvaluation $evaluation, ?User $auteur): array
    {
        $recalcul = RecalculApresDeplacement::apresSuppression($evaluation, $auteur?->id);

        return [
            'avertissement' => MoyennesLaissees::apresSuppression($recalcul),
            'liens' => MoyennesLaissees::liens($recalcul, $auteur),
        ];
    }
}
