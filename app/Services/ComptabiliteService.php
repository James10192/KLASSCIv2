<?php

namespace App\Services;

use App\Models\ESBTPPaiement;
use App\Models\ESBTPSalaire;
use App\Models\ESBTPFraisScolarite;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use Carbon\Carbon;
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

            return [
                'total_recettes' => $recettes['total'],
                'taux_recouvrement' => $recettes['taux_recouvrement'],
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
            $totalPaiements = ESBTPPaiement::netCashSum(
                ESBTPPaiement::where('annee_universitaire_id', $annee->id)->where('status', 'validé')
            );

            $paiementsMensuels = ESBTPPaiement::netCashSum(
                ESBTPPaiement::where('annee_universitaire_id', $annee->id)
                    ->where('status', 'validé')
                    ->whereMonth('date_paiement', Carbon::now()->month)
                    ->whereYear('date_paiement', Carbon::now()->year)
            );

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
            'paiements' => ['total' => 0, 'complets' => 0, 'impayés' => 0],
            'alertes' => []
        ];
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
