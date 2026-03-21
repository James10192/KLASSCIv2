<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        // Vérifier l'état d'installation
        $this->checkInstallation();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));

            // Charger les routes ESBTP
            // Commenté pour éviter les routes dupliquées
            // if (file_exists(base_path('routes/esbtp.php'))) {
            //     Route::middleware('web')
            //         ->namespace($this->namespace)
            //         ->group(base_path('routes/esbtp.php'));
            // }
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        // Rate limiter strict pour les endpoints de découverte LMS (anti-énumération)
        RateLimiter::for('lms-discovery', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }

    /**
     * Vérifie si l'application est installée et redirige vers l'installation si nécessaire
     */
    protected function checkInstallation()
    {
        try {
            // Ne pas rediriger si on est déjà sur la page d'accueil ou les routes d'authentification
            if (request()->is('/') || request()->is('login') || request()->is('register')) {
                return;
            }

            // Vérifier si la base de données est configurée
            if (!config('database.connections.' . config('database.default') . '.database')) {
                $this->redirectToInstall();
                return;
            }

            // Vérifier si le fichier .env existe
            if (!file_exists(base_path('.env'))) {
                $this->redirectToInstall();
                return;
            }

            // Vérifier si la table users existe
            if (!Schema::hasTable('users')) {
                $this->redirectToInstall();
                return;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur lors de la vérification de l\'installation: ' . $e->getMessage());
            $this->redirectToInstall();
        }
    }

    /**
     * Redirige vers la page d'installation
     */
    protected function redirectToInstall()
    {
        if (request()->is('install') || request()->is('install/*')) {
            return;
        }

        // Rediriger vers l'installation sauf pour certains chemins
        if (!request()->is('assets/*') && !request()->is('css/*') && !request()->is('js/*')) {
            header('Location: ' . url('/install'));
            exit;
        }
    }
}
