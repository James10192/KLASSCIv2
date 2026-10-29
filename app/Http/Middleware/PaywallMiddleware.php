<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ESBTPSystemSetting;

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
        // Vérifier si la route est exclue
        if ($this->shouldExclude($request)) {
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
     * Le statut de blocage : la fiche adminKlassci d'abord, les reglages
     * locaux en secours. Le calcul vit dans AbonnementDeLInstance, que lit
     * aussi l'ecran du service technique — ce que l'ecran annonce est ce que
     * ce middleware applique.
     */
    protected function checkPaywallStatus()
    {
        return app(\App\Services\Master\AbonnementDeLInstance::class)->statutDeBlocage();
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
