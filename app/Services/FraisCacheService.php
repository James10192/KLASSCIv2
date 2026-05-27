<?php

namespace App\Services;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisOption;
use App\Models\ESBTPInscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service de cache pour optimiser les performances du système de frais
 * Utilise des stratégies de cache avancées avec invalidation intelligente
 */
class FraisCacheService
{
    private const CACHE_PREFIX = 'frais_cache_';
    private const DEFAULT_TTL = 3600; // 1 heure
    private const LONG_TTL = 86400; // 24 heures
    private const SHORT_TTL = 900; // 15 minutes

    /**
     * Cache les configurations de frais pour une classe
     */
    public function cacheClassConfigurations($filiereId, $niveauId, $anneeId = null, ?string $systeme = 'BTS', $parcoursId = null): array
    {
        $cacheKey = $this->buildKey('class_configs', [$systeme, $filiereId, $parcoursId, $niveauId, $anneeId]);
        
        return Cache::remember($cacheKey, self::DEFAULT_TTL, function () use ($filiereId, $niveauId, $anneeId, $systeme, $parcoursId, $cacheKey) {
            $query = ESBTPFraisConfiguration::queryForScope([
                    'systeme' => $systeme,
                    'filiere_id' => $filiereId,
                    'parcours_id' => $parcoursId,
                    'niveau_id' => $niveauId,
                ])
                ->active()
                ->valid()
                ->with(['fraisCategory', 'options' => function ($query) {
                    $query->active()->ordered();
                }]);

            if ($anneeId) {
                $query->where('annee_universitaire_id', $anneeId);
            } else {
                $query->whereNull('annee_universitaire_id');
            }

            $configurations = $query->get();
            
            Log::info('Configurations mises en cache', [
                'systeme' => $systeme,
                'filiere_id' => $filiereId,
                'parcours_id' => $parcoursId,
                'niveau_id' => $niveauId,
                'annee_id' => $anneeId,
                'count' => $configurations->count(),
                'cache_key' => $cacheKey
            ]);

            return $configurations->toArray();
        });
    }

