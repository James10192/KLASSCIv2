<?php

namespace App\Services;

use App\Models\ESBTPPaiement;
use App\Models\ESBTPDepense;
use App\Models\ESBTPSalaire;
use App\Models\ESBTPFraisScolarite;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\ESBTPCategorieDepense;
use App\Models\ESBTPKPI;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\ESBTPTransactionFinanciere;
use Illuminate\Support\Facades\Auth;

class ComptabiliteService
{
    // Cache tags pour l'invalidation intelligente
    private const CACHE_TAG_KPI = 'comptabilite_kpi';
    private const CACHE_TAG_STATS = 'comptabilite_stats';
    private const CACHE_TAG_DASHBOARD = 'comptabilite_dashboard';

    // Durées de cache en minutes
    private const CACHE_TTL_KPI = 15; // 15 minutes
    private const CACHE_TTL_STATS = 30; // 30 minutes
    private const CACHE_TTL_HEAVY = 60; // 1 heure pour calculs lourds

    /**
     * Calcule les KPIs financiers avancés avec cache intelligent
     */
    public function calculerKPIsAvances($anneeId = null)
    {
        $annee = $anneeId ?
            ESBTPAnneeUniversitaire::find($anneeId) :
            ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$annee) {
            return $this->getDefaultKPIs();
        }

        $cacheKey = "kpis_avances_{$annee->id}_" . Carbon::now()->format('Y-m-d-H');

