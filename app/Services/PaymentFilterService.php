<?php

namespace App\Services;

use App\Models\ESBTPPaiement;
use App\Models\ESBTPAnneeUniversitaire;
use App\Services\FuzzyNameMatcher;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class PaymentFilterService
{
    /**
     * Prépare les données de listing des paiements (liste, statistiques, timestamp).
     */
    public function preparePaiementListing(Request $request, FuzzyNameMatcher $matcher, array $baseLogContext, float $startMicrotime, string $logPrefix): array
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneeId = $anneeEnCours?->id;

        $baseQuery = ESBTPPaiement::with([
            'etudiant.user',
            'inscription.anneeUniversitaire',
            'inscription.filiere',
            'inscription.niveauEtude',
            'validatedBy',
            'creator:id,name',
            'fraisCategory',
            'categorie',
        ])->orderByDesc('created_at');

        // Lot 13 — Ownership filter : si user n'a PAS `paiements.view` mais a `paiements.view_own`,
        // restreindre aux paiements qu'il a encaissés (created_by = user.id).
        $authUser = $request->user();
        if ($authUser
            && ! $authUser->can('paiements.view')
            && $authUser->can('paiements.view_own')
        ) {
            $baseQuery->ownedBy($authUser);
        }

        if ($status) {
            $baseQuery->where('status', $status);
        }

        if ($dateDebut) {
            $baseQuery->whereDate('date_paiement', '>=', $dateDebut);
        }

        if ($dateFin) {
            $baseQuery->whereDate('date_paiement', '<=', $dateFin);
        }

        if ($anneeId) {
            $baseQuery->whereHas('inscription', function ($q) use ($anneeId) {
                $q->where('annee_universitaire_id', $anneeId);
            });
        } else {
            $baseQuery->anneeEnCours();
        }

        $perPage = 15;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $escapeLike = static function (string $value): string {
            return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
        };

        $searchTokens = collect(preg_split('/[\s,]+/u', $search, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($token) => trim($token))
            ->filter();

        Log::info("{$logPrefix} processing", array_merge($baseLogContext, [
            'has_search' => $search !== '',
            'filters' => [
                'status' => $status,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
            ],
        ]));

        $applyQuickSearch = function ($builder) use ($search, $searchTokens, $escapeLike) {
            $escapedSearch = $escapeLike($search);
            $likeSearch = "%{$escapedSearch}%";

            $builder->where(function ($q) use ($likeSearch, $searchTokens, $escapeLike) {
                $q->whereHas('etudiant', function ($etudiantQuery) use ($likeSearch, $searchTokens, $escapeLike) {
                    $etudiantQuery->where('matricule', 'like', $likeSearch)
                        ->orWhere('nom', 'like', $likeSearch)
                        ->orWhere('prenoms', 'like', $likeSearch)
                        ->orWhereHas('user', function ($userQuery) use ($likeSearch) {
                            $userQuery->where('name', 'like', $likeSearch)
                                ->orWhere('email', 'like', $likeSearch);
                        })
                        ->orWhereRaw("CONCAT_WS(' ', prenoms, nom) LIKE ?", [$likeSearch])
                        ->orWhereRaw("CONCAT_WS(' ', nom, prenoms) LIKE ?", [$likeSearch]);

                    if ($searchTokens->isNotEmpty()) {
                        $etudiantQuery->orWhere(function ($subQuery) use ($searchTokens, $escapeLike) {
                            foreach ($searchTokens as $token) {
                                $escapedToken = $escapeLike($token);
                                $likeToken = "%{$escapedToken}%";
                                $subQuery->orWhere('nom', 'like', $likeToken)
                                         ->orWhere('prenoms', 'like', $likeToken)
                                         ->orWhere('matricule', 'like', $likeToken);
                            }
                        });
                    }
                })
                ->orWhere('numero_recu', 'like', $likeSearch)
                ->orWhere('reference_paiement', 'like', $likeSearch);

                if ($searchTokens->isNotEmpty()) {
                    $q->orWhere(function ($subQuery) use ($searchTokens, $escapeLike) {
                        foreach ($searchTokens as $token) {
                            $escapedToken = $escapeLike($token);
                            $likeToken = "%{$escapedToken}%";
                            $subQuery->orWhere('numero_recu', 'like', $likeToken)
                                     ->orWhere('reference_paiement', 'like', $likeToken);
                        }
                    });
                }
            });
        };

        $applyFallbackQuickSearch = function ($builder) use ($search, $escapeLike) {
            $escapedSearch = $escapeLike($search);
            $likeSearch = "%{$escapedSearch}%";

            $builder->where(function ($q) use ($likeSearch) {
                $q->whereHas('etudiant', function ($etudiantQuery) use ($likeSearch) {
                    $etudiantQuery->where('matricule', 'like', $likeSearch)
                        ->orWhere('nom', 'like', $likeSearch)
                        ->orWhere('prenoms', 'like', $likeSearch);
                })
                ->orWhere('numero_recu', 'like', $likeSearch)
                ->orWhere('reference_paiement', 'like', $likeSearch);
            });
        };

        $scored = collect();

        if ($search !== '') {
            $candidatesQuery = clone $baseQuery;
            $applyQuickSearch($candidatesQuery);

            try {
                $candidates = $candidatesQuery->limit(250)->get();
            } catch (QueryException $exception) {
                Log::warning("{$logPrefix} fallback search triggered", array_merge($baseLogContext, [
                    'message' => $exception->getMessage(),
                ]));

                $fallbackQuery = clone $baseQuery;
                $applyFallbackQuickSearch($fallbackQuery);

                $candidates = $fallbackQuery->limit(250)->get();
            }

            $scored = $matcher->match($search, $candidates, function ($paiement) {
                $etudiant = $paiement->etudiant;

                return [
                    'matricule' => $etudiant?->matricule,
                    'nom' => $etudiant?->nom,
                    'prenoms' => $etudiant?->prenoms,
                    'full_name' => $etudiant ? trim($etudiant->prenoms . ' ' . $etudiant->nom) : null,
                    'numero_recu' => $paiement->numero_recu,
                    'reference' => $paiement->reference_paiement,
                ];
            }, [
                'threshold' => 35,
                'limit' => 200,
                'boosts' => [
                    'numero_recu' => 20,
                    'reference' => 15,
                    'matricule' => 10,
                ],
            ]);

            $total = $scored->count();
            $items = $scored->forPage($currentPage, $perPage)->values();

            $paiements = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        } else {
            $paiements = (clone $baseQuery)->paginate($perPage, ['*'], 'page', $currentPage);
        }

        $paiements->appends($request->query());

        $statsQueryBase = clone $baseQuery;
        if ($search !== '') {
            try {
                $applyQuickSearch($statsQueryBase);
            } catch (QueryException $exception) {
                Log::warning("{$logPrefix} stats fallback triggered", array_merge($baseLogContext, [
                    'message' => $exception->getMessage(),
                ]));
                $applyFallbackQuickSearch($statsQueryBase);
            }
        }

        // ===================================================================
        // IMPORTANT: Calculer les stats UNIQUEMENT sur les paiements filtrés
        // ===================================================================

        // Compter les paiements filtrés
        $stats = [
            'total' => (clone $statsQueryBase)->count(),
            'valides' => (clone $statsQueryBase)->where('status', 'validé')->count(),
            'en_attente' => (clone $statsQueryBase)->where('status', 'en_attente')->count(),
            'rejetes' => (clone $statsQueryBase)->where('status', 'rejeté')->count(),
        ];

        // Calculer les montants sur les paiements filtrés
        $montantTotal = (clone $statsQueryBase)->sum('montant') ?? 0;
        $montantValide = (clone $statsQueryBase)->where('status', 'validé')->sum('montant') ?? 0;
        $montantEnAttente = (clone $statsQueryBase)->where('status', 'en_attente')->sum('montant') ?? 0;
        $montantRejete = (clone $statsQueryBase)->where('status', 'rejeté')->sum('montant') ?? 0;

        $stats['montant_total'] = $montantTotal;
        $stats['montant_valide'] = $montantValide;
        $stats['montant_en_attente'] = $montantEnAttente;
        $stats['montant_rejete'] = $montantRejete;

        // Calculer le taux de recouvrement sur les paiements filtrés
        $stats['recovery_rate'] = $montantTotal > 0
            ? round(($montantValide / $montantTotal) * 100, 1)
            : 0;

        // Calculer les stats par catégorie sur les paiements filtrés
        $statsParCategorie = $this->calculateFilteredCategoryStats(clone $statsQueryBase);
        $stats = array_merge($stats, $statsParCategorie);

        $lastUpdatedAt = null;

        if ($search !== '') {
            $lastUpdatedAt = $scored->map(function ($paiement) {
                return $paiement->updated_at ?? $paiement->created_at;
            })->filter()->max();
        } else {
            $latestUpdated = (clone $baseQuery)->max('updated_at');
            if ($latestUpdated) {
                $lastUpdatedAt = Carbon::parse($latestUpdated);
            } else {
                $latestCreated = (clone $baseQuery)->max('created_at');
                $lastUpdatedAt = $latestCreated ? Carbon::parse($latestCreated) : null;
            }
        }

        if ($lastUpdatedAt && ! $lastUpdatedAt instanceof Carbon) {
            $lastUpdatedAt = Carbon::parse($lastUpdatedAt);
        }

        return [
            'paiements' => $paiements,
            'stats' => $stats,
            'last_updated_at' => $lastUpdatedAt,
            'summary' => [
                'total' => $paiements->total(),
                'page' => $paiements->currentPage(),
                'per_page' => $paiements->perPage(),
            ],
        ];
    }

    /**
     * Calcule les statistiques par catégorie UNIQUEMENT sur les paiements filtrés
     */
    public function calculateFilteredCategoryStats($filteredQuery)
    {
        // Initialiser les stats
        $stats = [
            'academic_total' => 0,
            'academic_paid' => 0,
            'academic_pending' => 0,
            'service_total' => 0,
            'service_paid' => 0,
            'service_pending' => 0,
            'administrative_total' => 0,
            'administrative_paid' => 0,
            'administrative_pending' => 0,
        ];

        // Récupérer les catégories de frais
        $categories = \App\Models\ESBTPFraisCategory::all()->keyBy('id');

        // Calculer les montants par catégorie sur les paiements filtrés
        $paiementsParCategorie = (clone $filteredQuery)
            ->selectRaw('frais_category_id, status, SUM(montant) as total_montant')
            ->whereNotNull('frais_category_id')
            ->groupBy('frais_category_id', 'status')
            ->get();

        foreach ($paiementsParCategorie as $stat) {
            $category = $categories->get($stat->frais_category_id);
            if (!$category) continue;

            $montant = (float) $stat->total_montant;
            $type = strtolower($category->type); // academic, service, administrative

            // Ajouter au total de la catégorie
            if (isset($stats["{$type}_total"])) {
                $stats["{$type}_total"] += $montant;
            }

            // Répartir selon le statut
            if ($stat->status === 'validé' && isset($stats["{$type}_paid"])) {
                $stats["{$type}_paid"] += $montant;
            } elseif ($stat->status === 'en_attente' && isset($stats["{$type}_pending"])) {
                $stats["{$type}_pending"] += $montant;
            }
        }

        return $stats;
    }

    /**
     * Calcule les vraies statistiques basées sur les inscriptions et leurs frais attendus.
     */
    public function calculateCategoryStats($baseQuery = null)
    {
        // Obtenir l'année en cours pour les calculs
        $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Si aucune année courante, prendre la plus récente
        if (!$anneeEnCours) {
            $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->first();
        }

        if (!$anneeEnCours) {
            return $this->getEmptyStats();
        }

        // Récupérer toutes les inscriptions de l'année en cours (même filtrage que suivi-categories)
        $inscriptions = \App\Models\ESBTPInscription::with(['filiere', 'niveauEtude'])
            ->where('annee_universitaire_id', $anneeEnCours->id)
            ->whereIn('status', ['active', 'en_attente', 'validée'])
            ->get();

        $statsService = app(PaymentStatsService::class);

        $stats = [
            'academic_paid' => 0,
            'service_paid' => 0,
            'administrative_paid' => 0,
            'academic_pending' => 0,
            'service_pending' => 0,
            'administrative_pending' => 0,
            'academic_total' => 0,
            'service_total' => 0,
            'administrative_total' => 0,
        ];

        // Pour chaque inscription, calculer les montants attendus et payés
        foreach ($inscriptions as $inscription) {
            $fraisStats = $statsService->calculateFraisForInscription($inscription);

            foreach (['academic', 'service', 'administrative'] as $type) {
                $stats[$type . '_total'] += $fraisStats[$type]['expected'];
                $stats[$type . '_paid'] += $fraisStats[$type]['paid'];
                $stats[$type . '_pending'] += $fraisStats[$type]['expected'] - $fraisStats[$type]['paid'];
            }
        }

        // S'assurer que les pending ne sont jamais négatifs
        foreach (['academic', 'service', 'administrative'] as $type) {
            $stats[$type . '_pending'] = max(0, $stats[$type . '_pending']);
        }

        // Ajouter les reliquats aux montants en attente
        $reliquatsStats = $statsService->calculateReliquatsStats($inscriptions);
        foreach (['academic', 'service', 'administrative'] as $type) {
            $stats[$type . '_pending'] += $reliquatsStats[$type . '_pending'];
            $stats[$type . '_total'] += $reliquatsStats[$type . '_total'];
        }

        return $stats;
    }

    /**
     * Retourne des stats vides en cas de problème.
     */
    private function getEmptyStats()
    {
        return [
            'academic_paid' => 0,
            'service_paid' => 0,
            'administrative_paid' => 0,
            'academic_pending' => 0,
            'service_pending' => 0,
            'administrative_pending' => 0,
            'academic_total' => 0,
            'service_total' => 0,
            'administrative_total' => 0,
        ];
    }

    /**
     * Récupère TOUS les paiements filtrés (sans pagination) pour les exports
     */
    public function getAllFilteredPaiements(Request $request, FuzzyNameMatcher $matcher)
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneeId = $anneeEnCours?->id;

        // Construire la requête de base avec les mêmes filtres que preparePaiementListing
        $query = ESBTPPaiement::with([
            'etudiant.user',
            'inscription.classe',
            'inscription.anneeUniversitaire',
            'inscription.filiere',
            'inscription.niveauEtude',
            'validatedBy',
            'creator:id,name',
            'fraisCategory',
            'categorie',
        ])->orderByDesc('created_at');

        // Lot 13 — Ownership filter pour l'export aussi (cohérence avec preparePaiementListing)
        $authUser = $request->user();
        if ($authUser
            && ! $authUser->can('paiements.view')
            && $authUser->can('paiements.view_own')
        ) {
            $query->ownedBy($authUser);
        }

        // Appliquer les filtres
        if ($status) {
            $query->where('status', $status);
        }

        if ($dateDebut) {
            $query->whereDate('date_paiement', '>=', $dateDebut);
        }

        if ($dateFin) {
            $query->whereDate('date_paiement', '<=', $dateFin);
        }

        if ($anneeId) {
            $query->whereHas('inscription', function ($q) use ($anneeId) {
                $q->where('annee_universitaire_id', $anneeId);
            });
        } else {
            $query->anneeEnCours();
        }

        // Appliquer le filtre de recherche si présent
        if ($search !== '') {
            $escapeLike = static function (string $value): string {
                return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
            };

            $escapedSearch = $escapeLike($search);
            $likeSearch = "%{$escapedSearch}%";

            $query->where(function ($q) use ($likeSearch) {
                $q->whereHas('etudiant', function ($etudiantQuery) use ($likeSearch) {
                    $etudiantQuery->where('matricule', 'like', $likeSearch)
                        ->orWhere('nom', 'like', $likeSearch)
                        ->orWhere('prenoms', 'like', $likeSearch)
                        ->orWhereHas('user', function ($userQuery) use ($likeSearch) {
                            $userQuery->where('name', 'like', $likeSearch)
                                ->orWhere('email', 'like', $likeSearch);
                        })
                        ->orWhereRaw("CONCAT_WS(' ', prenoms, nom) LIKE ?", [$likeSearch])
                        ->orWhereRaw("CONCAT_WS(' ', nom, prenoms) LIKE ?", [$likeSearch]);
                })
                ->orWhere('numero_recu', 'like', $likeSearch)
                ->orWhere('reference_paiement', 'like', $likeSearch);
            });
        }

        // Récupérer TOUS les résultats (pas de pagination)
        return $query->get();
    }
}
