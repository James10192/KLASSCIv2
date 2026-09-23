<?php

namespace App\Domain\Notes;

use App\Models\ESBTPEvaluation;
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
 * Le recalcul part après la suppression, hors de sa transaction : un
 * recalcul en échec ne la défait pas, il est compté et dit.
 */
final class SuppressionDEvaluation
{
    /**
     * @param  (\Closure(): void)|null  $avecElle  ce qui doit disparaître dans la même transaction
     *                                          (la séance d'un devoir) : l'un ne part pas sans l'autre
     * @return array{avertissement: ?string, liens: array<int, array{libelle:string, url:string}>}
     */
    public static function supprimer(ESBTPEvaluation $evaluation, ?User $auteur, ?\Closure $avecElle = null): array
    {
        DB::transaction(function () use ($evaluation, $avecElle) {
            $evaluation->delete();

            if ($avecElle !== null) {
                $avecElle();
            }
        });

        $recalcul = RecalculApresDeplacement::apresSuppression($evaluation, $auteur?->id);

        return [
            'avertissement' => MoyennesLaissees::apresSuppression($recalcul),
            'liens' => MoyennesLaissees::liens($recalcul, $auteur),
        ];
    }
}
