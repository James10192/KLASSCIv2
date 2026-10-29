<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ContractExpiryMiddleware
{
    /**
     * Nombre de jours avant expiration à partir duquel afficher l'alerte
     */
    const WARNING_THRESHOLD_DAYS = 30;

    /**
     * Délai minimum entre deux affichages du modal (en secondes = 12h)
     */
    const DISMISS_COOLDOWN_SECONDS = 43200; // 12 * 60 * 60

    /**
     * Routes exclues de la vérification
     */
    protected array $excludedRoutes = [
        'login',
        'logout',
        'register',
        'password.*',
        'contract-expiry.*',
        'esbtp.paywall-config.*',
    ];

    public function handle(Request $request, Closure $next)
    {
        // Ne pas vérifier si pas authentifié
        if (! auth()->check()) {
            return $next($request);
        }

        // Ne pas vérifier sur les routes exclues
        if ($this->shouldExclude($request)) {
            return $next($request);
        }

        // Ne pas vérifier sur les requêtes AJAX / JSON
        if ($request->expectsJson() || $request->ajax()) {
            return $next($request);
        }

        // Récupérer les données d'expiration (avec cache 5 min, partagé avec PaywallMiddleware)
        $expiryData = $this->getExpiryData();

        if ($expiryData && $expiryData['show_warning']) {
            // Stocker les données dans la session pour la vue
            session(['contract_expiry' => $expiryData]);

            // Vérifier si on doit afficher le modal (cooldown 12h)
            $lastShown = session('contract_expiry_last_shown', 0);
            $shouldShow = (time() - $lastShown) >= self::DISMISS_COOLDOWN_SECONDS;

            session(['contract_expiry_should_show' => $shouldShow]);
        } else {
            // Nettoyer si plus dans la zone d'alerte
            session()->forget(['contract_expiry', 'contract_expiry_should_show']);
        }

        return $next($request);
    }

    /**
     * Récupérer les données d'expiration depuis l'API Master (cache partagé)
     */
    protected function getExpiryData(): ?array
    {
        // Même lecture que le paywall : réponse gardée 5 min, échec 1 min.
        $apiData = app(\App\Services\Master\LimitesDuMaster::class)->lire();

        if (! $apiData || ! isset($apiData['subscription'])) {
            return null;
        }

        $subscription = $apiData['subscription'];
        $daysRemaining = isset($subscription['days_remaining'])
            ? (int) $subscription['days_remaining']
            : null;

        if ($daysRemaining === null) {
            return null;
        }

        $isExpired = $subscription['is_expired'] ?? false;
        $endDate = $subscription['end_date'] ?? null;
        $showWarning = $isExpired || $daysRemaining <= self::WARNING_THRESHOLD_DAYS;

        if (! $showWarning) {
            return null;
        }

        // Déterminer le niveau d'urgence
        $urgency = 'green'; // 16-30 jours
        if ($isExpired || $daysRemaining <= 0) {
            $urgency = 'expired';
        } elseif ($daysRemaining <= 7) {
            $urgency = 'red';
        } elseif ($daysRemaining <= 14) {
            $urgency = 'orange';
        }

        return [
            'show_warning'   => true,
            'days_remaining' => $daysRemaining,
            'end_date'       => $endDate,
            'end_date_formatted' => $endDate ? Carbon::parse($endDate)->translatedFormat('d F Y') : null,
            'is_expired'     => $isExpired,
            'urgency'        => $urgency,
            'plan'           => $apiData['plan'] ?? 'Inconnu',
            'tenant_name'    => $apiData['tenant_name'] ?? config('app.name'),
        ];
    }

    /**
     * Vérifier si la route est exclue
     */
    protected function shouldExclude(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return false;
        }

        foreach ($this->excludedRoutes as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }
}