        return Cache::store('comptabilite_kpis')->remember($cacheKey, self::CACHE_TTL_KPI, function () use ($annee) {
            Log::info("Calcul KPIs avancés pour l'année {$annee->id}");

            return [
                'recettes' => $this->calculerStatsRecettes($annee),
                'depenses' => $this->calculerStatsDepenses($annee),
                'paiements' => $this->calculerStatsPaiements($annee),
                'performance' => $this->calculerIndicateursPerformance($annee),
                'previsions' => $this->calculerPrevisions($annee),
                'alertes' => $this->detecterAlertes($annee),
                'cache_generated_at' => now()->toISOString()
            ];
        });
    }

    /**
     * Méthode rapide pour récupérer les KPIs du dashboard avec cache optimisé
     */
    public function getKPIsDashboard($anneeId = null)
    {
        $annee = $anneeId ?
            ESBTPAnneeUniversitaire::find($anneeId) :
            ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$annee) {
            return $this->getDefaultKPIs();
        }

        $cacheKey = "dashboard_kpis_{$annee->id}";

        return Cache::store('dashboard_queries')->remember($cacheKey, self::CACHE_TTL_KPI, function () use ($annee) {
            $recettes = $this->calculerStatsRecettes($annee);
            $depenses = $this->calculerStatsDepenses($annee);
            $performance = $this->calculerIndicateursPerformance($annee);

            return [
                'total_recettes' => $recettes['total'],
                'total_depenses' => $depenses['total'],
                'resultat_net' => $performance['resultat_net'],
                'taux_recouvrement' => $recettes['taux_recouvrement'],
                'marge_nette' => $performance['marge_nette'],
                'objectif_atteint' => $recettes['objectif_atteint'],
                'last_updated' => now()->toISOString()
            ];
        });
    }

    /**
     * Calcule les statistiques des recettes avec cache
     */
    private function calculerStatsRecettes($annee)
    {
        $cacheKey = "stats_recettes_{$annee->id}_" . Carbon::now()->format('Y-m-d');

        return Cache::store('comptabilite_kpis')->remember($cacheKey, self::CACHE_TTL_STATS, function () use ($annee) {
            // Optimisation avec eager loading - Correction status/statut
            $totalPaiements = ESBTPPaiement::where('annee_universitaire_id', $annee->id)
                ->where('status', 'validé')
                ->sum('montant');

            $paiementsMensuels = ESBTPPaiement::where('annee_universitaire_id', $annee->id)
                ->where('status', 'validé')
                ->whereMonth('date_paiement', Carbon::now()->month)
                ->whereYear('date_paiement', Carbon::now()->year)
                ->sum('montant');

            $totalPrevisionnel = ESBTPFraisScolarite::where('annee_universitaire_id', $annee->id)
                ->where('est_actif', true)
                ->sum('montant_total');

            $tauxRecouvrement = $totalPrevisionnel > 0 ?
                round(($totalPaiements / $totalPrevisionnel) * 100, 2) : 0;

            return [
                'total' => $totalPaiements,
                'mensuel' => $paiementsMensuels,
                'previsionnel' => $totalPrevisionnel,
                'taux_recouvrement' => $tauxRecouvrement,
                'objectif_atteint' => $tauxRecouvrement >= 85
            ];
        });
    }

    /**
     * Calcule les statistiques des dépenses
     */
    private function calculerStatsDepenses($annee)
    {
        $cacheKey = "stats_depenses_{$annee->id}_" . Carbon::now()->format('Y-m-d');

        return Cache::store('comptabilite_kpis')->remember($cacheKey, self::CACHE_TTL_STATS, function () use ($annee) {
            $dateDebut = Carbon::parse($annee->date_debut);
            $dateFin = Carbon::parse($annee->date_fin);

            // Optimisation: index composites sur date_depense + statut
            $totalDepenses = ESBTPDepense::whereBetween('date_depense', [$dateDebut, $dateFin])
                ->whereIn('statut', ['validée', 'approuve'])
                ->sum('montant');

            $depensesMensuelles = ESBTPDepense::whereMonth('date_depense', Carbon::now()->month)
                ->whereYear('date_depense', Carbon::now()->year)
                ->whereIn('statut', ['validée', 'approuve'])
                ->sum('montant');

            // Dépenses par catégorie avec eager loading
            $depensesParCategorie = ESBTPDepense::with('categorie')
                ->whereBetween('date_depense', [$dateDebut, $dateFin])
                ->whereIn('statut', ['validée', 'approuve'])
                ->get()
                ->groupBy('categorie.nom')
                ->map(function ($group) {
                    return $group->sum('montant');
                });

            return [
                'total' => $totalDepenses,
                'mensuel' => $depensesMensuelles,
                'par_categorie' => $depensesParCategorie,
                'budget_restant' => $this->calculerBudgetRestant($annee, $totalDepenses)
            ];
        });
    }

    /**
     * Calcule les statistiques des paiements avec cache optimisé
     */
    private function calculerStatsPaiements($annee)
    {
        $cacheKey = "stats_paiements_{$annee->id}_" . Carbon::now()->format('Y-m-d');

        return Cache::store('heavy_calculations')->remember($cacheKey, self::CACHE_TTL_HEAVY, function () use ($annee) {
            $totalInscriptions = ESBTPInscription::where('annee_universitaire_id', $annee->id)->count();

            // Optimisation avec requête unique pour éviter les N+1 - Correction jointure et colonnes
            $paymentStats = DB::table('esbtp_inscriptions')
                ->select([
                    'esbtp_etudiants.id as etudiant_id',
                    DB::raw('COALESCE(SUM(esbtp_paiements.montant), 0) as total_paye'),
                    DB::raw('(esbtp_inscriptions.montant_scolarite + esbtp_inscriptions.frais_inscription) as montant_requis')
                ])
                ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
                ->leftJoin('esbtp_paiements', function($join) {
                    $join->on('esbtp_inscriptions.id', '=', 'esbtp_paiements.inscription_id')
                         ->where('esbtp_paiements.status', '=', 'validé');
                })
                ->where('esbtp_inscriptions.annee_universitaire_id', $annee->id)
                ->groupBy(['esbtp_etudiants.id', 'esbtp_inscriptions.montant_scolarite', 'esbtp_inscriptions.frais_inscription'])
                ->get();

            $etudiantsPayeComplet = 0;
            $etudiantsPayePartiel = 0;

            foreach ($paymentStats as $stat) {
                if ($stat->total_paye >= $stat->montant_requis) {
                    $etudiantsPayeComplet++;
                } elseif ($stat->total_paye > 0) {
                    $etudiantsPayePartiel++;
                }
            }

            $etudiantsImpaye = $totalInscriptions - $etudiantsPayeComplet - $etudiantsPayePartiel;

            return [
                'total' => $totalInscriptions,
                'complets' => $etudiantsPayeComplet,
                'partiels' => $etudiantsPayePartiel,
                'impayés' => $etudiantsImpaye,
                'taux_recouvrement' => $totalInscriptions > 0 ?
                    round(($etudiantsPayeComplet / $totalInscriptions) * 100, 2) : 0
            ];
        });
    }

    /**
     * Calcule les indicateurs de performance
     */
    private function calculerIndicateursPerformance($annee)
    {
        $recettes = $this->calculerStatsRecettes($annee);
        $depenses = $this->calculerStatsDepenses($annee);

        $resultatNet = $recettes['total'] - $depenses['total'];
        $margeNette = $recettes['total'] > 0 ?
            round(($resultatNet / $recettes['total']) * 100, 2) : 0;

        return [
            'resultat_net' => $resultatNet,
            'marge_nette' => $margeNette,
            'rentabilite' => $resultatNet > 0 ? 'positive' : 'negative',
            'croissance_mensuelle' => $this->calculerCroissanceMensuelle($annee)
        ];
    }

    /**
     * Génère les prévisions financières avec cache
     */
    public function calculerPrevisions($annee, $nombreMois = 3)
    {
        $cacheKey = "previsions_{$annee->id}_{$nombreMois}_" . Carbon::now()->format('Y-m-d');

        return Cache::store('comptabilite_reports')->remember($cacheKey, self::CACHE_TTL_HEAVY, function () use ($annee, $nombreMois) {
            // Moyenne des 6 derniers mois avec optimisation SQL
            $moyenneRecettes = ESBTPPaiement::where('annee_universitaire_id', $annee->id)
                ->where('date_paiement', '>=', Carbon::now()->subMonths(6))
                ->where('status', 'validé')
                ->selectRaw('AVG(montant) as moyenne')
                ->value('moyenne') ?? 0;

            $moyenneDepenses = ESBTPDepense::where('date_depense', '>=', Carbon::now()->subMonths(6))
                ->whereIn('statut', ['validée', 'approuve'])
                ->selectRaw('AVG(montant) as moyenne')
                ->value('moyenne') ?? 0;

            $previsions = [];
            for ($i = 1; $i <= $nombreMois; $i++) {
                $moisFutur = Carbon::now()->addMonths($i);
                $previsions[$moisFutur->format('Y-m')] = [
                    'recettes_prevues' => $moyenneRecettes * 1.05, // Légère croissance
                    'depenses_prevues' => $moyenneDepenses * 1.02, // Légère inflation
                    'resultat_prevu' => ($moyenneRecettes * 1.05) - ($moyenneDepenses * 1.02)
                ];
            }

            return $previsions;
        });
    }

    /**
     * Détecte les alertes financières
     */
    private function detecterAlertes($annee)
    {
        $alertes = [];
        $recettes = $this->calculerStatsRecettes($annee);
        $paiements = $this->calculerStatsPaiements($annee);

        // Alerte taux de recouvrement faible
        if ($recettes['taux_recouvrement'] < 70) {
            $alertes[] = [
                'type' => 'warning',
                'message' => 'Taux de recouvrement faible: ' . $recettes['taux_recouvrement'] . '%',
                'action' => 'Intensifier les relances'
            ];
        }

        // Alerte grand nombre d'impayés
        if ($paiements['impayés'] > ($paiements['total'] * 0.3)) {
            $alertes[] = [
                'type' => 'danger',
                'message' => $paiements['impayés'] . ' étudiants n\'ont rien payé',
                'action' => 'Campagne de relance urgente'
            ];
        }

        return $alertes;
    }

    /**
     * Sauvegarde les KPIs calculés
     */
    public function sauvegarderKPIs($kpis, $periode = 'jour')
    {
        foreach ($kpis as $nom => $donnees) {
            if (is_array($donnees)) {
                foreach ($donnees as $sousNom => $valeur) {
                    if (is_numeric($valeur)) {
                        ESBTPKPI::updateOrCreate(
                            [
                                'nom' => $nom . '.' . $sousNom,
                                'periode' => $periode,
                                'date_calcul' => Carbon::now()->format('Y-m-d')
                            ],
                            [
                                'valeur' => $valeur,
                                'type' => $this->determinerTypeKPI($nom, $sousNom),
                                'metadata' => json_encode(['source' => 'auto_calculation'])
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * Génère automatiquement les factures depuis les inscriptions
     */
    public function genererFacturesAutomatiques($anneeId = null)
    {
        // Cette méthode sera implémentée pour la facturation automatique
        // selon les configurations de frais de scolarité
        return ['status' => 'success', 'factures_generees' => 0];
    }

    /**
     * Méthodes privées utilitaires
     */
    private function getDefaultKPIs()
    {
        return [
            'recettes' => ['total' => 0, 'mensuel' => 0, 'taux_recouvrement' => 0],
            'depenses' => ['total' => 0, 'mensuel' => 0],
            'paiements' => ['total' => 0, 'complets' => 0, 'impayés' => 0],
            'performance' => ['resultat_net' => 0, 'marge_nette' => 0],
            'alertes' => []
        ];
    }

    private function calculerBudgetRestant($annee, $totalDepenses)
    {
        // Logique pour calculer le budget restant
        // Peut être configuré via la table de configuration
        return 0;
    }

    private function calculerCroissanceMensuelle($annee)
    {
        // Logique pour calculer la croissance mensuelle
        return 0;
    }

    private function determinerTypeKPI($nom, $sousNom)
    {
        if (strpos($nom, 'recette') !== false) return 'recette';
        if (strpos($nom, 'depense') !== false) return 'depense';
        if (strpos($nom, 'performance') !== false) return 'performance';
        return 'ratio';
    }

    /**
     * Invalide intelligemment le cache lors de modifications
     */
    public function invalidateCache($type = 'all', $anneeId = null)
    {
        try {
            switch ($type) {
                case 'kpis':
                    $this->invalidateKPICache($anneeId);
                    break;
                case 'dashboard':
                    $this->invalidateDashboardCache($anneeId);
                    break;
                case 'reports':
                    $this->invalidateReportsCache($anneeId);
                    break;
                case 'all':
                default:
                    $this->invalidateAllCache($anneeId);
                    break;
            }

            Log::info("Cache invalidé", ['type' => $type, 'annee_id' => $anneeId]);
        } catch (\Exception $e) {
            Log::error("Erreur invalidation cache", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Invalide le cache des KPIs
     */
    private function invalidateKPICache($anneeId = null)
    {
        $stores = ['comptabilite_kpis', 'dashboard_queries'];

        foreach ($stores as $store) {
            if ($anneeId) {
                // Invalider spécifiquement pour une année
                $patterns = [
                    "kpis_avances_{$anneeId}_*",
                    "dashboard_kpis_{$anneeId}",
                    "stats_recettes_{$anneeId}_*",
                    "stats_depenses_{$anneeId}_*",
                    "stats_paiements_{$anneeId}_*"
                ];

                foreach ($patterns as $pattern) {
                    $this->forgetCachePattern($store, $pattern);
                }
            } else {
                // Vider complètement le store
                Cache::store($store)->flush();
            }
        }
    }

    /**
     * Invalide le cache du dashboard
     */
    private function invalidateDashboardCache($anneeId = null)
    {
        if ($anneeId) {
            Cache::store('dashboard_queries')->forget("dashboard_kpis_{$anneeId}");
        } else {
            Cache::store('dashboard_queries')->flush();
        }
    }

    /**
     * Invalide le cache des rapports
     */
    private function invalidateReportsCache($anneeId = null)
    {
        if ($anneeId) {
            $this->forgetCachePattern('comptabilite_reports', "previsions_{$anneeId}_*");
        } else {
            Cache::store('comptabilite_reports')->flush();
        }
    }

    /**
     * Invalide tout le cache comptabilité
     */
    private function invalidateAllCache($anneeId = null)
    {
        $stores = ['comptabilite_kpis', 'dashboard_queries', 'comptabilite_reports', 'heavy_calculations'];

        foreach ($stores as $store) {
            if ($anneeId) {
                // Patterns spécifiques à l'année
                $patterns = [
                    "kpis_avances_{$anneeId}_*",
                    "dashboard_kpis_{$anneeId}",
                    "stats_*_{$anneeId}_*",
                    "previsions_{$anneeId}_*"
                ];

                foreach ($patterns as $pattern) {
                    $this->forgetCachePattern($store, $pattern);
                }
            } else {
                try {
                    Cache::store($store)->flush();
                } catch (\Exception $e) {
                    Log::warning("Impossible de vider le store {$store}", ['error' => $e->getMessage()]);
                }
            }
        }
    }

    /**
     * Oublie les clés de cache selon un pattern
     */
    private function forgetCachePattern($store, $pattern)
    {
        try {
            // Pour Redis, on peut utiliser les patterns
            if (Cache::store($store)->getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::store($store)->getRedis();
                $prefix = Cache::store($store)->getPrefix();
                $keys = $redis->keys($prefix . $pattern);

                foreach ($keys as $key) {
                    $cacheKey = str_replace($prefix, '', $key);
                    Cache::store($store)->forget($cacheKey);
                }
            }
        } catch (\Exception $e) {
            Log::warning("Erreur lors de la suppression pattern {$pattern}", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Surveille les performances des requêtes
     */
    public function monitorPerformance($operation, callable $callback)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            $result = $callback();

            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $executionTime = round(($endTime - $startTime) * 1000, 2); // en ms
            $memoryUsage = round(($endMemory - $startMemory) / 1024 / 1024, 2); // en MB

            // Logger les performances si c'est lent
            if ($executionTime > 1000) { // > 1 seconde
                Log::warning("Opération lente détectée", [
                    'operation' => $operation,
                    'execution_time_ms' => $executionTime,
                    'memory_usage_mb' => $memoryUsage
                ]);
            } else {
                Log::debug("Performance monitoring", [
                    'operation' => $operation,
                    'execution_time_ms' => $executionTime,
                    'memory_usage_mb' => $memoryUsage
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Erreur dans l'opération {$operation}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function createPaiementFromInscription(ESBTPInscription $inscription, float $montant, string $methodePaiement = 'espece', string $reference = null)
    {
        $paiement = ESBTPPaiement::create([
            'inscription_id' => $inscription->id,
            'etudiant_id' => $inscription->etudiant_id,
            'annee_universitaire_id' => $inscription->annee_universitaire_id,
            'type_paiement' => 'inscription',
            'montant' => $montant,
            'date_paiement' => now(),
            'mode_paiement' => $methodePaiement,
            'reference_paiement' => $reference ?? 'INSCRIPTION-' . $inscription->id,
            'motif' => 'Frais d\'inscription',
            'status' => 'en_attente', // Tous les paiements doivent être validés manuellement
            'created_by' => Auth::id(),
        ]);

        // Mettre à jour le statut de l'inscription si le paiement couvre les frais
        $inscription->updateTotalPaye();

        // Enregistrer la transaction financière
        ESBTPTransactionFinanciere::create([
            'type' => 'credit',
            'montant' => $montant,
            'description' => 'Paiement frais inscription pour ' . $inscription->etudiant->nom_complet,
            'reference_id' => $paiement->id,
            'reference_type' => ESBTPPaiement::class,
            'date_transaction' => now(),
        ]);

        // Invalider les caches pertinents
        $this->invalidateCache('kpi', $inscription->annee_universitaire_id);
        $this->invalidateCache('dashboard', $inscription->annee_universitaire_id);

        return $paiement;
    }

    public function validerPaiementInscription(ESBTPInscription $inscription, float $montant, string $methodePaiement = 'espece', string $reference = null)
    {
        $paiement = $this->createPaiementFromInscription($inscription, $montant, $methodePaiement, $reference);

        // Activer la comptabilité pour l'étudiant
        $inscription->etudiant->update(['comptabilite_active' => true]);

        return $paiement;
    }
}
