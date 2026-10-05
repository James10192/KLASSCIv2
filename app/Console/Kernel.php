<?php

namespace App\Console;

use App\Console\Commands\MarkTeacherAbsences;
use App\Console\Commands\MarkUnattendedTeacherSessions;
use App\Console\Commands\QueueMonitorCommand;
use App\Console\Commands\RunQueueWorker;
use App\Console\Commands\SendInscriptionPaiementReminders;
use App\Jobs\ComputeAnalyticsPredictionsJob;
use App\Jobs\DetectAnalyticsAnomaliesJob;
use App\Jobs\EvaluateAnalyticsAccuracyJob;
use App\Jobs\PlanifierRelancesJob;
use App\Jobs\SauvegardeDataJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(fn () => \App\Domain\Exploitation\PoulsPlanificateur::battre())
            ->everyMinute()
            ->name('pouls-planificateur');

        $schedule->command('reconciliation:check-overdue')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('attendance:mark-unattended-teacher-sessions')->everyTenMinutes();
        $schedule->command('support:vider-boite-envoi')->everyMinute()->withoutOverlapping(10);
        $schedule->command('support:suivre-demandes')->everyFiveMinutes()->withoutOverlapping(10);
        $schedule->command('bulletins:purger-exports')->hourly();
        $schedule->command('traces:purger --jours=30')->dailyAt('03:20');

        $schedule->command('bulletins:traiter-taches --budget=50')
            ->everyMinute()
            ->withoutOverlapping(10)
            ->runInBackground()
            ->name('bulletins-traiter-taches')
            ->description('Fait avancer les générations et PDF groupés de bulletins lancés en arrière-plan');

        $schedule->command('lmd:pv-retention')
            ->weeklyOn(1, '05:00')
            ->name('recensement-retention-pv-deliberation')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('teacher:mark-absences')
            ->everyFifteenMinutes()
            ->name('marquage-absences-enseignants')
            ->description('Marque automatiquement les enseignants absents après expiration de la fenêtre de 45min')
            ->onOneServer();

        $schedule->job(new SauvegardeDataJob('complet', [
            'inclure_fichiers' => true,
            'compression' => true,
            'retention_jours' => 30,
        ]))
            ->dailyAt('03:00')
            ->name('sauvegarde-quotidienne')
            ->description('Sauvegarde complète quotidienne avec compression')
            ->onOneServer();

        $schedule->job(new SauvegardeDataJob('database', [
            'inclure_fichiers' => false,
            'compression' => true,
            'retention_jours' => 7,
        ]))
            ->everySixHours()
            ->name('sauvegarde-database')
            ->description('Sauvegarde rapide de la base de données')
            ->onOneServer();

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

        $schedule->job(new PlanifierRelancesJob([
            'segmentation' => 'niveau_retard',
            'niveau_max' => 5,
            'types_relance' => ['email', 'sms'],
            'seuil_urgence' => 60,
        ]))
            ->dailyAt('14:00')
            ->name('planification-relances-urgentes')
            ->description('Planification des relances urgentes')
            ->onOneServer();

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

        // À 00:00, si l'école a activé la bascule, aujourd'hui devient une
        // journée fermée aux nouvelles réservations. Les réservations existantes
        // restent intactes ; le catalogue et ReservateurRdv portent le même garde.
        $schedule->command('inscriptions:fermer-creneaux-rdv-du-jour')
            ->dailyAt('00:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->name('rdv-fermeture-du-jour-minuit')
            ->description('Ferme les créneaux du jour aux nouvelles réservations à minuit');

        $schedule->command('inscriptions:envoyer-convocations-rdv --max=50 --budget=45')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->name('rdv-convocations-en-attente')
            ->description('Envoie les convocations de rendez-vous en attente');

        $schedule->command('inscriptions:synchroniser-convocations-rdv --max=100')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->name('rdv-convocations-synchro')
            ->description('Relit chez MailPulse la remise reelle des convocations de rendez-vous');

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

        $schedule->command('queue:prune-batches --hours=168')
            ->weekly()
            ->sundays()
            ->at('04:00')
            ->name('nettoyage-batches')
            ->description('Nettoyage des anciens batches de jobs');

        $schedule->command('queue:prune-failed --hours=168')
            ->weekly()
            ->sundays()
            ->at('04:15')
            ->name('nettoyage-failed-jobs')
            ->description('Nettoyage des jobs échoués anciens');

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

        $schedule->command('queue:restart')
            ->everySixHours()
            ->name('restart-workers')
            ->description('Redémarrage préventif des workers de queue');

        $schedule->call(function () {
            \DB::statement('OPTIMIZE TABLE jobs, failed_jobs, esbtp_paiements, esbtp_relances');
            \Log::info('Optimisation base de données terminée');
        })
            ->weekly()
            ->sundays()
            ->at('05:00')
            ->name('optimisation-database')
            ->description('Optimisation hebdomadaire de la base de données');

        $schedule->job(new ComputeAnalyticsPredictionsJob)
            ->dailyAt('04:00')
            ->name('analytics-predictions-daily')
            ->description('Calcul quotidien cash flow + default risk + persistence + cache warm-up')
            ->onOneServer();

        $schedule->job(new DetectAnalyticsAnomaliesJob)
            ->everySixHours()
            ->name('analytics-anomaly-detection')
            ->description('Détection anomalies revenue + paiements outliers + notification admin/comptables')
            ->onOneServer();

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

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }

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
    ];
}
