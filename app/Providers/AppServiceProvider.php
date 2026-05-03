<?php

namespace App\Providers;

use App\Models\ESBTPNote;
use App\Observers\ESBTPNoteObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Charger explicitement le fichier d'aide helpers.php
        if (file_exists(app_path('Helpers/helpers.php'))) {
            require_once app_path('Helpers/helpers.php');
        }
    }


    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Fix for key length issue with MySQL < 5.7.7 or MariaDB < 10.2.2
        Schema::defaultStringLength(191);

        \App\Services\SsoSecretValidator::validate();

        // Observers
        ESBTPNote::observe(ESBTPNoteObserver::class);

        // Use Bootstrap for pagination
        Paginator::useBootstrap();
        Paginator::defaultView('pagination::bootstrap-4');

        // Force URLs to use the correct base path
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        } else {
            // Pour le développement local
            $rootUrl = request()->getSchemeAndHttpHost();

            // Vérifier si nous sommes sur le serveur de développement Laravel (port 8000)
            $isArtisanServe = (request()->getPort() == 8000);

            if (!$isArtisanServe) {
                // Si nous sommes sur Apache/WAMP, forcer l'URL de base pour le sous-dossier
                URL::forceRootUrl($rootUrl . 'public');
            }

            URL::forceScheme('http');
        }
    }
}
