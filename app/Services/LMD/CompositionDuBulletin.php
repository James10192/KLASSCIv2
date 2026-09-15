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
     * Le cas qui compte est celui de la composition VIDE, et il se produit dans
     * des situations ordinaires : désactiver les matières d'une unité vide sa
     * liste d'éléments, et une unité partagée dont la maquette réserve tous ses
     * éléments à l'autre parcours donne le même résultat.
     *
     * Rendre `[]` protège donc les lignes plutôt que de les effacer pour un état
     * qui n'aura duré qu'un instant.
     *
     * Deux issues, selon le rang : au niveau de l'UNITÉ, le cas n'arrive plus
     * jusqu'ici — `LMDBulletinService::refuserSurUneMaquetteVide()` l'intercepte
     * avant toute écriture et lève. Au niveau des ÉLÉMENTS, la garde sert : elle
     * est la condition qu'`elaguerLesElements()` remonte pour que l'unité soit
     * laissée telle quelle.
     *
     * @param  array<int, int>  $idsRetenus
     * @param  array<int, int>  $idsPresents
     * @return array<int, int>
     */
    public static function gelActif(bool $reglage, bool $dejaPublie, bool $aDesUnites): bool
    {
        return $reglage && $dejaPublie && $aDesUnites;
    }

    /**
     * @param  array<int, int>  $idsMaquette
     * @param  array<int, int>  $idsGelees
     * @return array<int, int>
     */
    public static function idsMaquetteSousGel(array $idsMaquette, array $idsGelees): array
    {
        return array_values(array_intersect($idsMaquette, $idsGelees));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $ues
     * @return array{0: \Illuminate\Support\Collection<int, object>, 1: bool}
     */
    public function appliquerGel($ues, ?ESBTPLMDBulletin $existant, bool $reglage): array
    {
        $geler = self::gelActif(
            $reglage,
            (bool) ($existant?->is_published),
            $existant !== null && $existant->resultatsUEs->isNotEmpty()
        );
        if (! $geler) {
            return [$ues, false];
        }

        $idsGelees = $existant->resultatsUEs->pluck('unite_enseignement_id')->map(static fn ($id): int => (int) $id)->all();
        $idsMaquette = $ues->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $retenus = self::idsMaquetteSousGel($idsMaquette, $idsGelees);

        return [$ues->filter(static fn ($ue) => in_array((int) $ue->id, $retenus, true))->values(), true];
    }

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

        // Pas de branche « composition vide » ici : `refuserSurUneMaquetteVide`
        // l'a déjà interceptée AVANT toute écriture, et elle lève. Une garde
        // rejouée ici serait morte, et une branche morte se lit comme un cas
        // possible qu'elle n'est pas.
        if ($retirees === []) {
            return;
        }

        // Une suppression de masse ne déclenche aucun événement de modèle : ni
        // piste d'audit, ni horodatage d'auteur. Ces lignes portent des notes
        // SAISIES — seconde session, note finale retenue — que rien ne
        // recalcule. Elles se retrouvent en corbeille, mais encore faut-il
        // savoir qu'il faut les y chercher.
        Log::warning('Unités retirées de la maquette : lignes sorties du bulletin', [
            'bulletin_id' => $bulletin->id,
            'classe_id' => $bulletin->classe_id,
            'semestre' => $bulletin->semestre,
            'resultats_ue_retires' => $retirees,
        ]);

        // Les éléments ne tombent pas avec leur unité : pas de cascade sur une
        // suppression en douceur. Sans cette ligne, ils resteraient visibles
        // sous `$bulletin->resultatsECUEs`, orphelins de leur unité.
        ESBTPLMDResultatECUE::query()->whereIn('resultat_ue_id', $retirees)->delete();
        ESBTPLMDResultatUE::query()->whereIn('id', $retirees)->delete();
    }

    /**
     * @param  array<int, ESBTPLMDResultatECUE>  $resultatsRetenus
     * @return bool `true` quand la maquette ne rattache plus aucun élément à
     *              cette unité alors qu'elle en portait — l'appelant doit alors
     *              laisser l'unité telle quelle, sans toucher à sa moyenne.
     */
    public function elaguerLesElements(ESBTPLMDResultatUE $resultatUE, array $resultatsRetenus): bool
    {
        $presents = ESBTPLMDResultatECUE::query()
            ->where('resultat_ue_id', $resultatUE->id)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $idsRetenus = array_map(static fn (ESBTPLMDResultatECUE $r): int => (int) $r->id, $resultatsRetenus);
        $retires = self::idsAElaguer($idsRetenus, $presents);

        if ($idsRetenus === [] && $presents !== []) {
            // Conserver les lignes ne suffit pas ici non plus. Si le calcul
            // reprend, il écrit une moyenne vide et un statut « non acquis »
            // sur une unité qui garde ses éléments imprimés avec leurs notes :
            // ses crédits cessent d'être capitalisés alors que le total continue
            // de les compter, et la moyenne générale du bulletin bouge — donc la
            // frontière entre « admis » et « admis sous condition » aussi.
            //
            // Le cas n'a rien de théorique : désactiver les matières d'une unité
            // est un geste d'administration courant, et une unité partagée dont
            // la maquette réserve tous les éléments à l'autre parcours produit
            // exactement cet état.
            Log::warning('Unité sans élément dans la maquette : unité laissée intacte', [
                'resultat_ue_id' => $resultatUE->id,
                'unite_enseignement_id' => $resultatUE->unite_enseignement_id,
                'lignes_conservees' => count($presents),
            ]);

            return true;
        }

        if ($retires === []) {
            return false;
        }

        Log::warning('Éléments retirés de la maquette : lignes sorties de l’unité', [
            'resultat_ue_id' => $resultatUE->id,
            'unite_enseignement_id' => $resultatUE->unite_enseignement_id,
            'resultats_ecue_retires' => $retires,
        ]);

        ESBTPLMDResultatECUE::query()->whereIn('id', $retires)->delete();

        return false;
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
