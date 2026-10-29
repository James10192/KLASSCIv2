<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Le contrat serveur du defilement infini, le meme pour toutes les listes.
 *
 * La page complete rend la premiere tranche et le composant x-liste-infinie.
 * Arrive en bas, le composant rappelle la MEME adresse avec les memes filtres,
 * `page=N` et `mode=rows` ; le controleur repond par reponse() : les lignes
 * suivantes deja rendues, et de quoi savoir s'il en reste.
 *
 * Un seul contrat, parce que quatre ecrans l'avaient chacun reinvente, avec
 * quatre formes de reponse et quatre bugs differents.
 *
 * Le controleur doit repondre AVANT de calculer ce que seule la page complete
 * affiche (statistiques, listes de filtres) : sinon chaque tranche refait tout
 * l'ecran pour n'en renvoyer que les lignes.
 *
 * Le tri DOIT finir par une colonne unique (l'id). Sur une egalite de tri,
 * MySQL rend les lignes dans un ordre libre a chaque LIMIT/OFFSET : mesure sur
 * 1 000 lignes triees par statut, 20 tranches de 25 n'en montraient que 230
 * distinctes. Un tri sans departage unique n'est pas une liste infinie valable.
 * Le script ecarte aussi une ligne dont la cle (data-li-cle) est deja affichee.
 *
 * La pagination est par decalage. Des lignes retirees de l'ecran sans
 * rechargement (restauration, action groupee) font reculer toute la suite :
 * le client demande donc la tranche a partir de ce qu'il affiche
 * (`affiches` / `par_page`, ListeInfinie.pageAPrendre dans liste-infinie.js),
 * jamais « la page d'apres ». Dette restante : une ligne qui sort du filtre
 * du fait d'un AUTRE poste, sans que cet ecran le sache, decale encore la
 * suite d'un cran. Seule une pagination par curseur supprimerait ce trou.
 */
final class ListeInfinie
{
    public const MODE = 'rows';

    public static function demandee(Request $request): bool
    {
        return $request->input('mode') === self::MODE && ($request->ajax() || $request->wantsJson());
    }

    /**
     * @param  callable(mixed): string  $ligne  rend une ligne du paginateur
     * @param  array<string, mixed>  $extra
     */
    public static function reponse(Paginator $paginateur, callable $ligne, array $extra = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'rows_html' => collect($paginateur->items())->map($ligne)->implode(''),
            'pagination' => self::pagination($paginateur),
        ] + $extra);
    }

    /**
     * @return array{current_page: int, next_page: ?int, has_more: bool, total: ?int, affiches: int, par_page: int}
     */
    public static function pagination(Paginator $paginateur): array
    {
        $total = method_exists($paginateur, 'total') ? (int) $paginateur->total() : null;

        return [
            'current_page' => $paginateur->currentPage(),
            'next_page' => $paginateur->hasMorePages() ? $paginateur->currentPage() + 1 : null,
            'has_more' => $paginateur->hasMorePages(),
            'total' => $total,
            // Combien de lignes l'ecran porte apres cette tranche. Le compteur ne
            // compte pas les <tr> du DOM : une ligne peut en rendre deux (detail).
            'affiches' => ($paginateur->currentPage() - 1) * $paginateur->perPage() + count($paginateur->items()),
            // Le client en deduit la tranche a demander apres un retrait sur place.
            'par_page' => $paginateur->perPage(),
        ];
    }
}
