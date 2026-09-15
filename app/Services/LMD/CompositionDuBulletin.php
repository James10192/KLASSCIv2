<?php

declare(strict_types=1);

namespace App\Services\LMD;

use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDResultatECUE;
use App\Models\ESBTPLMDResultatUE;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Ce que le bulletin retient d'une maquette, et ce qu'il doit oublier.
 *
 * `updateOrCreate` ajoute et met à jour, il n'enlève jamais. Une unité retirée
 * de la maquette laissait donc sa ligne de résultat derrière elle : elle
 * continuait d'être IMPRIMÉE sur le bulletin, avec sa moyenne et ses crédits,
 * alors que ni le total des crédits ni la moyenne générale ne la comptaient —
 * ceux-ci se recalculent sur la composition relue. Le document montrait une
 * unité acquise dont les crédits manquaient au décompte, sans qu'aucune erreur
 * ne soit levée.
 *
 * Sorti du service du bulletin, qui dépassait mille lignes.
 */
final class CompositionDuBulletin
{
    /**
     * Les lignes à retirer — la décision seule, sans la base.
     *
     * Le cas qui compte est celui de la composition VIDE. Il se produit pour de
     * bon (une maquette qu'on vient de vider) mais aussi, et bien plus souvent,
     * de façon passagère :
     *
     * - `LMDCleanupService` supprime les liens parcours-unité PUIS met les
     *   unités à la corbeille ; entre les deux, la maquette ne rend rien. Le
     *   couple « nettoyer puis réimporter » est le geste que la documentation du
     *   dépôt décrit comme normal.
     * - la composition ne retient que les matières actives : désactiver les
     *   matières d'une unité — geste d'administration courant — vide sa liste
     *   d'éléments.
     *
     * Vider le bulletin dans ces fenêtres-là effacerait des résultats, note de
     * seconde session comprise, pour un état qui n'a duré qu'un instant. On
     * garde donc les lignes et on le dit : c'est l'appelant qui journalise.
     *
     * @param  array<int, int>  $idsRetenus
     * @param  array<int, int>  $idsPresents
     * @return array<int, int>
     */
    public static function idsAElaguer(array $idsRetenus, array $idsPresents): array
    {
        if ($idsRetenus === []) {
            return [];
        }

        return array_values(array_diff($idsPresents, $idsRetenus));
    }

    /**
     * @param  array<int, ESBTPLMDResultatUE>  $resultatsRetenus
     */
    public function elaguerLesUnites(ESBTPLMDBulletin $bulletin, array $resultatsRetenus): void
    {
        $presents = ESBTPLMDResultatUE::query()
            ->where('bulletin_id', $bulletin->id)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $idsRetenus = array_map(static fn (ESBTPLMDResultatUE $r): int => (int) $r->id, $resultatsRetenus);
        $retirees = self::idsAElaguer($idsRetenus, $presents);

        if ($idsRetenus === [] && $presents !== []) {
            Log::warning('Composition de maquette vide : bulletin laissé intact', [
                'bulletin_id' => $bulletin->id,
                'classe_id' => $bulletin->classe_id,
                'semestre' => $bulletin->semestre,
                'lignes_conservees' => count($presents),
            ]);

            return;
        }

        if ($retirees === []) {
            return;
        }

        // Les éléments ne tombent pas avec leur unité : pas de cascade sur une
        // suppression en douceur. Sans cette ligne, ils resteraient visibles
        // sous `$bulletin->resultatsECUEs`, orphelins de leur unité.
        ESBTPLMDResultatECUE::query()->whereIn('resultat_ue_id', $retirees)->delete();
        ESBTPLMDResultatUE::query()->whereIn('id', $retirees)->delete();
    }

    /**
     * @param  array<int, ESBTPLMDResultatECUE>  $resultatsRetenus
     */
    public function elaguerLesElements(ESBTPLMDResultatUE $resultatUE, array $resultatsRetenus): void
    {
        $presents = ESBTPLMDResultatECUE::query()
            ->where('resultat_ue_id', $resultatUE->id)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $idsRetenus = array_map(static fn (ESBTPLMDResultatECUE $r): int => (int) $r->id, $resultatsRetenus);
        $retires = self::idsAElaguer($idsRetenus, $presents);

        if ($idsRetenus === [] && $presents !== []) {
            Log::warning('Unité sans élément dans la maquette : lignes laissées intactes', [
                'resultat_ue_id' => $resultatUE->id,
                'unite_enseignement_id' => $resultatUE->unite_enseignement_id,
                'lignes_conservees' => count($presents),
            ]);

            return;
        }

        if ($retires === []) {
            return;
        }

        ESBTPLMDResultatECUE::query()->whereIn('id', $retires)->delete();
    }

    /**
     * Un `updateOrCreate` qui voit les lignes mises à la corbeille.
     *
     * `esbtp_lmd_resultats_ues` et `esbtp_lmd_resultats_ecues` portent chacune
     * un index UNIQUE en base — et un index MySQL compte les lignes supprimées
     * en douceur, alors qu'`updateOrCreate` ne les voit pas. Une unité retirée
     * de la maquette puis remise y produirait donc une insertion refusée
     * (`Duplicate entry`), et toute la génération du bulletin tomberait.
     *
     * On ressort la ligne de la corbeille au lieu d'en créer une seconde. Elle
     * revient avec ce qu'elle portait — une note de seconde session, par
     * exemple — plutôt que remise à zéro.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $requete
     * @param  array<string, mixed>  $cles
     * @param  array<string, mixed>  $valeurs
     * @return TModel
     */
    public function reprendreOuCreer(Builder $requete, array $cles, array $valeurs)
    {
        $existant = $requete->clone()->withTrashed()->where($cles)->first();

        if ($existant === null) {
            return $requete->clone()->create($cles + $valeurs);
        }

        if ($existant->trashed()) {
            $existant->restore();
        }

        $existant->update($valeurs);

        return $existant;
    }
}
