<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;

trait CacheInvalidationTrait
{
    /**
     * Boot the trait and register model events
     */
    public static function bootCacheInvalidationTrait()
    {
        static::created(function ($model) {
            $model->invalidateModelCache();
        });

        static::updated(function ($model) {
            $model->invalidateModelCache();
        });

        static::deleted(function ($model) {
            $model->invalidateModelCache();
        });
    }

    /**
     * Invalidate cache keys related to this model
     */
    public function invalidateModelCache()
    {
        $cacheKeys = $this->getCacheKeys();

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Invalider les tags de cache si Redis est utilisé
        if (config('cache.default') === 'redis') {
            $tags = $this->getCacheTags();
            if (!empty($tags)) {
                Cache::tags($tags)->flush();
            }
        }
    }

    /**
     * Get cache keys to invalidate for this model
     */
    protected function getCacheKeys()
    {
        $modelName = class_basename($this);
        $modelKey = strtolower($modelName);

        return [
            "comptabilite.{$modelKey}.all",
            "comptabilite.{$modelKey}.stats",
            "comptabilite.kpis",
            "comptabilite.dashboard",
            "comptabilite.{$modelKey}.{$this->id}",
        ];
    }

    /**
     * Get cache tags for this model
     */
    protected function getCacheTags()
    {
        $modelName = class_basename($this);

        return [
            'comptabilite',
            strtolower($modelName),
            'kpis',
            'dashboard'
        ];
    }

    /**
     * Clear all comptabilite cache
     */
    public static function clearComptabiliteCache()
    {
        $keys = [
            'comptabilite.*',
            'dashboard.*',
            'kpis.*'
        ];

        foreach ($keys as $pattern) {
            if (config('cache.default') === 'redis') {
                Cache::tags(['comptabilite', 'dashboard', 'kpis'])->flush();
            } else {
                // Pour les drivers qui ne supportent pas les tags
                $cacheKeys = [
                    'comptabilite.depenses.all',
                    'comptabilite.paiements.all',
                    'comptabilite.factures.all',
                    'comptabilite.kpis',
                    'comptabilite.dashboard',
                    'comptabilite.stats'
                ];

                foreach ($cacheKeys as $key) {
                    Cache::forget($key);
                }
            }
        }
    }
}
