<?php

namespace App\Domain\Notes;

use App\Models\ESBTPEvaluation;
use App\Models\User;

/**
 * Changer le statut d'une évaluation — l'annuler, la réactiver, ou poser un
 * statut choisi — et en tirer les conséquences sur les moyennes.
 *
 * L'annulation retire les notes de l'évaluation de toute moyenne, la
 * réactivation les y remet, sans qu'aucune note ne soit enregistrée : rien ne
 * relance donc l'observateur des notes, et la moyenne enregistrée, qui
 * l'emporte sur les notes, gardait l'état d'avant. Chaque changement de statut
 * passe ici, et donc par {@see RecalculApresDeplacement::apresChangementDeStatut()} :
 * un écran qui annule une évaluation ne peut plus oublier le recalcul, faute
 * d'autre entrée.
 *
 * La publication suit le statut au même endroit : annuler dépublie, planifier
 * publie, réactiver publie selon le choix de la personne.
 */
final class ChangementDeStatut
{
    /** @return array{avertissement: ?string, liens: array<int, array{libelle:string, url:string}>} */
    public static function annuler(ESBTPEvaluation $evaluation, ?User $auteur): array
    {
        return self::appliquer($evaluation, $auteur, function (ESBTPEvaluation $e) {
            $e->status = ESBTPEvaluation::STATUS_CANCELLED;
            $e->is_published = false;
        });
    }

    /**
     * Le statut rendu dépend de la publication (brouillon tant qu'elle ne
     * l'est pas) : elle est posée d'abord.
     *
     * @return array{avertissement: ?string, liens: array<int, array{libelle:string, url:string}>}
     */
    public static function reactiver(ESBTPEvaluation $evaluation, bool $publier, ?User $auteur): array
    {
        return self::appliquer($evaluation, $auteur, function (ESBTPEvaluation $e) use ($publier) {
            $e->is_published = $publier;
            $e->status = $e->determineAutomaticStatus(null, false);
        });
    }

    /**
     * Un statut choisi. Planifier publie, annuler dépublie ; les autres
     * statuts laissent la publication telle quelle.
     *
     * @return array{avertissement: ?string, liens: array<int, array{libelle:string, url:string}>}
     */
    public static function poser(ESBTPEvaluation $evaluation, string $statut, ?User $auteur): array
    {
        return self::appliquer($evaluation, $auteur, function (ESBTPEvaluation $e) use ($statut) {
            $e->status = $statut;

            if ($statut === ESBTPEvaluation::STATUS_SCHEDULED) {
                $e->is_published = true;
            } elseif ($statut === ESBTPEvaluation::STATUS_CANCELLED) {
                $e->is_published = false;
            }
        });
    }

    /**
     * Le recalcul part après l'enregistrement, hors de toute transaction :
     * un recalcul en échec ne défait pas le changement de statut, il est
     * compté et dit dans l'avertissement.
     *
     * @param  \Closure(ESBTPEvaluation): void  $changer
     * @return array{avertissement: ?string, liens: array<int, array{libelle:string, url:string}>}
     */
    private static function appliquer(ESBTPEvaluation $evaluation, ?User $auteur, \Closure $changer): array
    {
        $statutAvant = $evaluation->status;

        $changer($evaluation);
        $evaluation->updated_by = $auteur?->id;
        $evaluation->save();

        $recalcul = RecalculApresDeplacement::apresChangementDeStatut($evaluation, $statutAvant, $auteur?->id);

        return [
            'avertissement' => MoyennesLaissees::apresChangementDeStatut($recalcul, $evaluation),
            'liens' => MoyennesLaissees::liens($recalcul, $auteur),
        ];
    }
}
