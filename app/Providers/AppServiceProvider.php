<?php

namespace App\Providers;

use App\Domain\BtsTroncCommun\BtsPhaseResolver;
use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Domain\BtsTroncCommun\ClasseOuvertureResolver;
use App\Domain\AcademicPilotage\Contracts\AcademicSystemMetricsProvider;
use App\Domain\AcademicPilotage\Services\AcademicMetricsProviderResolver;
use App\Domain\AcademicPilotage\Services\OpenAlertMetricService;
use App\Domain\Notifications\PhoneNormalizer;
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
use App\View\Composers\CouleursDesCourrielsParents;
use App\View\Composers\MobileShellComposer;
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

        // Une seule instance par requete : la generation groupee precharge les
        // dispenses de toute la classe une fois, puis chaque bulletin lit en
        // memoire. Deux instances distinctes rendraient ce prechargement
        // inutile — et surtout, le service qui accorde une dispense ne saurait
        // pas invalider celle que lit le bulletin.
        // scoped() et non singleton() : un worker de file vit des heures, et
        // forgetScopedInstances() borne le memo entre deux taches.
        $this->app->scoped(\App\Domain\Dispenses\DispenseLookup::class);
        $this->app->scoped(OpenAlertMetricService::class);
        // Le vocabulaire de la structure LMD est lu par des dizaines de libelles
        // dans une meme page : une instance par requete.
        $this->app->scoped(\App\Services\LMD\VocabulaireStructure::class);
        // KLASSCI Care : le layout le consulte a plusieurs endroits d'une meme page.
        $this->app->scoped(\App\Domain\Support\Services\DisponibiliteSupport::class);
        // Bornes de la journee de cours, lues par des grilles qui bouclent heure
        // par heure et par enseignant : une lecture des reglages par requete.
        $this->app->scoped(\App\Services\Planning\PlageHoraireJournee::class);

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
     * Branche la lecture des réglages téléphoniques de l'instance.
     *
     * `PhoneNormalizer` est du calcul pur — il s'exécute sans application, et
     * son test aussi — donc il ne peut pas lire un réglage lui-même. On lui
     * branche une fermeture, qu'il n'évalue qu'au premier numéro analysé : une
     * commande qui ne touche pas au téléphone ne paie aucune lecture.
     *
     * Réglages absents : l'indicatif `225` et les préfixes ivoiriens, soit
     * exactement ce que faisaient les constantes en dur. Les six instances
     * ivoiriennes ne bougent pas.
     */
    private function brancherReglagesTelephone(): void
    {
        PhoneNormalizer::definirResolveurReglages(static function (string $cle): ?string {
            try {
                return SettingsHelper::get($cle, null);
            } catch (\Throwable $e) {
                // Base injoignable ou pas encore migrée (installation, test qui
                // boote l'application sans schéma).
                //
                // Le repli n'est neutre QUE si l'instance a laissé l'indicatif
                // par défaut. Sur une instance qui l'a changé — c'est-à-dire
                // celle pour qui tout ceci existe — il réapposerait `+225` à un
                // numéro qui n'est pas ivoirien, et l'écrirait sous l'index
                // UNIQUE d'`esbtp_candidatures`. C'est une dégradation, donc
                // elle se journalise.
                //
                // Une ligne par processus, pas par appel : sans mémoïsation
                // dans le normaliseur (voir son commentaire), ce rattrapage se
                // déclenche à chaque numéro analysé, et journaliser à chaque
                // fois noierait le journal au lieu de le renseigner.
                static $signale = false;

                if (! $signale) {
                    $signale = true;
                    Log::warning('Réglages téléphoniques illisibles : indicatif par défaut appliqué.', [
                        'cle' => $cle,
                        'erreur' => $e->getMessage(),
                    ]);
                }

                return null;
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix for key length issue with MySQL < 5.7.7 or MariaDB < 10.2.2
        Schema::defaultStringLength(191);

        SsoSecretValidator::validate();

        $this->brancherReglagesTelephone();

        $this->partagerCompteurDemandesReinscription();

        // Shell mobile : $mobileShellEnabled et $mobileProfile dans toutes les
        // vues. Sur '*' a dessein — le layout, ses partials et les feuilles
        // mobiles en ont tous besoin, et le resolver est memoise par requete.
        View::composer('*', MobileShellComposer::class);

        // Couleurs des courriels aux parents. Elles étaient résolues dans le
        // `@php` du gabarit, donc APRÈS l'évaluation des `@section` de ses
        // enfants : l'avis de paiement validé échouait sur
        // `Undefined variable $emailPrimaryColor`, dans un `try` muet.
        View::composer('esbtp.emails.parents.*', CouleursDesCourrielsParents::class);

        // Nom des rangs de la structure LMD, regle par etablissement (Domaine /
        // Mention / Parcours, ou Composante / Departement / Specialite).
        // @rang('mention') → « Mention » ; @rangs('mention') → « Mentions ».
        \Illuminate\Support\Facades\Blade::directive('rang', fn (string $cle) =>
            "<?php echo e(app(\\App\\Services\\LMD\\VocabulaireStructure::class)->rang({$cle})); ?>");
        \Illuminate\Support\Facades\Blade::directive('rangs', fn (string $cle) =>
            "<?php echo e(app(\\App\\Services\\LMD\\VocabulaireStructure::class)->rangs({$cle})); ?>");
        \Illuminate\Support\Facades\Blade::directive('natureDe', fn (string $domaine) =>
            "<?php echo e(app(\\App\\Services\\LMD\\VocabulaireStructure::class)->natureDe({$domaine})); ?>");

        // Observers
        ESBTPNote::observe(ESBTPNoteObserver::class);

        $this->brancherLesObservateursDePilotage();

        // Use Bootstrap for pagination
        Paginator::useBootstrap();
        Paginator::defaultView('pagination::bootstrap-4');

        $this->forcerLesUrlsDeBase();
    }

    /**
     * Les observateurs du pilotage académique, et le réglage qui les coupe.
     *
     * Un encaissement validé se réimpute sur des mois déjà clos (allocation
     * FIFO) : les balayages analytiques mémorisés doivent être déréférencés.
     * PAS d'invalidation a chaque paiement valide. Elle semblait prudente et
     * elle vidait la fonction de son objet : sur une ecole guichet ouvert, la
     * memoire aurait ete purgee en continu, et la page serait restee a 24 ou 34
     * secondes PRECISEMENT pendant les heures d encaissement — c est-a-dire la
     * fenetre ou ces 24 a 34 secondes ont ete mesurees.
     *
     * Ce n est pas grave, et c est la raison de fond : l ecart de recouvrement ne
     * porte QUE sur des mois CLOS. Un encaissement du jour ne le deplace que par
     * reallocation FIFO, lentement. La duree de memorisation, reglable par ecole,
     * suffit — a condition d afficher la fraicheur, ce que l ecran fait.
     *
     * Le réglage n'est PAS honoré en production : le couper y rendrait les
     * indicateurs faux en silence. On le journalise en `critical` et on rebranche.
     */
    private function brancherLesObservateursDePilotage(): void
    {
        $actifs = (bool) config('academic_pilotage.observers_enabled', true);

        if (! $actifs && app()->environment('production')) {
            Log::critical('Academic pilotage observers cannot be disabled in production.');
            $actifs = true;
        }

        if (! $actifs) {
            return;
        }

        ESBTPNote::observe(ESBTPNoteAcademicPilotageObserver::class);
        ESBTPEvaluation::observe(ESBTPEvaluationAcademicPilotageObserver::class);
        ESBTPAttendance::observe(ESBTPAttendanceAcademicPilotageObserver::class);
        ESBTPLMDBulletin::observe(ESBTPLMDBulletinAcademicPilotageObserver::class);
        ESBTPInscription::observe(ESBTPInscriptionAcademicPilotageObserver::class);
        ESBTPPlanificationAcademique::observe(ESBTPPlanificationAcademicPilotageObserver::class);
    }

    /**
     * Le schéma et la racine des URLs générées.
     *
     * Hors local, tout est en HTTPS. En local, le sous-dossier `public` doit être
     * réintroduit dans la racine quand le service est rendu par Apache/WAMP —
     * mais PAS sous `artisan serve` (port 8000), qui sert déjà depuis `public`.
     */
    private function forcerLesUrlsDeBase(): void
    {
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');

            return;
        }

        if (request()->getPort() != 8000) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost().'public');
        }

        URL::forceScheme('http');
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
