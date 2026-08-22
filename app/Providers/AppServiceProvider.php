<?php

namespace App\Providers;

use App\Domain\BtsTroncCommun\BtsPhaseResolver;
use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use App\Domain\BtsTroncCommun\ClasseOuvertureResolver;
use App\Domain\AcademicPilotage\Contracts\AcademicSystemMetricsProvider;
use App\Domain\AcademicPilotage\Services\AcademicMetricsProviderResolver;
use App\Domain\AcademicPilotage\Services\OpenAlertMetricService;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPNote;
use App\Models\ESBTPPlanificationAcademique;
use App\Observers\ESBTPAttendanceAcademicPilotageObserver;
use App\Observers\ESBTPEvaluationAcademicPilotageObserver;
use App\Observers\ESBTPInscriptionAcademicPilotageObserver;
use App\Observers\ESBTPLMDBulletinAcademicPilotageObserver;
use App\Observers\ESBTPNoteAcademicPilotageObserver;
use App\Observers\ESBTPNoteObserver;
use App\Observers\ESBTPPlanificationAcademicPilotageObserver;
use App\Services\Analytics\RecouvrementGapService;
use App\Services\LMD\Tpe\AutoValidateStrategy;
use App\Services\LMD\Tpe\TeacherValidateStrategy;
use App\Services\LMD\Tpe\TpeValidationStrategy;
use App\Services\SsoSecretValidator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Charger explicitement le fichier d'aide helpers.php
        if (file_exists(app_path('Helpers/helpers.php'))) {
            require_once app_path('Helpers/helpers.php');
        }

        // Une seule instance par requête pour qu'AnomalyDetector et le contrôleur
        // analytics partagent le même cache de buckets attendu/encaissé.
        $this->app->scoped(RecouvrementGapService::class);
        $this->app->scoped(OpenAlertMetricService::class);

        // Resolveurs du parcours BTS : une seule instance par requete, sinon
        // leur memoire ne sert a rien. Le compteur de cohorte balaie toutes les
        // inscriptions de l'annee — plus de deux mille sur les grosses ecoles —
        // et l'export groupe d'une classe l'interroge une fois par etudiant.
        $this->app->scoped(BtsPhaseResolver::class);
        $this->app->scoped(BtsClassCohortCounter::class);
        $this->app->scoped(ClasseOuvertureResolver::class);
        $this->app->bind(AcademicSystemMetricsProvider::class, AcademicMetricsProviderResolver::class);

        // TPE — Strategy de validation pilotée par Setting tenant.
        // Setting `tpe.validation.enabled` = false (defaut) → AutoValidateStrategy (Option 2)
        // Setting `tpe.validation.enabled` = true            → TeacherValidateStrategy (Option 3)
        // Opt-in dormant : 100% du code Option 3 est présent mais inactif tant
        // que l'école ne flip pas le toggle via /esbtp/settings.
        $this->app->bind(
            TpeValidationStrategy::class,
            function ($app) {
                $enabled = (bool) SettingsHelper::get('tpe.validation.enabled', false);

                return $enabled
                    ? new TeacherValidateStrategy
                    : new AutoValidateStrategy;
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix for key length issue with MySQL < 5.7.7 or MariaDB < 10.2.2
        Schema::defaultStringLength(191);

        SsoSecretValidator::validate();

        // Observers
        ESBTPNote::observe(ESBTPNoteObserver::class);
        $academicPilotageObserversEnabled = (bool) config('academic_pilotage.observers_enabled', true);
        if (! $academicPilotageObserversEnabled && app()->environment('production')) {
            Log::critical('Academic pilotage observers cannot be disabled in production.');
            $academicPilotageObserversEnabled = true;
        }

        if ($academicPilotageObserversEnabled) {
            ESBTPNote::observe(ESBTPNoteAcademicPilotageObserver::class);
            ESBTPEvaluation::observe(ESBTPEvaluationAcademicPilotageObserver::class);
            ESBTPAttendance::observe(ESBTPAttendanceAcademicPilotageObserver::class);
            ESBTPLMDBulletin::observe(ESBTPLMDBulletinAcademicPilotageObserver::class);
            ESBTPInscription::observe(ESBTPInscriptionAcademicPilotageObserver::class);
            ESBTPPlanificationAcademique::observe(ESBTPPlanificationAcademicPilotageObserver::class);
        }

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

            if (! $isArtisanServe) {
                // Si nous sommes sur Apache/WAMP, forcer l'URL de base pour le sous-dossier
                URL::forceRootUrl($rootUrl.'public');
            }

            URL::forceScheme('http');
        }
    }
}
