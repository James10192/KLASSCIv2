<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\ESBTPSystemSetting;
use App\Models\User;
use App\Models\ESBTPEtudiant;
use Carbon\Carbon;

class PaywallMiddleware
{
    /**
     * Routes exclues de la vérification paywall
     */
    protected $excludedRoutes = [
        'esbtp.paywall-config.blocked',
        'esbtp.paywall-config.upgrade',
        'logout',
        'login',
        'register',
        'password.*',
    ];


    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Debug: Logger la route actuelle avec tous les détails
        \Log::warning('🔥 PAYWALL MIDDLEWARE DÉMARRÉ', [
            'route_name' => $request->route() ? $request->route()->getName() : 'NO_ROUTE',
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_email' => $request->user() ? $request->user()->email : 'guest',
            'user_id' => $request->user() ? $request->user()->id : null,
            'user_roles' => $request->user() ? $request->user()->roles->pluck('name')->toArray() : [],
            'middleware_stack' => $request->route() ? $request->route()->gatherMiddleware() : [],
        ]);

        // Vérifier si la route est exclue
        if ($this->shouldExclude($request)) {
            \Log::info('PaywallMiddleware: Route exclue, passage autorisé');
            return $next($request);
        }

        // Vérifier si c'est une route paywall-config (PRIORITÉ ABSOLUE)
        if ($this->isPaywallConfigRoute($request)) {
            \Log::info('PaywallMiddleware: Route paywall-config détectée');
            // IMPORTANT: Les codes d'urgence ne fonctionnent PAS pour les routes paywall-config
            // Seuls les utilisateurs avec permissions service technique peuvent accéder
            if ($this->hasServiceTechniquePermissions($request)) {
                \Log::info('PaywallMiddleware: Permissions service technique OK, accès autorisé');
                return $next($request);
            } else {
                \Log::info('PaywallMiddleware: Permissions service technique manquantes, accès refusé');
                // Nettoyer tout accès d'urgence en session pour ces routes
                session()->forget('emergency_access');
                // Rediriger vers la page de blocage avec un message d'accès refusé
                return redirect()->route('esbtp.paywall-config.blocked')
                    ->with('error', 'Accès refusé : Cette section est réservée au Service Technique d\'African Digit Consulting')
                    ->with('paywall_blocked', true);
            }
        }

        // Vérifier le code d'urgence dans la session ou en paramètre
        if ($this->hasEmergencyAccess($request)) {
            return $next($request);
        }

        // Vérifier si le paywall est actif
        $isPaywallActive = ESBTPSystemSetting::getValue('paywall_active', false);

        if (!$isPaywallActive) {
            return $next($request);
        }

        // Vérifier le statut du paywall (via API Master ou fallback local)
        $status = $this->checkPaywallStatus();

