<?php

namespace App\Providers;

use App\Domain\BtsTroncCommun\BtsPhaseResolver;
use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Domain\BtsTroncCommun\ClasseOuvertureResolver;
use App\Domain\AcademicPilotage\Contracts\AcademicSystemMetricsProvider;
use App\Domain\AcademicPilotage\Services\AcademicMetricsProviderResolver;
use App\Domain\AcademicPilotage\Services\OpenAlertMetricService;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPNote;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPReinscriptionDemande;
use App\Observers\ESBTPAttendanceAcademicPilotageObserver;
use App\Observers\ESBTPEvaluationAcademicPilotageObserver;
use App\Observers\ESBTPInscriptionAcademicPilotageObserver;
use App\Observers\ESBTPLMDBulletinAcademicPilotageObserver;
use App\Observers\ESBTPNoteAcademicPilotageObserver;
use App\Observers\ESBTPNoteObserver;
use App\Observers\ESBTPPlanificationAcademicPilotageObserver;
use App\Services\Analytics\AnalyticsScanCache;
use App\Services\Analytics\CashFlowProjectionService;
use App\Services\Analytics\RecouvrementGapService;
use App\Services\LMD\Tpe\AutoValidateStrategy;
use App\Services\LMD\Tpe\TeacherValidateStrategy;
use App\Services\LMD\Tpe\TpeValidationStrategy;
use App\Services\SsoSecretValidator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton : le service memorise ses resolutions de chemin. Resolu a la
        // volee, le conteneur en reconstruisait une instance neuve a chaque acces
        // a photo_url — donc un memo toujours vide, et une liste de cinquante
        // etudiants payait cinq sondages disque par ligne, deux fois.
        // scoped() et non singleton() : un worker de file d attente vit des heures et
        // traite des milliers de fiches. forgetScopedInstances() est appele entre deux
        // taches, ce qui borne le memo. Un singleton le laisserait grossir sans fin.
        $this->app->scoped(\App\Services\Photos\StockagePhoto::class);

        // Charger explicitement le fichier d'aide helpers.php
        if (file_exists(app_path('Helpers/helpers.php'))) {
            require_once app_path('Helpers/helpers.php');
        }

        // Une seule instance par requête pour qu'AnomalyDetector et le contrôleur
        // analytics partagent le même cache de buckets attendu/encaissé.
        $this->app->scoped(RecouvrementGapService::class);
        // Même raison pour la projection d'encaissement : le prédicteur et
        // l'export la demandent tous les deux dans la même requête.
        $this->app->scoped(CashFlowProjectionService::class);
        $this->app->scoped(AnalyticsScanCache::class);
        $this->app->scoped(OpenAlertMetricService::class);

        // Resolveurs du parcours BTS : une seule instance par requete, sinon
        // leur memoire ne sert a rien. Le compteur de cohorte balaie toutes les
        // inscriptions de l'annee — plus de deux mille sur les grosses ecoles —
        // et l'export groupe d'une classe l'interroge une fois par etudiant.
        $this->app->scoped(BtsPhaseResolver::class);
        $this->app->scoped(BtsClassCohortCounter::class);
        $this->app->scoped(ClasseOuvertureResolver::class);
        // Quatre services injectent la carte annuelle et memoisaient chacun de
        // leur cote le meme triplet : une seule instance par requete suffit.
        $this->app->scoped(BtsAnnualClassMapResolver::class);
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

        $this->partagerCompteurDemandesReinscription();

        // Observers
        ESBTPNote::observe(ESBTPNoteObserver::class);
        // Un encaissement validé se réimpute sur des mois déjà clos (allocation
        // FIFO) : les balayages analytiques mémorisés doivent être déréférencés.
        // PAS d'invalidation a chaque paiement valide. Elle semblait prudente et
        // elle vidait la fonction de son objet : sur une ecole guichet ouvert, la
        // memoire aurait ete purgee en continu, et la page serait restee a 24 ou 34
        // secondes PRECISEMENT pendant les heures d encaissement — c est-a-dire la
        // fenetre ou ces 24 a 34 secondes ont ete mesurees.
        //
        // Ce n est pas grave, et c est la raison de fond : l ecart de recouvrement ne
        // porte QUE sur des mois CLOS. Un encaissement du jour ne le deplace que par
        // reallocation FIFO, lentement. La duree de memorisation, reglable par ecole,
        // suffit — a condition d afficher la fraicheur, ce que l ecran fait.
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

    /**
     * Compteur du badge « Demandes en ligne » de la barre laterale.
     *
     * Le calcul vit ici, et non dans le gabarit, pour deux raisons. D'abord le
     * cout : le gabarit est rendu par CHAQUE page, le compte serait donc paye
     * en permanence par tous les agents de scolarite. Ensuite et surtout le
     * deploiement : la sequence est `pull` puis `migrate`, donc le code
     * precede la table de quelques secondes. Une requete dans le gabarit
     * global ferait tomber l'application ENTIERE pendant cette fenetre.
     */
    private function partagerCompteurDemandesReinscription(): void
    {
        View::composer('layouts.app', function ($view): void {
            $enAttente = 0;

            if (auth()->check() && auth()->user()->can('reinscriptions.demandes.view')) {
                $enAttente = Cache::remember(ESBTPReinscriptionDemande::CLE_CACHE_EN_ATTENTE, 60, function (): int {
                    if (! Schema::hasTable('esbtp_reinscription_demandes')) {
                        return 0;
                    }

                    return ESBTPReinscriptionDemande::enAttente()->count();
                });
            }

            $view->with('reinscriptionDemandesEnAttente', $enAttente);
            $view->with('candidaturesEnAttente', $this->candidaturesEnAttente());
        });
    }

    /**
     * Compteur du badge « Candidatures en ligne ».
     *
     * Meme discipline que pour les demandes de reinscription : cache court, et
     * garde sur l'existence de la table. Le deploiement fait `pull` puis
     * `migrate` — le code precede donc la table de quelques secondes, et une
     * requete non gardee dans le gabarit global ferait tomber l'application
     * ENTIERE pendant cette fenetre.
     */
    private function candidaturesEnAttente(): int
    {
        if (! auth()->check() || ! auth()->user()->can('inscriptions.candidatures.view')) {
            return 0;
        }

        return Cache::remember('inscriptions.candidatures.en_attente', 60, function (): int {
            if (! Schema::hasTable('esbtp_candidatures')) {
                return 0;
            }

            return ESBTPCandidature::enAttente()->count();
        });
    }
}
