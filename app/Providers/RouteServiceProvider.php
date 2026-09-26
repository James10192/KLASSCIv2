<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for the application.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    public function register(): void
    {
        parent::register();

        // Nanan keeps the standard prompt everywhere. On /messages, the bound
        // subclass appends a server-built, permission-aware conversation context.
        $this->app->bind(
            \App\Domain\Assistant\Harnais\ConstructeurDePrompt::class,
            \App\Domain\Assistant\Harnais\SafeMessageHubPrompt::class,
        );
    }

    public function boot()
    {
        // Production must never expose Laravel traces even when an environment
        // variable was accidentally left at APP_DEBUG=true.
        if (app()->environment('production') && config('app.debug')) {
            config(['app.debug' => false]);
            Log::critical('APP_DEBUG was enabled in production and has been forced off.');
        }

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

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/message-hub.php'));

            // Charger les routes ESBTP
            // Commenté pour éviter les routes dupliquées
            // if (file_exists(base_path('routes/esbtp.php'))) {
            //     Route::middleware('web')
            //         ->namespace($this->namespace)
            //         ->group(base_path('routes/esbtp.php'));
            // }
        });
    }

    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            $utilisateur = $request->user();

            if (\App\Support\Lms\JetonServeurLms::estServeur($utilisateur)) {
                $parMinute = max(60, (int) \App\Helpers\SettingsHelper::get('lms.serveur.limite_par_minute', 600));

                return Limit::perMinute($parMinute)->by('lms-serveur:'.$utilisateur->currentAccessToken()->id);
            }

            return Limit::perMinute(60)->by(optional($utilisateur)->id ?: $request->ip());
        });

        RateLimiter::for('lms-discovery', function (Request $request) {
            $identifiant = mb_strtolower(trim((string) (
                $request->input('identifier') ?? $request->input('email') ?? $request->input('username') ?? ''
            )));

            return [
                Limit::perMinute(10)->by('lms-decouverte-id:'.sha1($identifiant.'|'.$request->ip())),
                Limit::perMinute(max(10, (int) \App\Helpers\SettingsHelper::get('lms.decouverte.limite_ip_par_minute', 30)))
                    ->by('lms-decouverte-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('audit', function (Request $request) {
            if ($request->user()) {
                return Limit::perMinute(600)->by($request->user()->id);
            }
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('security', function (Request $request) {
            return [
                Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(100)->by($request->ip()),
            ];
        });

        RateLimiter::for('exports', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(20)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        RateLimiter::for('login', function (Request $request) {
            $identifier = strtolower((string) $request->input('username', $request->input('email', '')));
            return [
                Limit::perMinute(5)->by($identifier ?: $request->ip()),
                Limit::perMinute(10)->by($request->ip()),
            ];
        });

        RateLimiter::for('financial', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }

    protected function checkInstallation()
    {
        try {
            if (request()->is('/') || request()->is('login')) {
                return;
            }

            if (!config('database.connections.' . config('database.default') . '.database')) {
                $this->redirectToInstall();
                return;
            }

            if (!file_exists(base_path('.env'))) {
                $this->redirectToInstall();
                return;
            }

            if (!Schema::hasTable('users')) {
                $this->redirectToInstall();
                return;
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification de l\'installation: ' . $e->getMessage());
            $this->redirectToInstall();
        }
    }

    protected function redirectToInstall()
    {
        if (request()->is('install') || request()->is('install/*')) {
            return;
        }

        if (!request()->is('assets/*') && !request()->is('css/*') && !request()->is('js/*')) {
            header('Location: ' . url('/install'));
            exit;
        }
    }
}
