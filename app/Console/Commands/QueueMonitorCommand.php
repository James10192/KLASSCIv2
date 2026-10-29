<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueueMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:monitor
                            {--interval=30 : Interval de surveillance en secondes}
                            {--show-details : Afficher les détails des jobs}
                            {--auto-restart : Redémarrer automatiquement les workers si nécessaire}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor queue performance and worker status (alternative to Horizon for Windows)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $interval = (int) $this->option('interval');
        $showDetails = $this->option('show-details');
        $autoRestart = $this->option('auto-restart');

        $this->info("=== ESBTP Queue Monitor ===");
        $this->info("Surveillance des files d'attente toutes les {$interval} secondes");
        $this->info("Appuyez sur Ctrl+C pour arrêter...");
        $this->line('');

        while (true) {
            $this->clearScreen();
            $this->displayHeader();
            $this->monitorQueues($showDetails);
            $this->monitorFailedJobs();
            $this->monitorSystemHealth();

            if ($autoRestart) {
                $this->checkAndRestartWorkers();
            }

            $this->line('');
            $this->info("Prochaine mise à jour dans {$interval} secondes...");

            sleep($interval);
        }
    }

    /**
     * Clear the screen for better display
     */
    private function clearScreen()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            system('cls');
        } else {
            system('clear');
        }
    }

    /**
     * Display the header with timestamp
     */
    private function displayHeader()
    {
        $this->info("🔄 ESBTP Queue Monitor - " . now()->format('d/m/Y H:i:s'));
        $this->line(str_repeat('=', 80));
    }

    /**
     * Monitor queue status
     */
    private function monitorQueues($showDetails = false)
    {
        $this->info("📊 État des Files d'Attente");
        $this->line(str_repeat('-', 40));

        $queues = ['default', 'high', 'medium', 'low', 'reports', 'backup'];

        foreach ($queues as $queue) {
            $pending = DB::table('jobs')->where('queue', $queue)->count();
            $processing = DB::table('jobs')
                ->where('queue', $queue)
                ->where('reserved_at', '!=', null)
                ->count();

            $status = $pending > 0 ? '🟡' : '🟢';
            $this->line(sprintf(
                "%s %-10s: %d en attente, %d en cours",
                $status,
                strtoupper($queue),
                $pending,
                $processing
            ));

            if ($showDetails && $pending > 0) {
                $this->showQueueDetails($queue);
            }
        }
    }

    /**
     * Show detailed queue information
     */
    private function showQueueDetails($queue)
    {
        $jobs = DB::table('jobs')
            ->where('queue', $queue)
            ->orderBy('available_at')
            ->limit(5)
            ->get();

        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);
            $jobClass = $payload['displayName'] ?? 'Unknown';
            $createdAt = \Carbon\Carbon::createFromTimestamp($job->created_at);

            $this->line("  └─ {$jobClass} (créé: {$createdAt->diffForHumans()})");
        }
    }

    /**
     * Monitor failed jobs
     */
    private function monitorFailedJobs()
    {
        $this->line('');
        $this->info("❌ Jobs Échoués");
        $this->line(str_repeat('-', 40));

        $failedCount = DB::table('failed_jobs')->count();
        $recentFailed = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subHours(24))
            ->count();

        if ($failedCount === 0) {
            $this->line("🟢 Aucun job échoué");
        } else {
            $this->line("🔴 Total: {$failedCount} jobs échoués");
            $this->line("🟡 Dernières 24h: {$recentFailed} jobs échoués");

            // Afficher les échecs récents
            $recentFailures = DB::table('failed_jobs')
                ->orderBy('failed_at', 'desc')
                ->limit(3)
                ->get();

            foreach ($recentFailures as $failure) {
                $payload = json_decode($failure->payload, true);
                $jobClass = $payload['displayName'] ?? 'Unknown';
                $failedAt = \Carbon\Carbon::parse($failure->failed_at);

                $this->line("  └─ {$jobClass} (échec: {$failedAt->diffForHumans()})");
            }
        }
    }

    /**
     * Monitor system health
     */
    private function monitorSystemHealth()
    {
        $this->line('');
        $this->info("💻 Santé du Système");
        $this->line(str_repeat('-', 40));

        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimit();
        $memoryPercent = ($memoryUsage / $memoryLimit) * 100;

        $memoryStatus = $memoryPercent > 80 ? '🔴' : ($memoryPercent > 60 ? '🟡' : '🟢');
        $this->line(sprintf(
            "%s Mémoire: %s / %s (%.1f%%)",
            $memoryStatus,
            $this->formatBytes($memoryUsage),
            $this->formatBytes($memoryLimit),
            $memoryPercent
        ));

        // Disk space
        $diskFree = disk_free_space(storage_path());
        $diskTotal = disk_total_space(storage_path());
        $diskUsed = $diskTotal - $diskFree;
        $diskPercent = ($diskUsed / $diskTotal) * 100;

        $diskStatus = $diskPercent > 90 ? '🔴' : ($diskPercent > 80 ? '🟡' : '🟢');
        $this->line(sprintf(
            "%s Disque: %s / %s (%.1f%% utilisé)",
            $diskStatus,
            $this->formatBytes($diskUsed),
            $this->formatBytes($diskTotal),
            $diskPercent
        ));

        // Database connections
        try {
            $connectionCount = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;
            $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'")[0]->Value ?? 100;
            $connectionPercent = ($connectionCount / $maxConnections) * 100;

            $connectionStatus = $connectionPercent > 80 ? '🔴' : ($connectionPercent > 60 ? '🟡' : '🟢');
            $this->line(sprintf(
                "%s DB Connexions: %d / %d (%.1f%%)",
                $connectionStatus,
                $connectionCount,
                $maxConnections,
                $connectionPercent
            ));
        } catch (\Exception $e) {
            $this->line("🟡 DB Connexions: Impossible de vérifier");
        }

        // Queue processing rate
        $this->displayProcessingRate();
    }

    /**
     * Display processing rate statistics
     */
    private function displayProcessingRate()
    {
        $this->line('');
        $this->info("📈 Statistiques de Traitement (dernière heure)");
        $this->line(str_repeat('-', 40));

        try {
            // Compter les jobs traités dans la dernière heure via les logs
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $oneHourAgo = now()->subHour();
                $logContent = file_get_contents($logFile);

                // Compter les occurrences de jobs terminés
                $completedJobs = substr_count($logContent, 'terminé avec succès');
                $failedJobs = substr_count($logContent, 'Job échoué');

                $this->line("✅ Jobs terminés: {$completedJobs}");
                $this->line("❌ Jobs échoués: {$failedJobs}");

                if ($completedJobs + $failedJobs > 0) {
                    $successRate = ($completedJobs / ($completedJobs + $failedJobs)) * 100;
                    $this->line(sprintf("📊 Taux de succès: %.1f%%", $successRate));
                }
            }
        } catch (\Exception $e) {
            $this->line("🟡 Statistiques: Non disponibles");
        }
    }

    /**
     * Check and restart workers if needed
     */
    private function checkAndRestartWorkers()
    {
        $this->line('');
        $this->info("🔄 Vérification des Workers");
        $this->line(str_repeat('-', 40));

        // Vérifier si des jobs sont bloqués depuis trop longtemps
        $stuckJobs = DB::table('jobs')
            ->where('reserved_at', '<', now()->subMinutes(30)->timestamp)
            ->where('reserved_at', '!=', null)
            ->count();

        if ($stuckJobs > 0) {
            $this->warn("⚠️  {$stuckJobs} jobs bloqués détectés - Redémarrage des workers...");
            $this->call('queue:restart');
            Log::warning("Workers redémarrés automatiquement - {$stuckJobs} jobs bloqués détectés");
        } else {
            $this->line("🟢 Workers opérationnels");
        }
    }

    /**
     * Format bytes for display
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get memory limit in bytes
     */
    private function getMemoryLimit()
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit == -1) {
            return PHP_INT_MAX;
        }

        $value = (int) $memoryLimit;
        $unit = strtoupper(substr($memoryLimit, -1));

        switch ($unit) {
            case 'G':
                $value *= 1024;
            case 'M':
                $value *= 1024;
            case 'K':
                $value *= 1024;
        }

        return $value;
    }
}
