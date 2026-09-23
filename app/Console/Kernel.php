<?php

namespace App\Console;

use App\Console\Commands\MarkTeacherAbsences;
use App\Console\Commands\MarkUnattendedTeacherSessions;
use App\Console\Commands\QueueMonitorCommand;
use App\Console\Commands\RecalculatePersonnelScoresCommand;
use App\Console\Commands\RunQueueWorker;
use App\Console\Commands\SendInscriptionPaiementReminders;
use App\Jobs\CalculerKPIsJob;
use App\Jobs\ComputeAnalyticsPredictionsJob;
use App\Jobs\DetectAnalyticsAnomaliesJob;
use App\Jobs\EvaluateAnalyticsAccuracyJob;
use App\Jobs\PlanifierRelancesJob;
use App\Jobs\SauvegardeDataJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // PR6 Réconciliation : check overdue sessions daily à 8h (heure du matin = pertinent)
        $schedule->command('reconciliation:check-overdue')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Tâches existantes
        $schedule->command('attendance:mark-unattended-teacher-sessions')->everyTenMinutes();

        // KLASSCI Care : signalements que le Master n'a pas pu recevoir.
        $schedule->command('support:vider-boite-envoi')->everyMinute()->withoutOverlapping();

        // Restes des exports groupés abandonnés (dossiers de tranches, PDF
        // assemblés jamais récupérés). Sans ça, rien ne les reprenait.
        $schedule->command('bulletins:purger-exports')->hourly();

        // Statistiques de la page d'audit. Le calcul (sept COUNT sur `audits`)
        // rendait la page inatteignable sur les grosses instances ; il vit
        // desormais ici et la page ne fait plus qu'une lecture.
        //
        // On passe toutes les quinze minutes mais la commande se limite d'elle
        // meme au reglage `audit.stats.frequence_minutes` (60 par defaut) : ce
        // tic frequent ne sert qu'a permettre aux instances qui veulent des
        // compteurs plus frais de le regler sans toucher au code.
        $schedule->command('audit:rafraichir-statistiques')
            ->everyFifteenMinutes()
            ->name('statistiques-audit')
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();

        // Retention legale des proces-verbaux de deliberation. Le reglage
        // lmd_pv_retention_years annoncait une duree que rien ne mesurait : ce
        // recensement la mesure. Il ne PURGE PAS — pas de --purger ici : il liste,
        // et l'archivage reste une decision humaine.
        $schedule->command('lmd:pv-retention')
            ->weeklyOn(1, '05:00')
            ->name('recensement-retention-pv-deliberation')
            ->withoutOverlapping()
            ->runInBackground();

        // Marquage automatique des absences enseignants (toutes les 15 minutes)
        $schedule->command('teacher:mark-absences')
            ->everyFifteenMinutes()
            ->name('marquage-absences-enseignants')
            ->description('Marque automatiquement les enseignants absents après expiration de la fenêtre de 45min')
            ->onOneServer();

        // =====================================================================
        // NOUVELLES TÂCHES ASYNCHRONES - TASK #7
        // =====================================================================

        // Calcul des KPIs quotidiens (23h00 chaque jour)
        $schedule->job(new CalculerKPIsJob('journalier'))
            ->dailyAt('23:00')
            ->name('kpis-quotidiens')
            ->description('Calcul automatique des KPIs quotidiens')
            ->onOneServer(); // Éviter la duplication si plusieurs serveurs

        // Calcul des KPIs hebdomadaires (lundi 01h00)
        $schedule->job(new CalculerKPIsJob('hebdomadaire'))
            ->weekly()
            ->mondays()
            ->at('01:00')
            ->name('kpis-hebdomadaires')
            ->description('Calcul automatique des KPIs hebdomadaires')
            ->onOneServer();

        // Calcul des KPIs mensuels (1er du mois à 02h00)
        $schedule->job(new CalculerKPIsJob('mensuel'))
            ->monthlyOn(1, '02:00')
            ->name('kpis-mensuels')
            ->description('Calcul automatique des KPIs mensuels')
            ->onOneServer();

        // Sauvegarde complète quotidienne (03h00 chaque jour)
        $schedule->job(new SauvegardeDataJob('complet', [
            'inclure_fichiers' => true,
            'compression' => true,
            'retention_jours' => 30,
        ]))
            ->dailyAt('03:00')
            ->name('sauvegarde-quotidienne')
            ->description('Sauvegarde complète quotidienne avec compression')
            ->onOneServer();

        // Sauvegarde base de données uniquement (toutes les 6 heures)
        $schedule->job(new SauvegardeDataJob('database', [
            'inclure_fichiers' => false,
            'compression' => true,
            'retention_jours' => 7,
        ]))
            ->everySixHours()
            ->name('sauvegarde-database')
            ->description('Sauvegarde rapide de la base de données')
            ->onOneServer();

        // Planification automatique des relances (08h00 chaque jour)
        $schedule->job(new PlanifierRelancesJob([
            'segmentation' => 'auto',
            'niveau_max' => 3,
            'types_relance' => ['email'],
            'intervalle_jours' => 7,
        ]))
            ->dailyAt('08:00')
            ->name('planification-relances')
            ->description('Planification automatique des relances de paiement')
            ->onOneServer();

        // Planification des relances urgentes (14h00 chaque jour)
        $schedule->job(new PlanifierRelancesJob([
            'segmentation' => 'niveau_retard',
            'niveau_max' => 5,
            'types_relance' => ['email', 'sms'],
            'seuil_urgence' => 60, // Plus de 60 jours de retard
        ]))
            ->dailyAt('14:00')
            ->name('planification-relances-urgentes')
            ->description('Planification des relances urgentes')
            ->onOneServer();

        // =====================================================================
        // RAPPELS AUTOMATIQUES INSCRIPTIONS/PAIEMENTS
        // =====================================================================

        // Envoi des rappels pour inscriptions et paiements en attente (08h00 chaque jour)
        $schedule->command('reminders:send-inscription-paiement')
            ->dailyAt('08:00')
            ->name('rappels-inscriptions-paiements')
            ->description('Envoi automatique des rappels pour inscriptions et paiements en attente')
            ->onOneServer();

        $schedule->command('mailpulse:reconcile-parent-notifications --limit=50')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->name('mailpulse-reconcile-parent-notifications')
            ->description('Rejoue les notifications parents MailPulse en attente de reconciliation');

        // Convocations de rendez-vous en attente. Remplace l'envoi en lot dans
        // `terminating`, qui mourait avec le processus. N'envoie que l'etat
        // « en attente » : les reservations d'avant le suivi attendent un geste
        // de l'ecole.
        $schedule->command('inscriptions:envoyer-convocations-rdv --max=50 --budget=45')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->name('rdv-convocations-en-attente')
            ->description('Envoie les convocations de rendez-vous en attente');

        $schedule->command('mailpulse:reconcile-parent-link-codes --limit=50')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer()
            ->name('mailpulse-reconcile-parent-link-codes')
            ->description('Rejoue les codes de liaison parents MailPulse en attente de reconciliation');

        $schedule->command('mailpulse:process-parent-chatbot-onboarding --limit=25')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer()
            ->name('mailpulse-process-parent-chatbot-onboarding')
            ->description('Traite les activations massives du chatbot parent MailPulse');

        $schedule->command('mailpulse:prune-parent-chatbot-inbound-responses --limit=1000')
            ->dailyAt('03:30')
            ->withoutOverlapping()
            ->onOneServer()
            ->name('mailpulse-prune-parent-chatbot-inbound-responses')
            ->description('Supprime les reponses chiffrees expirees du chatbot parent MailPulse');

        // Calcul des KPIs temps réel (toutes les heures)
        $schedule->job(new CalculerKPIsJob('horaire'))
            ->hourly()
            ->name('kpis-temps-reel')
            ->description('Mise à jour des indicateurs temps réel')
            ->onOneServer();

        // Nettoyage des logs et fichiers temporaires (chaque dimanche à 04h00)
        $schedule->command('queue:prune-batches --hours=168') // 7 jours
            ->weekly()
            ->sundays()
            ->at('04:00')
            ->name('nettoyage-batches')
            ->description('Nettoyage des anciens batches de jobs');

        $schedule->command('queue:prune-failed --hours=168') // 7 jours
            ->weekly()
            ->sundays()
            ->at('04:15')
            ->name('nettoyage-failed-jobs')
            ->description('Nettoyage des jobs échoués anciens');

        // Surveillance de la santé du système (toutes les 15 minutes)
        $schedule->call(function () {
            \Log::info('Système opérationnel - Vérification automatique', [
                'timestamp' => now(),
                'queue_size' => \DB::table('jobs')->count(),
                'failed_jobs' => \DB::table('failed_jobs')->count(),
                'memory_usage' => memory_get_usage(true),
                'disk_space' => disk_free_space(storage_path()),
            ]);
        })
            ->everyFifteenMinutes()
            ->name('surveillance-systeme')
            ->description('Surveillance de la santé du système');

        // Redémarrage automatique des workers (toutes les 6 heures)
        $schedule->command('queue:restart')
            ->everySixHours()
            ->name('restart-workers')
            ->description('Redémarrage préventif des workers de queue');

        // Optimisation de la base de données (chaque dimanche à 05h00)
        $schedule->call(function () {
            // Optimiser les tables principales
            \DB::statement('OPTIMIZE TABLE jobs, failed_jobs, esbtp_paiements, esbtp_relances');
            \Log::info('Optimisation base de données terminée');
        })
            ->weekly()
            ->sundays()
            ->at('05:00')
            ->name('optimisation-database')
            ->description('Optimisation hebdomadaire de la base de données');

        // =====================================================================
        // ANALYTICS — Phase 4 (PR feat/analytics-default-risk-anomaly)
        // =====================================================================

        // Calcul quotidien des prédictions analytics (4h au fuseau de l'instance)
        $schedule->job(new ComputeAnalyticsPredictionsJob)
            ->dailyAt('04:00')
            ->name('analytics-predictions-daily')
            ->description('Calcul quotidien cash flow + default risk + persistence + cache warm-up')
            ->onOneServer();

        // Détection d'anomalies financières (toutes les 6 heures)
        $schedule->job(new DetectAnalyticsAnomaliesJob)
            ->everySixHours()
            ->name('analytics-anomaly-detection')
            ->description('Détection anomalies revenue + paiements outliers + notification admin/comptables')
            ->onOneServer();

        // Évaluation rétrospective de la précision des prédictions (1er du mois 5h)
        $schedule->job(new EvaluateAnalyticsAccuracyJob)
            ->monthlyOn(1, '05:00')
            ->name('analytics-accuracy-evaluation')
            ->description('Comparaison predicted vs actual du mois écoulé + update accuracy_score')
            ->onOneServer();

        $schedule->command('academic-pilotage:refresh-snapshots --limit=100')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->name('academic-pilotage-refresh-snapshots')
            ->description('Rafraichit les snapshots academiques invalides');

        $schedule->command('academic-pilotage:refresh-alerts --chunk-size=100')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->name('academic-pilotage-refresh-alerts')
            ->description('Rafraichit les alertes academiques idempotentes');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CleanupInstallation::class,
        Commands\ActivateAllTimetables::class,
        Commands\FixTimetablesCommand::class,
        Commands\SyncStudentEmailsCommand::class,
        Commands\CreateTestUsersCommand::class,
        MarkUnattendedTeacherSessions::class,
        RunQueueWorker::class,
        QueueMonitorCommand::class,
        SendInscriptionPaiementReminders::class,
        MarkTeacherAbsences::class,
        RecalculatePersonnelScoresCommand::class,
    ];
}
