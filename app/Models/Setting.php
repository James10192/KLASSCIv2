<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class Setting extends Model
{
    use HasFactory;

    /**
     * Les attributs qui peuvent être assignés en masse.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_required',
        'default_value',
        'validation_rules',
        'is_active',
        'requires_restart',
        'category',
        'sort_order',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'validation_rules' => 'json',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'requires_restart' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Relations
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Récupère un paramètre par sa clé.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        try {
            $cacheKey = "setting_{$key}";

            return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
                $setting = static::where('key', $key)->where('is_active', true)->first();

                if (!$setting) {
                    return $default;
                }

                return static::castValue($setting->value, $setting->type);
            });
        } catch (\Exception $e) {
            // Fallback sans cache en cas d'erreur
            $setting = static::where('key', $key)->where('is_active', true)->first();

            if (!$setting) {
            return $default;
            }

            return static::castValue($setting->value, $setting->type);
        }
    }

    /**
     * Définit ou met à jour un paramètre.
     *
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @return bool
     */
    public static function set($key, $value, $userId = null)
    {
        try {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                throw new \Exception("Configuration '{$key}' non trouvée");
            }

            // Validation de la valeur
            if (!static::validateValue($value, $setting)) {
                throw new \Exception("Valeur invalide pour la configuration '{$key}'");
            }

            // Sauvegarde automatique avant modification
            static::createAutoBackup($userId);

            $setting->update([
                'value' => $value,
                'updated_by' => $userId
            ]);

            // Vider le cache (avec gestion d'erreur)
            try {
                Cache::forget("setting_{$key}");
            } catch (\Exception $e) {
                // Ignorer les erreurs de cache
            }

            Log::info("Configuration mise à jour", [
                'key' => $key,
                'value' => $value,
                'user_id' => $userId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur lors de la mise à jour de la configuration", [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public static function getByGroup($group)
    {
        try {
            $cacheKey = "settings_group_{$group}";

            return Cache::remember($cacheKey, 3600, function () use ($group) {
                return static::where('group', $group)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->mapWithKeys(function ($setting) {
                        return [$setting->key => static::castValue($setting->value, $setting->type)];
                    });
            });
        } catch (\Exception $e) {
            // Fallback sans cache
            return static::where('group', $group)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => static::castValue($setting->value, $setting->type)];
                });
        }
    }

    public static function getByCategory($category)
    {
        try {
            $cacheKey = "settings_category_{$category}";

            return Cache::remember($cacheKey, 3600, function () use ($category) {
                return static::where('category', $category)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();
            });
        } catch (\Exception $e) {
            // Fallback sans cache
            return static::where('category', $category)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }
    }

    // Vérification des configurations requises
    public static function checkRequiredSettings()
    {
        $missing = static::where('is_required', true)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('value')
                    ->orWhere('value', '')
                    ->orWhere('value', '[]')
                    ->orWhere('value', '{}');
            })
            ->get();

        return $missing;
    }

    // Validation des valeurs
    protected static function validateValue($value, $setting)
    {
        if (!$setting->validation_rules) {
            return true;
        }

        $validator = Validator::make(
            ['value' => $value],
            ['value' => $setting->validation_rules]
        );

        return !$validator->fails();
    }

    // Cast des valeurs selon le type
    protected static function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'json':
                return json_decode($value, true);
            case 'array':
                return is_array($value) ? $value : json_decode($value, true);
            default:
                return $value;
        }
    }

    // Sauvegarde automatique
    protected static function createAutoBackup($userId = null)
    {
        try {
            $settings = static::all()->toArray();

            SettingsBackup::create([
                'backup_name' => 'Auto Backup - ' . now()->format('Y-m-d H:i:s'),
                'description' => 'Sauvegarde automatique avant modification',
                'settings_data' => $settings,
                'backup_type' => 'automatic',
                'backup_date' => now(),
                'created_by' => $userId ?? 1
            ]);

            // Nettoyer les anciennes sauvegardes automatiques (garder les 10 dernières)
            $oldBackups = SettingsBackup::where('backup_type', 'automatic')
                ->orderBy('backup_date', 'desc')
                ->skip(10)
                ->take(100)
                ->get();

            foreach ($oldBackups as $backup) {
                $backup->delete();
            }

        } catch (\Exception $e) {
            Log::warning("Impossible de créer une sauvegarde automatique", [
                'error' => $e->getMessage()
            ]);
        }
    }

    // Vider tous les caches
    public static function clearCache()
    {
        try {
            $groups = static::distinct('group')->pluck('group');
            $categories = static::distinct('category')->whereNotNull('category')->pluck('category');
            $keys = static::pluck('key');

            foreach ($keys as $key) {
                try {
                    Cache::forget("setting_{$key}");
                } catch (\Exception $e) {
                    // Ignorer les erreurs de cache individuelles
                }
            }

            foreach ($groups as $group) {
                try {
                    Cache::forget("settings_group_{$group}");
                } catch (\Exception $e) {
                    // Ignorer les erreurs de cache individuelles
                }
            }

            foreach ($categories as $category) {
                try {
                    Cache::forget("settings_category_{$category}");
                } catch (\Exception $e) {
                    // Ignorer les erreurs de cache individuelles
                }
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs de cache globales
            Log::warning("Erreur lors du nettoyage du cache des settings", [
                'error' => $e->getMessage()
            ]);
        }
    }

    // Événements du modèle
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            try {
                Cache::forget("setting_{$setting->key}");
                Cache::forget("settings_group_{$setting->group}");
                if ($setting->category) {
                    Cache::forget("settings_category_{$setting->category}");
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs de cache
            }
        });

        static::deleted(function ($setting) {
            try {
                Cache::forget("setting_{$setting->key}");
                Cache::forget("settings_group_{$setting->group}");
                if ($setting->category) {
                    Cache::forget("settings_category_{$setting->category}");
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs de cache
            }
        });
    }
}
