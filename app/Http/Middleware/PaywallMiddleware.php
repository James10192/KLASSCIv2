<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
     * Code d'urgence pour accéder temporairement au système (pas paywall)
     */
    protected $emergencyCode = 'ADMIN2024EMERGENCY';

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

        // Vérifier le code d'urgence dans la session ou en paramètre
        if ($this->hasEmergencyAccess($request)) {
            return $next($request);
        }

        // Vérifier si c'est une route paywall-config
        if ($this->isPaywallConfigRoute($request)) {
            // Seuls les utilisateurs avec permissions service technique peuvent accéder
            if ($this->hasServiceTechniquePermissions($request)) {
                return $next($request);
            } else {
                // Rediriger vers la page de blocage avec un message d'accès refusé
                return redirect()->route('esbtp.paywall-config.blocked')
                    ->with('error', 'Accès refusé : Cette section est réservée au Service Technique d\'African Digit Consulting')
                    ->with('paywall_blocked', true);
            }
        }

        // Vérifier si le paywall est actif
        $isPaywallActive = ESBTPSystemSetting::getValue('paywall_active', false);

        if (!$isPaywallActive) {
            return $next($request);
        }

        // Vérifier le statut du paywall
        $status = $this->checkPaywallStatus();

        if ($status['is_blocked']) {
            // Si c'est une requête AJAX, retourner du JSON
            if ($request->expectsJson()) {
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
        // Vérifier si le code d'urgence est fourni en paramètre GET
        if ($request->get('emergency_code') === $this->emergencyCode) {
            // Stocker en session pour 1 heure
            session(['emergency_access' => time() + 3600]);
            return true;
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
     */
    protected function checkPaywallStatus()
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

        // Vérifier si l'utilisateur a le rôle serviceTechnique OU les permissions spéciales
        return $user->hasRole('serviceTechnique') ||
               $user->can('paywall.configure') ||
               $user->can('system.technical_access');
    }
}
