<?php

namespace App\Http\Controllers;

use App\Support\Recherche\IndexDesPages;
use App\Support\Recherche\RechercheDesEntites;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Recherche globale : la palette Ctrl K / ⌘ K (JSON) et sa page de résultats.
 *
 * Les deux lisent les MÊMES sources, filtrées par les mêmes permissions :
 * IndexDesPages pour les écrans, RechercheDesEntites pour les fiches. Ce qui
 * n'apparaît pas dans la palette n'apparaît pas non plus sur la page.
 */
class SearchController extends Controller
{
    /** Pages proposées quand la palette s'ouvre vide. */
    private const NOMBRE_DE_SUGGESTIONS = 6;

    public function __construct(
        private readonly IndexDesPages $pages,
        private readonly RechercheDesEntites $entites,
    ) {
    }

    /** GET /search?q=… — la palette. */
    public function globalSearch(Request $request): JsonResponse
    {
        $saisie = trim((string) $request->query('q', ''));
        $limite = max(1, min(RechercheDesEntites::LIMITE_PALETTE, (int) $request->query('limit', RechercheDesEntites::LIMITE_PALETTE)));
        $utilisateur = $request->user();

        if (mb_strlen($saisie) < 2) {
            return response()->json([
                'success' => true,
                'query' => $saisie,
                'results' => [],
                'suggestions' => array_map([$this, 'pageEnResultat'], $this->pages->suggestions($utilisateur, self::NOMBRE_DE_SUGGESTIONS)),
            ]);
        }

        $pages = array_map([$this, 'pageEnResultat'], $this->pages->chercher($saisie, $utilisateur, $limite));
        [$fiches, $partiel] = $this->fiches($saisie, $utilisateur, $limite);

        return response()->json([
            'success' => true,
            'query' => $saisie,
            'results' => array_merge($pages, $fiches),
            'partial' => $partiel,
        ]);
    }

    /** GET /search/results?q=…&type=… — la page complète. */
    public function searchResults(Request $request)
    {
        $saisie = trim((string) $request->query('q', ''));
        $utilisateur = $request->user();
        $groupesOuverts = $this->entites->groupesOuverts($utilisateur);
        $type = (string) $request->query('type', 'all');
        $types = array_merge(['all', 'pages'], $groupesOuverts);
        $type = in_array($type, $types, true) ? $type : 'all';

        $pages = [];
        $fiches = [];
        $partiel = false;

        if (mb_strlen($saisie) >= 2) {
            if ($type === 'all' || $type === 'pages') {
                $pages = array_map([$this, 'pageEnResultat'], $this->pages->chercher($saisie, $utilisateur, RechercheDesEntites::LIMITE_PAGE));
            }
            if ($type !== 'pages') {
                [$fiches, $partiel] = $this->fiches($saisie, $utilisateur, RechercheDesEntites::LIMITE_PAGE, $type === 'all' ? null : [$type]);
            }
        }

        $groupes = collect(array_merge($pages, $fiches))->groupBy('group');

        return view('search.results', [
            'query' => $saisie,
            'type' => $type,
            'groupes' => $groupes,
            'groupesOuverts' => $groupesOuverts,
            'partiel' => $partiel,
        ]);
    }

    /**
     * Les fiches, sans jamais laisser partir le message d'une exception : une
     * base qui refuse une requête ne doit pas priver l'utilisateur des pages,
     * ni lui montrer une trace SQL. Le rattrapage est journalisé.
     *
     * @return array{0: list<array>, 1: bool}
     */
    private function fiches(string $saisie, ?Authorizable $utilisateur, int $limite, ?array $seulement = null): array
    {
        try {
            return [$this->entites->chercher($saisie, $utilisateur, $limite, $seulement), false];
        } catch (\Throwable $e) {
            Log::error('Recherche globale : fiches indisponibles', [
                'user_id' => $utilisateur?->getAuthIdentifier(),
                'exception' => $e,
            ]);

            return [[], true];
        }
    }

    private function pageEnResultat(array $page): array
    {
        return [
            'group' => 'Pages',
            'type' => 'page',
            'id' => $page['route'],
            'title' => $page['titre'],
            'subtitle' => $page['groupe'],
            'url' => $page['url'],
            'icon' => $page['icone'],
        ];
    }
}