        if ($status['is_blocked']) {
            // Si c'est une requête AJAX, retourner du JSON
            if ($request->expectsJson() || $request->ajax() || str_contains($request->path(), 'ajax')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès bloqué par le paywall',
                    'reasons' => $status['reasons'],
                    'redirect' => route('esbtp.paywall-config.index')
                ], 402); // 402 Payment Required
            }

            // Rediriger vers la page d'upgrade pour les établissements
            return redirect()->route('esbtp.paywall-config.upgrade')
                ->with('error', 'Accès bloqué : ' . implode(', ', $status['reasons']))
                ->with('paywall_blocked', true);
        }

        // Ajouter les avertissements dans la session si il y en a
        if (count($status['warnings']) > 0) {
            session()->flash('paywall_warnings', $status['warnings']);
        }

        return $next($request);
    }

    /**
     * Vérifier si la route doit être exclue
     */
    protected function shouldExclude(Request $request)
    {
        $currentRoute = $request->route()->getName();

        foreach ($this->excludedRoutes as $pattern) {
            if (fnmatch($pattern, $currentRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifier si l'utilisateur a un accès d'urgence
     */
    protected function hasEmergencyAccess(Request $request)
    {
        // Vérifier si c'est un code d'urgence généré dynamiquement
        $providedCode = $request->get('emergency_code');
        if ($providedCode && str_starts_with($providedCode, 'EMERGENCY')) {
            $codeData = ESBTPSystemSetting::getValue('emergency_code_' . $providedCode, null);

            if ($codeData) {
                $codeInfo = json_decode($codeData, true);

                // Vérifier si le code est valide et non expiré
                if ($codeInfo &&
                    !$codeInfo['used'] &&
                    time() <= $codeInfo['expires_at']) {

                    // Marquer le code comme utilisé
                    $codeInfo['used'] = true;
                    $codeInfo['used_at'] = time();
                    $codeInfo['used_by_ip'] = $request->ip();
                    ESBTPSystemSetting::setValue('emergency_code_' . $providedCode, json_encode($codeInfo));

                    // Log de sécurité
                    \Log::warning('Code d\'urgence utilisé', [
                        'code' => $providedCode,
                        'created_by' => $codeInfo['created_by'],
                        'used_by_ip' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]);

                    // Stocker en session pour 1 heure
                    session(['emergency_access' => time() + 3600]);
                    return true;
                }
            }
        }

        // Vérifier si l'accès d'urgence est en session et encore valide
        $emergencyAccess = session('emergency_access');
        if ($emergencyAccess && $emergencyAccess > time()) {
            return true;
        }

        // Nettoyer la session si expirée
        if ($emergencyAccess && $emergencyAccess <= time()) {
            session()->forget('emergency_access');
        }

        return false;
    }

    /**
     * Vérifier le statut du paywall
     * Nouvelle version : Appelle l'API Master avec cache + fallback local
     */
    protected function checkPaywallStatus()
    {
        // Essayer d'obtenir les limites depuis l'API Master (avec cache 5 min)
        $limitsFromMaster = $this->getLimitsFromMaster();

        if ($limitsFromMaster) {
            \Log::info('PaywallMiddleware: Utilisation des limites depuis API Master');
            return $this->buildStatusFromMasterApi($limitsFromMaster);
        }

        // Fallback : Utiliser le système local
        \Log::warning('PaywallMiddleware: API Master indisponible, fallback vers système local');
        return $this->checkPaywallStatusLocal();
    }

    /**
     * Récupérer les limites depuis l'API Master (avec cache 5min)
     */
    protected function getLimitsFromMaster()
    {
        // Vérifier si l'API Master est configurée
        $masterApiUrl = config('services.master.api_url');
        $masterApiToken = config('services.master.api_token');
        $tenantCode = config('app.tenant_code');

        if (!$masterApiUrl || !$masterApiToken || !$tenantCode) {
            \Log::warning('PaywallMiddleware: Configuration API Master manquante', [
                'has_url' => !empty($masterApiUrl),
                'has_token' => !empty($masterApiToken),
                'has_code' => !empty($tenantCode),
            ]);
            return null;
        }

        // Utiliser le cache pour éviter trop d'appels API (5 minutes)
        $cacheKey = 'paywall_limits_' . $tenantCode;

        return Cache::remember($cacheKey, 300, function () use ($masterApiUrl, $masterApiToken, $tenantCode) {
            try {
                \Log::info('PaywallMiddleware: Appel API Master', [
                    'url' => $masterApiUrl . '/tenants/' . $tenantCode . '/limits',
                ]);

                $response = Http::withToken($masterApiToken)
                    ->timeout(10)
                    ->get($masterApiUrl . '/tenants/' . $tenantCode . '/limits');

                if ($response->successful()) {
                    $data = $response->json();
                    \Log::info('PaywallMiddleware: API Master réponse OK', [
                        'is_over_quota' => $data['quota_status']['is_over_quota'] ?? null,
                        'blocked_features' => $data['blocked_features'] ?? [],
                    ]);
                    return $data;
                }

                \Log::error('PaywallMiddleware: API Master erreur HTTP', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                \Log::error('PaywallMiddleware: Erreur appel API Master', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return null;
            }
        });
    }

    /**
     * Construire le statut depuis la réponse de l'API Master
     */
    protected function buildStatusFromMasterApi($apiData)
    {
        $status = [
            'is_blocked' => false,
            'reasons' => [],
            'warnings' => [],
        ];

        // Vérifier l'expiration de l'abonnement
        if ($apiData['subscription']['is_expired'] ?? false) {
            $status['is_blocked'] = true;
            $endDate = $apiData['subscription']['end_date'] ?? 'date inconnue';
            $status['reasons'][] = 'Abonnement expiré le ' . Carbon::parse($endDate)->format('d/m/Y');
        } elseif (isset($apiData['subscription']['days_remaining'])) {
            $daysRemaining = (int) $apiData['subscription']['days_remaining'];
            if ($daysRemaining <= 7 && $daysRemaining > 0) {
                $status['warnings'][] = 'Abonnement expire dans ' . $daysRemaining . ' jour(s)';
            }
        }

        // Vérifier si le quota est dépassé (is_over_quota)
        if ($apiData['quota_status']['is_over_quota'] ?? false) {
            $status['is_blocked'] = true;

            // Ajouter des raisons détaillées selon les limites dépassées
            if ($apiData['quota_status']['users_over_limit'] ?? false) {
                $current = $apiData['current_usage']['users'] ?? 0;
                $max = $apiData['limits']['max_users'] ?? 0;
                $status['reasons'][] = "Limite d'utilisateurs dépassée ($current/$max)";
            }

            if ($apiData['quota_status']['staff_over_limit'] ?? false) {
                $current = $apiData['current_usage']['staff'] ?? 0;
                $max = $apiData['limits']['max_staff'] ?? 0;
                $status['reasons'][] = "Limite de personnel dépassée ($current/$max)";
            }

            if ($apiData['quota_status']['students_over_limit'] ?? false) {
                $current = $apiData['current_usage']['students'] ?? 0;
                $max = $apiData['limits']['max_students'] ?? 0;
                $status['reasons'][] = "Limite d'étudiants dépassée ($current/$max)";
            }

            if ($apiData['quota_status']['inscriptions_over_limit'] ?? false) {
                $current = $apiData['current_usage']['inscriptions_per_year'] ?? 0;
                $max = $apiData['limits']['max_inscriptions_per_year'] ?? 0;
                $status['reasons'][] = "Limite d'inscriptions pour l'année dépassée ($current/$max)";
            }

            if ($apiData['quota_status']['storage_over_limit'] ?? false) {
                $current = $apiData['current_usage']['storage_mb'] ?? 0;
                $max = $apiData['limits']['max_storage_mb'] ?? 0;
                $status['reasons'][] = "Limite de stockage dépassée ($current/$max Mo)";
            }
        }

        // Ajouter des avertissements si proche des limites (>= 90%)
        foreach (['users', 'staff', 'students', 'inscriptions', 'storage'] as $type) {
            $usagePercent = $apiData['usage_percentage'][$type] ?? 0;
            if ($usagePercent >= 90 && $usagePercent < 100) {
                $limitKey = $type === 'inscriptions' ? 'max_inscriptions_per_year' : 'max_' . $type;
                $usageKey = $type === 'inscriptions' ? 'inscriptions_per_year' : $type;

                $current = $apiData['current_usage'][$usageKey] ?? 0;
                $max = $apiData['limits'][$limitKey] ?? 0;

                $status['warnings'][] = "Proche de la limite de $type ($current/$max - {$usagePercent}%)";
            }
        }

        return $status;
    }

    /**
     * Vérifier le statut du paywall (ancien système local - fallback)
     */
    protected function checkPaywallStatusLocal()
    {
        $status = [
            'is_blocked' => false,
            'reasons' => [],
            'warnings' => [],
        ];

        // Récupérer la configuration
        $config = [
            'subscription_end' => ESBTPSystemSetting::getValue('subscription_end_date', null),
            'max_users' => ESBTPSystemSetting::getValue('paywall_max_users', 50),
            'max_inscriptions_per_year' => ESBTPSystemSetting::getValue('paywall_max_inscriptions_per_year', 500),
        ];

        // Obtenir les statistiques actuelles
        $stats = $this->getCurrentStats();

        // Vérifier l'expiration de l'abonnement
        if ($config['subscription_end']) {
            $endDate = Carbon::parse($config['subscription_end']);
            $now = Carbon::now();

            if ($now->gt($endDate)) {
                $status['is_blocked'] = true;
                $status['reasons'][] = 'Abonnement expiré le ' . $endDate->format('d/m/Y');
            } else {
                $daysRemaining = $now->diffInDays($endDate);
                if ($daysRemaining <= 7) {
                    $status['warnings'][] = 'Abonnement expire dans ' . $daysRemaining . ' jour(s)';
                }
            }
        }

        // Vérifier les limites d'utilisateurs
        if ($stats['total_users'] > $config['max_users']) {
            $status['is_blocked'] = true;
            $status['reasons'][] = 'Limite d\'utilisateurs dépassée (' . $stats['total_users'] . '/' . $config['max_users'] . ')';
        } elseif ($stats['total_users'] >= $config['max_users'] * 0.9) {
            $status['warnings'][] = 'Proche de la limite d\'utilisateurs (' . $stats['total_users'] . '/' . $config['max_users'] . ')';
        }

        // Vérifier les limites d'inscriptions par année
        if ($stats['total_inscriptions_current_year'] > $config['max_inscriptions_per_year']) {
            $status['is_blocked'] = true;
            $status['reasons'][] = 'Limite d\'inscriptions dépassée pour l\'année (' . $stats['total_inscriptions_current_year'] . '/' . $config['max_inscriptions_per_year'] . ')';
        } elseif ($stats['total_inscriptions_current_year'] >= $config['max_inscriptions_per_year'] * 0.9) {
            $status['warnings'][] = 'Proche de la limite d\'inscriptions pour l\'année (' . $stats['total_inscriptions_current_year'] . '/' . $config['max_inscriptions_per_year'] . ')';
        }

        return $status;
    }

    /**
     * Obtenir les statistiques actuelles
     */
    protected function getCurrentStats()
    {
        // Compter les utilisateurs (enseignants, coordinateurs, secrétaires)
        $totalUsers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['enseignant', 'coordinateur', 'secretaire']);
        })->count();

        // Compter les inscriptions de l'année universitaire courante
        $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', 1)->first();
        $totalInscriptionsAnnee = 0;

        if ($anneeCourante) {
            $totalInscriptionsAnnee = \App\Models\ESBTPInscription::where('annee_universitaire_id', $anneeCourante->id)
                ->where('status', 'active')
                ->count();
        }

        return [
            'total_users' => $totalUsers,
            'total_inscriptions_current_year' => $totalInscriptionsAnnee,
        ];
    }

    /**
     * Vérifier si la route actuelle est une route paywall-config
     */
    protected function isPaywallConfigRoute(Request $request)
    {
        $currentRoute = $request->route()->getName();
        return str_starts_with($currentRoute, 'esbtp.paywall-config.');
    }

    /**
     * Vérifier si l'utilisateur a les permissions du service technique
     */
    protected function hasServiceTechniquePermissions(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        // ACCÈS RÉSERVÉ EXCLUSIVEMENT AU SERVICE TECHNIQUE D'AFRICAN DIGIT CONSULTING
        // Seul le rôle serviceTechnique est autorisé, pas les superAdmin
        return $user->hasRole('serviceTechnique');
    }
}
