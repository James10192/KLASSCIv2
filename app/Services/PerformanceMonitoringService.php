<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PerformanceMonitoringService
{
    private const CACHE_STORE = 'dashboard_queries';
    private const METRICS_TTL = 60; // 1 heure

    /**
     * Surveille les performances d'une opération
     */
    public function monitor(string $operation, callable $callback, array $context = [])
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $queryCount = $this->getQueryCount();

        try {
            $result = $callback();

            $this->recordMetrics($operation, $startTime, $startMemory, $queryCount, $context);

            return $result;

        } catch (\Exception $e) {
            $this->recordError($operation, $e, $startTime, $startMemory, $context);
            throw $e;
        }
    }

    /**
     * Enregistre les métriques de performance
     */
    private function recordMetrics(string $operation, float $startTime, int $startMemory, int $startQueries, array $context = [])
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $endQueries = $this->getQueryCount();

        $metrics = [
            'operation' => $operation,
            'execution_time_ms' => round(($endTime - $startTime) * 1000, 2),
            'memory_usage_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2),
            'query_count' => $endQueries - $startQueries,
            'timestamp' => now()->toISOString(),
            'context' => $context
        ];

        // Alertes pour performances dégradées
        if ($metrics['execution_time_ms'] > 2000) {
            $this->alertSlowOperation($metrics);
        }

        if ($metrics['query_count'] > 10) {
            $this->alertHighQueryCount($metrics);
        }

        // Stocker les métriques
        $this->storeMetrics($metrics);

        // Logger selon le niveau
        $this->logMetrics($metrics);
    }

    /**
     * Enregistre une erreur de performance
     */
    private function recordError(string $operation, \Exception $e, float $startTime, int $startMemory, array $context = [])
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        Log::error("Erreur de performance dans l'opération {$operation}", [
            'error' => $e->getMessage(),
            'execution_time_ms' => round(($endTime - $startTime) * 1000, 2),
            'memory_usage_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2),
            'context' => $context,
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Alerte pour opération lente
     */
    private function alertSlowOperation(array $metrics)
    {
        Log::warning("⚠️  Opération lente détectée", [
            'operation' => $metrics['operation'],
            'execution_time_ms' => $metrics['execution_time_ms'],
            'seuil_ms' => 2000,
            'recommandation' => 'Vérifier le cache et optimiser les requêtes'
        ]);

        // Incrémenter compteur d'alertes
        $this->incrementAlertCounter('slow_operations');
    }

    /**
     * Alerte pour nombre élevé de requêtes
     */
    private function alertHighQueryCount(array $metrics)
    {
        Log::warning("🔍 Nombre élevé de requêtes détecté", [
            'operation' => $metrics['operation'],
            'query_count' => $metrics['query_count'],
            'seuil' => 10,
            'recommandation' => 'Implémenter eager loading ou cache'
        ]);

        $this->incrementAlertCounter('high_query_count');
    }

    /**
     * Stocke les métriques dans le cache
     */
    private function storeMetrics(array $metrics)
    {
        try {
            $cacheKey = 'performance_metrics_' . Carbon::now()->format('Y-m-d-H');

            $existingMetrics = Cache::store(self::CACHE_STORE)->get($cacheKey, []);
            $existingMetrics[] = $metrics;

            // Garder seulement les 100 dernières métriques par heure
            if (count($existingMetrics) > 100) {
                $existingMetrics = array_slice($existingMetrics, -100);
            }

            Cache::store(self::CACHE_STORE)->put($cacheKey, $existingMetrics, self::METRICS_TTL);

        } catch (\Exception $e) {
            Log::error("Erreur stockage métriques", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log les métriques selon leur criticité
     */
    private function logMetrics(array $metrics)
    {
        if ($metrics['execution_time_ms'] > 5000) {
            // Très lent
            Log::error("🐌 Opération très lente", $metrics);
        } elseif ($metrics['execution_time_ms'] > 2000) {
            // Lent
            Log::warning("🕐 Opération lente", $metrics);
        } elseif ($metrics['execution_time_ms'] > 1000) {
            // Moyen
            Log::info("📊 Métriques performance", $metrics);
        } else {
            // Rapide
            Log::debug("⚡ Performance normale", $metrics);
        }
    }

    /**
     * Obtient le nombre de requêtes SQL executées
     */
    private function getQueryCount(): int
    {
        try {
            return count(DB::getQueryLog());
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Incrémente un compteur d'alerte
     */
    private function incrementAlertCounter(string $type)
    {
        try {
            $cacheKey = "alert_counter_{$type}_" . Carbon::now()->format('Y-m-d');
            $count = Cache::store(self::CACHE_STORE)->get($cacheKey, 0);
            Cache::store(self::CACHE_STORE)->put($cacheKey, $count + 1, 24 * 60); // 24h
        } catch (\Exception $e) {
            // Ignorer les erreurs de cache
        }
    }

    /**
     * Obtient les métriques de performance récentes
     */
    public function getRecentMetrics(int $hours = 1): array
    {
        try {
            $metrics = [];

            for ($i = 0; $i < $hours; $i++) {
                $hour = Carbon::now()->subHours($i)->format('Y-m-d-H');
                $cacheKey = "performance_metrics_{$hour}";
                $hourMetrics = Cache::store(self::CACHE_STORE)->get($cacheKey, []);
                $metrics = array_merge($metrics, $hourMetrics);
            }

            return $metrics;

        } catch (\Exception $e) {
            Log::error("Erreur récupération métriques", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Génère un rapport de performance
     */
    public function generatePerformanceReport(int $hours = 24): array
    {
        $metrics = $this->getRecentMetrics($hours);

        if (empty($metrics)) {
            return [
                'total_operations' => 0,
                'avg_execution_time' => 0,
                'avg_memory_usage' => 0,
                'slow_operations' => 0,
                'error_rate' => 0
            ];
        }

        $totalOperations = count($metrics);
        $executionTimes = array_column($metrics, 'execution_time_ms');
        $memoryUsages = array_column($metrics, 'memory_usage_mb');
        $slowOperations = count(array_filter($executionTimes, fn($time) => $time > 2000));

        return [
            'total_operations' => $totalOperations,
            'avg_execution_time' => round(array_sum($executionTimes) / $totalOperations, 2),
            'max_execution_time' => max($executionTimes),
            'min_execution_time' => min($executionTimes),
            'avg_memory_usage' => round(array_sum($memoryUsages) / $totalOperations, 2),
            'max_memory_usage' => max($memoryUsages),
            'slow_operations' => $slowOperations,
            'slow_operations_rate' => round(($slowOperations / $totalOperations) * 100, 2),
            'operations_by_type' => array_count_values(array_column($metrics, 'operation')),
            'period' => "{$hours} dernières heures",
            'generated_at' => now()->toISOString()
        ];
    }

    /**
     * Nettoie les anciennes métriques
     */
    public function cleanupOldMetrics(int $daysToKeep = 7)
    {
        try {
            $cutoffDate = Carbon::now()->subDays($daysToKeep);

            for ($date = $cutoffDate; $date <= Carbon::now()->subDay(); $date->addDay()) {
                for ($hour = 0; $hour < 24; $hour++) {
                    $cacheKey = "performance_metrics_" . $date->format('Y-m-d') . "-{$hour}";
                    Cache::store(self::CACHE_STORE)->forget($cacheKey);
                }
            }

            Log::info("Nettoyage métriques anciennes terminé", [
                'days_kept' => $daysToKeep,
                'cutoff_date' => $cutoffDate->toDateString()
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur nettoyage métriques", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Surveille l'état du cache
     */
    public function getCacheStatus(): array
    {
        $stores = ['comptabilite_kpis', 'dashboard_queries', 'comptabilite_reports', 'heavy_calculations'];
        $status = [];

        foreach ($stores as $store) {
            try {
                $testKey = "cache_test_" . time();
                $testValue = "test_value";

                // Test écriture
                Cache::store($store)->put($testKey, $testValue, 1);

                // Test lecture
                $retrieved = Cache::store($store)->get($testKey);

                // Nettoyage
                Cache::store($store)->forget($testKey);

                $status[$store] = [
                    'status' => 'healthy',
                    'writable' => true,
                    'readable' => $retrieved === $testValue
                ];

            } catch (\Exception $e) {
                $status[$store] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'writable' => false,
                    'readable' => false
                ];
            }
        }

        return $status;
    }
}
