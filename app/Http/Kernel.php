<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Spatie\Permission\Middlewares\PermissionMiddleware;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        \App\Http\Middleware\CheckInstalled::class,
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\LogRequests::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\LogRequestMiddleware::class,
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\UpdateLastLogin::class,
            \App\Http\Middleware\RouteDebugMiddleware::class,
            \App\Http\Middleware\ContractExpiryMiddleware::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'installed' => \App\Http\Middleware\EnsureInstalled::class,
        'role' => \App\Http\Middleware\CheckRole::class,
        'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
        'comptabilite.access' => \App\Http\Middleware\CheckComptabiliteAccess::class,
        'validate.device' => \App\Http\Middleware\ValidateAttendanceDevice::class,
        'attendance.rate_limit' => \App\Http\Middleware\AttendanceRateLimiter::class,
        'force.password.change' => \App\Http\Middleware\ForcePasswordChange::class,
        'paywall' => \App\Http\Middleware\PaywallMiddleware::class,
        'contract.expiry' => \App\Http\Middleware\ContractExpiryMiddleware::class,
    ];

    /**
     * Define the application's route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiting pour les audits (Task #10 - Sécurité)
        RateLimiter::for('audit', function (Request $request) {
            return [
                // Limite générale pour les pages d'audit
                Limit::perMinute(100)->by($request->user()?->id ?: $request->ip()),
                // Limite plus stricte pour les utilisateurs non authentifiés
                Limit::perMinute(10)->by($request->ip())->when(!$request->user()),
            ];
        });

        // Rate limiting strict pour les opérations de sécurité sensibles
        RateLimiter::for('security', function (Request $request) {
            return [
                // Limite très restrictive pour les opérations de sécurité
                Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()),
                // Limite par IP pour prévenir les attaques
                Limit::perHour(100)->by($request->ip()),
            ];
        });

        // Rate limiting pour les exports (très restrictif)
        RateLimiter::for('exports', function (Request $request) {
            return [
                // Maximum 5 exports par minute par utilisateur
                Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()),
                // Maximum 20 exports par heure par utilisateur
                Limit::perHour(20)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Rate limiting pour les tentatives de connexion
        RateLimiter::for('login', function (Request $request) {
            return [
                // 5 tentatives par minute par email
                Limit::perMinute(5)->by($request->input('email')),
                // 10 tentatives par minute par IP
                Limit::perMinute(10)->by($request->ip()),
            ];
        });

        // Rate limiting pour les opérations financières critiques
        RateLimiter::for('financial', function (Request $request) {
            return [
                // Limite stricte pour les opérations financières
                Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()),
                // Limite par IP pour la sécurité
                Limit::perHour(50)->by($request->ip()),
            ];
        });
    }
}
