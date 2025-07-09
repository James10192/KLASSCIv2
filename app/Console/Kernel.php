<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\CalculerKPIsJob;
use App\Jobs\SauvegardeDataJob;
use App\Jobs\PlanifierRelancesJob;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Tâches existantes
        $schedule->command('attendance:mark-unattended-teacher-sessions')->everyTenMinutes();

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
            'retention_jours' => 30
        ]))
            ->dailyAt('03:00')
            ->name('sauvegarde-quotidienne')
            ->description('Sauvegarde complète quotidienne avec compression')
            ->onOneServer();

        // Sauvegarde base de données uniquement (toutes les 6 heures)
        $schedule->job(new SauvegardeDataJob('database', [
            'inclure_fichiers' => false,
            'compression' => true,
            'retention_jours' => 7
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
            'intervalle_jours' => 7
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
            'seuil_urgence' => 60 // Plus de 60 jours de retard
        ]))
            ->dailyAt('14:00')
            ->name('planification-relances-urgentes')
            ->description('Planification des relances urgentes')
            ->onOneServer();

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
                'disk_space' => disk_free_space(storage_path())
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
        \App\Console\Commands\MarkUnattendedTeacherSessions::class,
        \App\Console\Commands\RunQueueWorker::class,
        \App\Console\Commands\QueueMonitorCommand::class,
    ];
}
