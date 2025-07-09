<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PerformanceMonitoring
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Activer le log des requêtes SQL
        DB::enableQueryLog();

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Traiter la requête
        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        // Calculer les métriques
        $executionTime = ($endTime - $startTime) * 1000; // en ms
        $memoryUsage = ($endMemory - $startMemory) / 1024 / 1024; // en MB
        $queryCount = count(DB::getQueryLog());

        // Collecter les données de performance
        $metrics = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'route' => optional($request->route())->getName(),
            'execution_time_ms' => round($executionTime, 2),
            'memory_usage_mb' => round($memoryUsage, 2),
            'query_count' => $queryCount,
            'status_code' => $response->getStatusCode(),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ];

        // Détecter les problèmes de performance
        $this->analyzePerformance($metrics, $request);

        // Stocker les métriques si nécessaire
        $this->storeMetricsIfNeeded($metrics);

        // Ajouter les headers de performance pour le debug
        if (config('app.debug') || $request->has('debug_performance')) {
            $response->headers->set('X-Execution-Time', $executionTime . 'ms');
            $response->headers->set('X-Memory-Usage', $memoryUsage . 'MB');
            $response->headers->set('X-Query-Count', $queryCount);
        }

        return $response;
    }

    /**
     * Analyse les métriques de performance et génère des alertes
     */
    private function analyzePerformance(array $metrics, Request $request)
    {
        $route = $metrics['route'] ?? 'unknown';
        $executionTime = $metrics['execution_time_ms'];
        $queryCount = $metrics['query_count'];
        $memoryUsage = $metrics['memory_usage_mb'];

        // Seuils de performance
        $slowRequestThreshold = 2000; // 2 secondes
        $highQueryThreshold = 15;
        $highMemoryThreshold = 128; // 128 MB

        // Alerte pour requête lente
        if ($executionTime > $slowRequestThreshold) {
            Log::warning('🐌 Requête lente détectée', [
                'route' => $route,
                'url' => $metrics['url'],
                'execution_time_ms' => $executionTime,
                'threshold_ms' => $slowRequestThreshold,
                'query_count' => $queryCount,
                'user_id' => $metrics['user_id']
            ]);

            $this->incrementSlowRequestCounter($route);
        }

        // Alerte pour nombre élevé de requêtes
        if ($queryCount > $highQueryThreshold) {
            Log::warning('🔍 Nombre élevé de requêtes SQL', [
                'route' => $route,
                'url' => $metrics['url'],
                'query_count' => $queryCount,
                'threshold' => $highQueryThreshold,
                'execution_time_ms' => $executionTime,
                'recommendation' => 'Vérifier eager loading et optimiser les requêtes'
            ]);

            // Logger les requêtes SQL en mode debug
            if (config('app.debug')) {
                $queries = DB::getQueryLog();
                Log::debug('Requêtes SQL détaillées', ['queries' => $queries]);
            }
        }

        // Alerte pour utilisation mémoire élevée
        if ($memoryUsage > $highMemoryThreshold) {
            Log::warning('💾 Utilisation mémoire élevée', [
                'route' => $route,
                'url' => $metrics['url'],
                'memory_usage_mb' => $memoryUsage,
                'threshold_mb' => $highMemoryThreshold,
                'recommendation' => 'Vérifier les collections et optimiser le code'
            ]);
        }

        // Détecter les patterns de performance par route
        $this->detectPerformancePatterns($route, $metrics);
    }

    /**
     * Incrémente le compteur de requêtes lentes
     */
    private function incrementSlowRequestCounter(string $route)
    {
        try {
            $cacheKey = "slow_requests_counter_{$route}_" . Carbon::now()->format('Y-m-d');
            $count = Cache::store('dashboard_queries')->get($cacheKey, 0);
            Cache::store('dashboard_queries')->put($cacheKey, $count + 1, 24 * 60); // 24h

            // Alerte si trop de requêtes lentes pour cette route
            if ($count > 10) {
                Log::error('⚠️  Route avec de nombreuses requêtes lentes', [
                    'route' => $route,
                    'slow_requests_today' => $count + 1,
                    'recommendation' => 'Investigation urgente nécessaire'
                ]);
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs de cache
        }
    }

    /**
     * Détecte les patterns de performance par route
     */
    private function detectPerformancePatterns(string $route, array $metrics)
    {
        try {
            $cacheKey = "performance_pattern_{$route}";
            $pattern = Cache::store('dashboard_queries')->get($cacheKey, [
                'avg_execution_time' => 0,
                'avg_query_count' => 0,
                'request_count' => 0,
                'last_updated' => now()
            ]);

            // Calculer les nouvelles moyennes
            $requestCount = $pattern['request_count'] + 1;
            $avgExecutionTime = (($pattern['avg_execution_time'] * $pattern['request_count']) + $metrics['execution_time_ms']) / $requestCount;
            $avgQueryCount = (($pattern['avg_query_count'] * $pattern['request_count']) + $metrics['query_count']) / $requestCount;

            // Mettre à jour le pattern
            $newPattern = [
                'avg_execution_time' => round($avgExecutionTime, 2),
                'avg_query_count' => round($avgQueryCount, 2),
                'request_count' => $requestCount,
                'last_updated' => now()
            ];

            Cache::store('dashboard_queries')->put($cacheKey, $newPattern, 24 * 60); // 24h

            // Détecter les dégradations de performance
            if ($requestCount > 10) {
                $performanceDegradation = ($metrics['execution_time_ms'] / $avgExecutionTime) > 2;
                if ($performanceDegradation) {
                    Log::warning('📈 Dégradation de performance détectée', [
                        'route' => $route,
                        'current_time_ms' => $metrics['execution_time_ms'],
                        'average_time_ms' => $avgExecutionTime,
                        'degradation_factor' => round($metrics['execution_time_ms'] / $avgExecutionTime, 2)
                    ]);
                }
            }

        } catch (\Exception $e) {
            // Ignorer les erreurs de pattern detection
        }
    }

    /**
     * Stocke les métriques si nécessaire
     */
    private function storeMetricsIfNeeded(array $metrics)
    {
        // Stocker seulement les métriques importantes ou problématiques
        $shouldStore =
            $metrics['execution_time_ms'] > 1000 || // > 1 seconde
            $metrics['query_count'] > 10 ||         // > 10 requêtes
            $metrics['memory_usage_mb'] > 64 ||     // > 64 MB
            $metrics['status_code'] >= 400;         // Erreurs HTTP

        if ($shouldStore) {
            try {
                $cacheKey = 'performance_metrics_' . Carbon::now()->format('Y-m-d-H');
                $storedMetrics = Cache::store('dashboard_queries')->get($cacheKey, []);

                $storedMetrics[] = $metrics;

                // Garder seulement les 50 dernières métriques par heure
                if (count($storedMetrics) > 50) {
                    $storedMetrics = array_slice($storedMetrics, -50);
                }

                Cache::store('dashboard_queries')->put($cacheKey, $storedMetrics, 60); // 1 heure

            } catch (\Exception $e) {
                Log::error('Erreur stockage métriques performance', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Obtient un rapport de performance pour une route spécifique
     */
    public static function getRoutePerformanceReport(string $route): array
    {
        try {
            $cacheKey = "performance_pattern_{$route}";
            $pattern = Cache::store('dashboard_queries')->get($cacheKey);

            if (!$pattern) {
                return [
                    'route' => $route,
                    'status' => 'no_data',
                    'message' => 'Aucune donnée de performance disponible'
                ];
            }

            // Déterminer le statut de performance
            $status = 'good';
            if ($pattern['avg_execution_time'] > 2000) {
                $status = 'poor';
            } elseif ($pattern['avg_execution_time'] > 1000) {
                $status = 'warning';
            }

            return [
                'route' => $route,
                'status' => $status,
                'avg_execution_time_ms' => $pattern['avg_execution_time'],
                'avg_query_count' => $pattern['avg_query_count'],
                'request_count' => $pattern['request_count'],
                'last_updated' => $pattern['last_updated'],
                'recommendations' => self::getPerformanceRecommendations($pattern)
            ];

        } catch (\Exception $e) {
            return [
                'route' => $route,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Génère des recommandations de performance
     */
    private static function getPerformanceRecommendations(array $pattern): array
    {
        $recommendations = [];

        if ($pattern['avg_execution_time'] > 2000) {
            $recommendations[] = 'Optimisation critique nécessaire - temps d\'exécution très élevé';
        } elseif ($pattern['avg_execution_time'] > 1000) {
            $recommendations[] = 'Optimisation recommandée - temps d\'exécution élevé';
        }

        if ($pattern['avg_query_count'] > 15) {
            $recommendations[] = 'Réduire le nombre de requêtes SQL avec eager loading';
        } elseif ($pattern['avg_query_count'] > 10) {
            $recommendations[] = 'Vérifier l\'optimisation des requêtes SQL';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Performances satisfaisantes';
        }

        return $recommendations;
    }

    /**
     * Nettoie les anciennes métriques de performance
     */
    public static function cleanupOldMetrics(int $daysToKeep = 7)
    {
        try {
            $cutoffDate = Carbon::now()->subDays($daysToKeep);

            for ($date = $cutoffDate; $date <= Carbon::now()->subDay(); $date->addDay()) {
                for ($hour = 0; $hour < 24; $hour++) {
                    $cacheKey = "performance_metrics_" . $date->format('Y-m-d') . "-{$hour}";
                    Cache::store('dashboard_queries')->forget($cacheKey);
                }
            }

            Log::info('Nettoyage métriques performance terminé', [
                'days_kept' => $daysToKeep,
                'cutoff_date' => $cutoffDate->toDateString()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur nettoyage métriques performance', ['error' => $e->getMessage()]);
        }
    }
}
