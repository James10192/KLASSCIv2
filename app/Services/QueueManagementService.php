<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\CalculerKPIsJob;
use App\Jobs\EnvoyerRelanceJob;
use App\Jobs\GenererRapportJob;
use App\Jobs\PlanifierRelancesJob;
use App\Jobs\SauvegardeDataJob;

class QueueManagementService
{
    /**
     * Obtenir les statistiques des files d'attente
     */
    public function getQueueStatistics()
    {
        $queues = ['default', 'high', 'medium', 'low', 'reports', 'backup'];
        $stats = [];

        foreach ($queues as $queue) {
            $pending = DB::table('jobs')->where('queue', $queue)->count();
            $processing = DB::table('jobs')
                ->where('queue', $queue)
                ->where('reserved_at', '!=', null)
                ->count();

            $stats[$queue] = [
                'pending' => $pending,
                'processing' => $processing,
                'total' => $pending + $processing
            ];
        }

        return $stats;
    }

    /**
     * Obtenir les jobs échoués récents
     */
    public function getFailedJobsStatistics()
    {
        $total = DB::table('failed_jobs')->count();
        $recent = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subHours(24))
            ->count();

        $recentFailures = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return [
                    'id' => $job->id,
                    'job_class' => $payload['displayName'] ?? 'Unknown',
                    'queue' => $job->queue,
                    'failed_at' => $job->failed_at,
                    'exception' => substr($job->exception, 0, 200) . '...'
                ];
            });

        return [
            'total' => $total,
            'recent_24h' => $recent,
            'recent_failures' => $recentFailures
        ];
    }

    /**
     * Dispatcher les jobs avec la bonne priorité
     */
    public function dispatchJob($jobClass, $parameters = [], $priority = 'medium')
    {
        try {
            $job = null;

            switch ($jobClass) {
                case 'CalculerKPIsJob':
                    $job = new CalculerKPIsJob(
                        $parameters['periode'] ?? 'journalier',
                        $parameters['annee_id'] ?? null,
                        $parameters['date_calcul'] ?? null
                    );
                    break;

                case 'SauvegardeDataJob':
                    $job = new SauvegardeDataJob(
                        $parameters['type'] ?? 'complet',
                        $parameters['options'] ?? []
                    );
                    break;

                case 'PlanifierRelancesJob':
                    $job = new PlanifierRelancesJob($parameters);
                    break;

                case 'GenererRapportJob':
                    $job = new GenererRapportJob(
                        $parameters['parametres'],
                        $parameters['user_id'] ?? null,
                        $parameters['format'] ?? 'pdf'
                    );
                    break;

                default:
                    throw new \Exception("Type de job non supporté: {$jobClass}");
            }

            if ($job) {
                // Configurer la priorité si pas déjà définie dans le constructeur
                if (method_exists($job, 'onQueue')) {
                    $job->onQueue($this->getQueueForPriority($priority));
                }

                dispatch($job);

                Log::info("Job dispatché avec succès", [
                    'job_class' => $jobClass,
                    'priority' => $priority,
                    'parameters' => $parameters
                ]);

                return [
                    'success' => true,
                    'message' => "Job {$jobClass} ajouté à la file d'attente {$priority}"
                ];
            }
        } catch (\Exception $e) {
            Log::error("Erreur lors du dispatch du job", [
                'job_class' => $jobClass,
                'parameters' => $parameters,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => "Erreur: " . $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir le nom de la file selon la priorité
     */
    private function getQueueForPriority($priority)
    {
        return match($priority) {
            'critical', 'urgent' => 'high',
            'high' => 'high',
            'medium', 'normal' => 'medium',
            'low' => 'low',
            'backup' => 'backup',
            'reports' => 'reports',
            default => 'medium'
        };
    }

    /**
     * Relancer les jobs échoués
     */
    public function retryFailedJobs($jobIds = null)
    {
        try {
            if ($jobIds) {
                // Relancer des jobs spécifiques
                $retried = 0;
                foreach ($jobIds as $jobId) {
                    if (artisan('queue:retry', ['id' => $jobId]) === 0) {
                        $retried++;
                    }
                }

                return [
                    'success' => true,
                    'message' => "{$retried} jobs relancés"
                ];
            } else {
                // Relancer tous les jobs échoués
                artisan('queue:retry', ['id' => 'all']);

                return [
                    'success' => true,
                    'message' => "Tous les jobs échoués ont été relancés"
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Erreur lors de la relance: " . $e->getMessage()
            ];
        }
    }

    /**
     * Purger les jobs échoués
     */
    public function purgeFailedJobs($olderThanHours = 168) // 7 jours par défaut
    {
        try {
            $cutoffDate = now()->subHours($olderThanHours);
            $deleted = DB::table('failed_jobs')
                ->where('failed_at', '<', $cutoffDate)
                ->delete();

            Log::info("Jobs échoués purgés", [
                'deleted_count' => $deleted,
                'older_than_hours' => $olderThanHours
            ]);

            return [
                'success' => true,
                'message' => "{$deleted} jobs échoués supprimés"
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Erreur lors de la purge: " . $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir les performances des workers
     */
    public function getWorkerPerformance()
    {
        try {
            $performance = [
                'active_workers' => $this->getActiveWorkersCount(),
                'queue_throughput' => $this->calculateQueueThroughput(),
                'average_processing_time' => $this->calculateAverageProcessingTime(),
                'error_rate' => $this->calculateErrorRate()
            ];

            return $performance;
        } catch (\Exception $e) {
            Log::error("Erreur lors du calcul des performances: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Compter les workers actifs (approximation)
     */
    private function getActiveWorkersCount()
    {
        // Approximation basée sur les jobs en cours de traitement
        return DB::table('jobs')
            ->where('reserved_at', '!=', null)
            ->distinct('queue')
            ->count('queue');
    }

    /**
     * Calculer le débit des files d'attente
     */
    private function calculateQueueThroughput()
    {
        // Calcul basé sur les logs des dernières heures
        $logFile = storage_path('logs/laravel.log');
        if (!file_exists($logFile)) {
            return 0;
        }

        $content = file_get_contents($logFile);
        $completedJobs = substr_count($content, 'terminé avec succès');

        return $completedJobs; // Jobs traités dans la dernière période
    }

    /**
     * Calculer le temps moyen de traitement
     */
    private function calculateAverageProcessingTime()
    {
        // Estimation basée sur la configuration des files
        $queueConfig = config('queue.workers', []);
        $totalTimeout = 0;
        $queueCount = 0;

        foreach ($queueConfig as $queue => $config) {
            $totalTimeout += $config['timeout'] ?? 60;
            $queueCount++;
        }

        return $queueCount > 0 ? round($totalTimeout / $queueCount, 2) : 60;
    }

    /**
     * Calculer le taux d'erreur
     */
    private function calculateErrorRate()
    {
        $totalJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        if ($totalJobs + $failedJobs === 0) {
            return 0;
        }

        return round(($failedJobs / ($totalJobs + $failedJobs)) * 100, 2);
    }

    /**
     * Planifier des sauvegardes automatiques
     */
    public function scheduleAutomaticBackups()
    {
        try {
            // Sauvegarde quotidienne complète
            dispatch(new SauvegardeDataJob('complet', [
                'inclure_fichiers' => true,
                'compression' => true,
                'retention_jours' => 30
            ]))->delay(now()->addMinutes(5));

            Log::info("Sauvegarde automatique planifiée");

            return [
                'success' => true,
                'message' => 'Sauvegarde automatique planifiée'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la planification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier la santé des files d'attente
     */
    public function healthCheck()
    {
        $issues = [];

        // Vérifier les jobs bloqués
        $stuckJobs = DB::table('jobs')
            ->where('reserved_at', '<', now()->subMinutes(30)->timestamp)
            ->where('reserved_at', '!=', null)
            ->count();

        if ($stuckJobs > 0) {
            $issues[] = "Jobs bloqués détectés: {$stuckJobs}";
        }

        // Vérifier l'accumulation de jobs
        $totalPending = DB::table('jobs')->count();
        if ($totalPending > 1000) {
            $issues[] = "Accumulation importante de jobs: {$totalPending}";
        }

        // Vérifier les échecs récents
        $recentFailures = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subHour())
            ->count();

        if ($recentFailures > 10) {
            $issues[] = "Taux d'échec élevé: {$recentFailures} dans la dernière heure";
        }

        return [
            'healthy' => empty($issues),
            'issues' => $issues,
            'timestamp' => now()->toISOString()
        ];
    }
}
