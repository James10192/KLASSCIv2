<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckRequiredSettings
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$requiredSettings)
    {
        // Vérifier si l'utilisateur est authentifié et a les permissions
        if (!auth()->check() || !auth()->user()->hasRole(['superAdmin', 'secretaire'])) {
            return $next($request);
        }

        // Obtenir les configurations manquantes
        $missingSettings = $this->getMissingRequiredSettings($requiredSettings);

        if (!empty($missingSettings)) {
            // Log de l'accès bloqué
            Log::warning('Accès bloqué - Configurations manquantes', [
                'user_id' => auth()->id(),
                'route' => $request->route()->getName(),
                'missing_settings' => $missingSettings,
                'ip' => $request->ip()
            ]);

            // Si c'est une requête AJAX, retourner JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => 'Configurations manquantes',
                    'message' => 'Certaines configurations requises ne sont pas définies.',
                    'missing_settings' => $missingSettings,
                    'redirect_url' => route('esbtp.settings.index')
                ], 422);
            }

            // Redirection avec message d'erreur
            return redirect()->route('esbtp.settings.index')
                ->with('error', 'Certaines configurations requises ne sont pas définies.')
                ->with('missing_settings', $missingSettings)
                ->with('blocked_route', $request->route()->getName());
        }

        return $next($request);
    }

    /**
     * Obtenir les configurations requises manquantes
     */
    private function getMissingRequiredSettings($requiredSettings = [])
    {
        // Cache des configurations manquantes pour 5 minutes
        $cacheKey = 'missing_required_settings_' . md5(implode(',', $requiredSettings));

        return Cache::remember($cacheKey, 300, function () use ($requiredSettings) {
            $missing = [];

            // Si des configurations spécifiques sont requises
            if (!empty($requiredSettings)) {
                foreach ($requiredSettings as $settingKey) {
                    $setting = Setting::where('key', $settingKey)->first();

                    if (!$setting || empty($setting->value)) {
                        $missing[] = [
                            'key' => $settingKey,
                            'description' => $setting->description ?? 'Configuration requise',
                            'category' => $setting->category ?? 'Général'
                        ];
                    }
                }
            } else {
                // Vérifier toutes les configurations marquées comme requises
                $requiredSettingsFromDB = Setting::where('is_required', true)
                    ->where('is_active', true)
                    ->get();

                foreach ($requiredSettingsFromDB as $setting) {
                    if (empty($setting->value)) {
                        $missing[] = [
                            'key' => $setting->key,
                            'description' => $setting->description,
                            'category' => $setting->category ?? 'Général'
                        ];
                    }
                }
            }

            return $missing;
        });
    }

    /**
     * Vérifier si une configuration spécifique est définie
     */
    public static function isSettingConfigured($key)
    {
        $setting = Setting::where('key', $key)->first();
        return $setting && !empty($setting->value);
    }

    /**
     * Obtenir toutes les configurations manquantes pour l'affichage
     */
    public static function getAllMissingSettings()
    {
        return Cache::remember('all_missing_required_settings', 300, function () {
            $missing = [];

            $requiredSettings = Setting::where('is_required', true)
                ->where('is_active', true)
                ->get();

            foreach ($requiredSettings as $setting) {
                if (empty($setting->value)) {
                    $missing[] = [
                        'key' => $setting->key,
                        'description' => $setting->description,
                        'category' => $setting->category ?? 'Général',
                        'type' => $setting->type,
                        'default_value' => $setting->default_value
                    ];
                }
            }

            return $missing;
        });
    }

    /**
     * Vider le cache des configurations manquantes
     */
    public static function clearCache()
    {
        try {
            Cache::forget('all_missing_required_settings');

            // Pour éviter l'erreur Redis avec le driver file, on utilise une approche différente
            // On va simplement vider les caches connus plutôt que de chercher avec keys()

            // Générer quelques clés possibles basées sur des combinaisons communes
            $commonKeys = [
                'missing_required_settings_' . md5(''),
                'missing_required_settings_' . md5('school_name'),
                'missing_required_settings_' . md5('school_name,director_name'),
                'missing_required_settings_' . md5('pdf_margin_top,pdf_margin_bottom'),
            ];

            foreach ($commonKeys as $key) {
                try {
                    Cache::forget($key);
                } catch (\Exception $e) {
                    // Ignorer les erreurs de cache individuelles
                }
            }

            // Alternative : vider tout le cache si possible
            try {
                if (method_exists(Cache::getStore(), 'flush')) {
                    // Seulement pour les drivers qui supportent flush
                    // Cache::flush(); // Commenté pour éviter de vider tout le cache
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs de flush
            }

        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer l'opération
            Log::warning('Erreur lors du nettoyage du cache des settings requis', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
