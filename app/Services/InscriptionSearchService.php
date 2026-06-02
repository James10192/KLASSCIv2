<?php

namespace App\Services;

use App\Models\ESBTPInscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class InscriptionSearchService
{
    public function __construct(
        private FuzzyNameMatcher $matcher,
    ) {}

    /**
     * Recherche fuzzy sur les inscriptions avec scoring et pagination.
     *
     * @param Builder $baseQuery Query déjà filtrée (filiere, niveau, annee, status)
     * @param string $search Terme de recherche
     * @param int $perPage Nombre par page
     * @param string $url URL courante pour les liens de pagination
     * @param array $queryParams Query string à appendre aux liens
     */
    public function search(
        Builder $baseQuery,
        string $search,
        int $perPage = 15,
        string $url = '',
        array $queryParams = [],
    ): LengthAwarePaginator {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $candidates = $this->fetchCandidates($baseQuery, $search);

        $scored = $this->matcher->match(
            $search,
            $candidates,
            function ($inscription) {
                $etudiant = $inscription->etudiant;

                return [
                    'matricule' => $etudiant?->matricule,
                    'nom' => $etudiant?->nom,
                    'prenoms' => $etudiant?->prenoms,
                    'full_name' => $etudiant
                        ? trim($etudiant->nom . ' ' . $etudiant->prenoms)
                        : null,
                    'classe' => $inscription->classe?->name,
                    'numero_inscription' => $inscription->numero_inscription,
                    'numero_recu' => $inscription->numero_recu,
                ];
            },
            [
                'threshold' => 35,
                'limit' => 150,
                'boosts' => [
                    'matricule' => 18,
                    'numero_inscription' => 12,
                    'numero_recu' => 10,
                    'full_name' => 6,
                ],
            ],
        );

        $total = $scored->count();
        $items = $scored->forPage($currentPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => $url, 'query' => $queryParams],
        );
        $paginator->appends($queryParams);

        return $paginator;
    }

    /**
     * Récupère les candidats via requête SQL avec fallback.
     */
    private function fetchCandidates(Builder $baseQuery, string $search)
    {
        $candidatesQuery = clone $baseQuery;
        $searchTokens = collect(
            preg_split("/[\s,]+/u", $search, -1, PREG_SPLIT_NO_EMPTY),
        )->map(fn($token) => trim($token))->filter();

        $candidatesQuery->where(function ($q) use ($search, $searchTokens) {
            $likeSearch = '%' . self::escapeLike($search) . '%';

            $q->whereHas('etudiant', function ($eq) use ($likeSearch, $searchTokens) {
                $eq->where('matricule', 'like', $likeSearch)
                    ->orWhere('nom', 'like', $likeSearch)
                    ->orWhere('prenoms', 'like', $likeSearch)
                    ->orWhereRaw("CONCAT_WS(' ', prenoms, nom) LIKE ?", [$likeSearch])
                    ->orWhereRaw("CONCAT_WS(' ', nom, prenoms) LIKE ?", [$likeSearch]);

                if ($searchTokens->isNotEmpty()) {
                    $eq->orWhere(function ($sub) use ($searchTokens) {
                        foreach ($searchTokens as $token) {
                            $likeToken = '%' . self::escapeLike($token) . '%';
                            $sub->orWhere('nom', 'like', $likeToken)
                                ->orWhere('prenoms', 'like', $likeToken)
                                ->orWhere('matricule', 'like', $likeToken)
                                ->orWhere('telephone', 'like', $likeToken)
                                ->orWhere('email_personnel', 'like', $likeToken);
                        }
                    });
                }
            })
                ->orWhere('numero_recu', 'like', $likeSearch)
                ->orWhereHas('classe', function ($cq) use ($likeSearch, $searchTokens) {
                    $cq->where('name', 'like', $likeSearch);
                    if ($searchTokens->isNotEmpty()) {
                        $cq->orWhere(function ($sub) use ($searchTokens) {
                            foreach ($searchTokens as $token) {
                                $sub->orWhere('name', 'like', '%' . self::escapeLike($token) . '%');
                            }
                        });
                    }
                });
        });

        try {
            return $candidatesQuery->limit(200)->get();
        } catch (QueryException $e) {
            Log::warning('Inscription search fallback triggered', [
                'message' => $e->getMessage(),
            ]);

            $fallbackQuery = clone $baseQuery;
            $fallbackQuery->where(function ($q) use ($search) {
                $likeSearch = '%' . self::escapeLike($search) . '%';
                $q->whereHas('etudiant', function ($eq) use ($likeSearch) {
                    $eq->where('matricule', 'like', $likeSearch)
                        ->orWhere('nom', 'like', $likeSearch)
                        ->orWhere('prenoms', 'like', $likeSearch);
                })->orWhere('numero_recu', 'like', $likeSearch);
            });

            return $fallbackQuery->limit(200)->get();
        }
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