    /**
     * Cache les catégories actives avec leurs métadonnées
     */
    public function cacheActiveCategories(): array
    {
        $cacheKey = $this->buildKey('active_categories');
        
        return Cache::remember($cacheKey, self::LONG_TTL, function () use ($cacheKey) {
            $categories = ESBTPFraisCategory::active()
                ->ordered()
                ->withCount(['configurations' => function ($query) {
                    $query->active();
                }])
                ->get();

            Log::info('Catégories actives mises en cache', [
                'count' => $categories->count(),
                'cache_key' => $cacheKey
            ]);

            return $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'code' => $category->code,
                    'description' => $category->description,
                    'category_type' => $category->category_type,
                    'is_mandatory' => $category->is_mandatory,
                    'default_amount' => $category->default_amount,
                    'payment_deadline_days' => $category->payment_deadline_days,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'configurations_count' => $category->configurations_count,
                ];
            })->toArray();
        });
    }

    /**
     * Cache les options disponibles pour une catégorie
     */
    public function cacheCategoryOptions($categoryId): array
    {
        $cacheKey = $this->buildKey('category_options', [$categoryId]);
        
        return Cache::remember($cacheKey, self::DEFAULT_TTL, function () use ($categoryId, $cacheKey) {
            $options = ESBTPFraisOption::active()
                ->forCategory($categoryId)
                ->ordered()
                ->get();

            Log::info('Options de catégorie mises en cache', [
                'category_id' => $categoryId,
                'count' => $options->count(),
                'cache_key' => $cacheKey
            ]);

            return $options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'name' => $option->name,
                    'description' => $option->description,
                    'option_type' => $option->option_type,
                    'base_amount' => $option->base_amount,
                    'amount_modifier' => $option->amount_modifier,
                    'modifier_type' => $option->modifier_type,
                    'is_default' => $option->is_default,
                    'requires_approval' => $option->requires_approval,
                    'capacity_limit' => $option->capacity_limit,
                    'available_spots' => $option->getAvailableSpots(),
                    'metadata' => $option->metadata,
                ];
            })->toArray();
        });
    }

    /**
     * Cache les statistiques globales des frais
     */
    public function cacheGlobalStats(): array
    {
        $cacheKey = $this->buildKey('global_stats');
        
        return Cache::remember($cacheKey, self::SHORT_TTL, function () use ($cacheKey) {
            $stats = [
                'total_categories' => ESBTPFraisCategory::count(),
                'active_categories' => ESBTPFraisCategory::active()->count(),
                'mandatory_categories' => ESBTPFraisCategory::mandatory()->count(),
                'optional_categories' => ESBTPFraisCategory::optional()->count(),
                'total_configurations' => ESBTPFraisConfiguration::count(),
                'active_configurations' => ESBTPFraisConfiguration::active()->count(),
                'total_options' => ESBTPFraisOption::count(),
                'active_options' => ESBTPFraisOption::active()->count(),
                'categories_by_type' => [
                    'academic' => ESBTPFraisCategory::academic()->count(),
                    'service' => ESBTPFraisCategory::service()->count(),
                    'administrative' => ESBTPFraisCategory::administrative()->count(),
                ],
                'last_updated' => Carbon::now()->toISOString(),
            ];

            Log::info('Statistiques globales mises en cache', [
                'stats' => $stats,
                'cache_key' => $cacheKey
            ]);

            return $stats;
        });
    }

    /**
     * Cache le calcul de frais pour une inscription spécifique
     */
    public function cacheInscriptionCalculation(ESBTPInscription $inscription, array $calculationResult): void
    {
        $cacheKey = $this->buildKey('inscription_calculation', [
            $inscription->id, 
            $inscription->updated_at->timestamp
        ]);
        
        Cache::put($cacheKey, $calculationResult, self::DEFAULT_TTL);
        
        Log::info('Calcul d\'inscription mis en cache', [
            'inscription_id' => $inscription->id,
            'cache_key' => $cacheKey,
            'total_amount' => $calculationResult['totals']['grand_total'] ?? 0
        ]);
    }

    /**
     * Récupère le calcul en cache pour une inscription
     */
    public function getInscriptionCalculation(ESBTPInscription $inscription): ?array
    {
        $cacheKey = $this->buildKey('inscription_calculation', [
            $inscription->id, 
            $inscription->updated_at->timestamp
        ]);
        
        $result = Cache::get($cacheKey);
        
        if ($result) {
            Log::info('Calcul d\'inscription récupéré du cache', [
                'inscription_id' => $inscription->id,
                'cache_key' => $cacheKey
            ]);
        }
        
        return $result;
    }

    /**
     * Cache la configuration applicable pour un contexte donné
     */
    public function cacheApplicableConfiguration($categoryId, $filiereId, $niveauId, $anneeId = null, ?string $systeme = 'BTS', $parcoursId = null): ?array
    {
        $cacheKey = $this->buildKey('applicable_config', [$categoryId, $systeme, $filiereId, $parcoursId, $niveauId, $anneeId]);
        
        return Cache::remember($cacheKey, self::DEFAULT_TTL, function () use ($categoryId, $filiereId, $niveauId, $anneeId, $systeme, $parcoursId, $cacheKey) {
            $configuration = ESBTPFraisConfiguration::getApplicableForScope($categoryId, [
                'systeme' => $systeme,
                'filiere_id' => $filiereId,
                'parcours_id' => $parcoursId,
                'niveau_id' => $niveauId,
                'annee_universitaire_id' => $anneeId,
            ]);
            
            Log::info('Configuration applicable mise en cache', [
                'category_id' => $categoryId,
                'systeme' => $systeme,
                'filiere_id' => $filiereId,
                'parcours_id' => $parcoursId,
                'niveau_id' => $niveauId,
                'annee_id' => $anneeId,
                'found' => $configuration ? true : false,
                'cache_key' => $cacheKey
            ]);
            
            return $configuration ? $configuration->toArray() : null;
        });
    }

    /**
     * Cache les métadonnées de calcul pour le monitoring
     */
    public function cacheCalculationMetrics(): array
    {
        $cacheKey = $this->buildKey('calculation_metrics');
        
        return Cache::remember($cacheKey, self::SHORT_TTL, function () use ($cacheKey) {
            $metrics = [
                'cache_hits' => $this->getCacheHits(),
                'cache_misses' => $this->getCacheMisses(),
                'active_cache_keys' => $this->getActiveCacheKeys(),
                'memory_usage' => $this->estimateCacheMemoryUsage(),
                'last_calculated' => Carbon::now()->toISOString(),
            ];

            Log::info('Métriques de calcul mises en cache', [
                'metrics' => $metrics,
                'cache_key' => $cacheKey
            ]);

            return $metrics;
        });
    }

    /**
     * Invalide le cache pour une inscription spécifique
     */
    public function invalidateInscriptionCache(ESBTPInscription $inscription): void
    {
        $patterns = [
            $this->buildKey('inscription_calculation', [$inscription->id, '*']),
            $this->buildKey('class_configs', ['*', $inscription->filiere_id, '*', $inscription->niveau_id, '*']),
        ];

        foreach ($patterns as $pattern) {
            $this->forgetByPattern($pattern);
        }

        Log::info('Cache invalidé pour l\'inscription', [
            'inscription_id' => $inscription->id,
            'patterns' => $patterns
        ]);
    }

    /**
     * Invalide le cache pour une catégorie spécifique
     */
    public function invalidateCategoryCache($categoryId): void
    {
        $patterns = [
            $this->buildKey('category_options', [$categoryId]),
            $this->buildKey('applicable_config', [$categoryId, '*']),
            $this->buildKey('active_categories'),
            $this->buildKey('global_stats'),
        ];

        foreach ($patterns as $pattern) {
            $this->forgetByPattern($pattern);
        }

        Log::info('Cache invalidé pour la catégorie', [
            'category_id' => $categoryId,
            'patterns' => $patterns
        ]);
    }

    /**
     * Invalide tout le cache des frais
     */
    public function invalidateAllFraisCache(): void
    {
        $pattern = self::CACHE_PREFIX . '*';
        $this->forgetByPattern($pattern);
        
        Log::info('Tout le cache des frais invalidé', [
            'pattern' => $pattern
        ]);
    }

    /**
     * Préchauffe le cache avec les données fréquemment utilisées
     */
    public function warmupCache(): void
    {
        Log::info('Début du préchauffage du cache');
        
        // Préchauffer les catégories actives
        $this->cacheActiveCategories();
        
        // Préchauffer les statistiques globales
        $this->cacheGlobalStats();
        
        // Préchauffer les options pour chaque catégorie active
        $categories = ESBTPFraisCategory::active()->pluck('id');
        foreach ($categories as $categoryId) {
            $this->cacheCategoryOptions($categoryId);
        }
        
        // Préchauffer les configurations pour les classes actives
        $activeClasses = ESBTPInscription::select('filiere_id', 'niveau_id', 'annee_universitaire_id')
            ->where('status', 'active')
            ->distinct()
            ->get();
            
        foreach ($activeClasses as $class) {
            $this->cacheClassConfigurations(
                $class->filiere_id,
                $class->niveau_id,
                $class->annee_universitaire_id
            );
        }
        
        Log::info('Préchauffage du cache terminé', [
            'categories_cached' => $categories->count(),
            'classes_cached' => $activeClasses->count()
        ]);
    }

    /**
     * Obtenir les catégories actives (méthode d'accès simple)
     */
    public function getCategories()
    {
        // Retourner directement les objets Eloquent au lieu du cache array
        return ESBTPFraisCategory::active()
            ->ordered()
            ->withCount(['configurations' => function ($query) {
                $query->active();
            }])
            ->get();
    }

    /**
     * Obtenir les configurations pour une classe (méthode d'accès simple)
     */
    public function getConfigurations($filiereId, $niveauId, $anneeId = null, ?string $systeme = 'BTS', $parcoursId = null)
    {
        $query = ESBTPFraisConfiguration::queryForScope([
                'systeme' => $systeme,
                'filiere_id' => $filiereId,
                'parcours_id' => $parcoursId,
                'niveau_id' => $niveauId,
            ])
            ->active()
            ->valid()
            ->with(['fraisCategory', 'options' => function ($query) {
                $query->active()->ordered();
            }]);

        if ($anneeId) {
            $query->where('annee_universitaire_id', $anneeId);
        } else {
            $query->whereNull('annee_universitaire_id');
        }

        return $query->get();
    }

    /**
     * Invalider le cache des configurations pour une classe spécifique
     */
    public function invalidateConfigurationCache($filiereId, $niveauId, $anneeId = null, ?string $systeme = 'BTS', $parcoursId = null): void
    {
        $cacheKey = $this->buildKey('class_configs', [$systeme, $filiereId, $parcoursId, $niveauId, $anneeId]);
        Cache::forget($cacheKey);
        
        Log::info('Cache des configurations invalidé', [
            'filiere_id' => $filiereId,
            'niveau_id' => $niveauId,
            'annee_id' => $anneeId
        ]);
    }

    /**
     * Invalider le cache des options
     */
    public function invalidateOptionsCache($categoryId = null): void
    {
        if ($categoryId) {
            $cacheKey = $this->buildKey('category_options', [$categoryId]);
            Cache::forget($cacheKey);
        } else {
            // Invalider tous les caches d'options
            $this->invalidateAllFraisCache();
        }
        
        Log::info('Cache des options invalidé', ['category_id' => $categoryId]);
    }

    /**
     * Obtient les informations sur le cache actuel
     */
    public function getCacheInfo(): array
    {
        return [
            'active_keys' => $this->getActiveCacheKeys(),
            'estimated_memory' => $this->estimateCacheMemoryUsage(),
            'cache_hits' => $this->getCacheHits(),
            'cache_misses' => $this->getCacheMisses(),
            'hit_ratio' => $this->getCacheHitRatio(),
            'last_warmup' => Cache::get(self::CACHE_PREFIX . 'last_warmup'),
        ];
    }

    /**
     * Construit une clé de cache standardisée
     */
    private function buildKey(string $type, array $params = []): string
    {
        $keyParts = [self::CACHE_PREFIX, $type];
        
        foreach ($params as $param) {
            $keyParts[] = $param ?? 'null';
        }
        
        return implode('_', $keyParts);
    }

    /**
     * Supprime les clés de cache correspondant à un pattern
     */
    private function forgetByPattern(string $pattern): void
    {
        // Note: Cette implémentation dépend du driver de cache utilisé
        // Pour Redis, on pourrait utiliser KEYS ou SCAN
        // Pour l'exemple, on utilise une approche simplifiée
        
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Cache::getStore()->getRedis();
            $keys = $redis->keys($pattern);
            
            if (!empty($keys)) {
                $redis->del($keys);
                Log::info('Clés supprimées du cache Redis', [
                    'pattern' => $pattern,
                    'count' => count($keys)
                ]);
            }
        } else {
            // Pour les autres drivers, suppression manuelle des clés connues
            Cache::forget($pattern);
        }
    }

    /**
     * Obtient le nombre de clés actives en cache
     */
    private function getActiveCacheKeys(): int
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Cache::getStore()->getRedis();
            return count($redis->keys(self::CACHE_PREFIX . '*'));
        }
        
        return 0; // Non disponible pour les autres drivers
    }

    /**
     * Estime l'utilisation mémoire du cache
     */
    private function estimateCacheMemoryUsage(): string
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Cache::getStore()->getRedis();
            $info = $redis->info('memory');
            return $info['used_memory_human'] ?? 'N/A';
        }
        
        return 'N/A';
    }

    /**
     * Obtient le nombre de cache hits
     */
    private function getCacheHits(): int
    {
        return (int) Cache::get(self::CACHE_PREFIX . 'hits', 0);
    }

    /**
     * Obtient le nombre de cache misses
     */
    private function getCacheMisses(): int
    {
        return (int) Cache::get(self::CACHE_PREFIX . 'misses', 0);
    }

    /**
     * Calcule le ratio de cache hits
     */
    private function getCacheHitRatio(): float
    {
        $hits = $this->getCacheHits();
        $misses = $this->getCacheMisses();
        $total = $hits + $misses;
        
        return $total > 0 ? ($hits / $total) * 100 : 0;
    }
}
