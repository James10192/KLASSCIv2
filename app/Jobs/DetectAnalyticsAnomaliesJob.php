<?php

namespace App\Jobs;

use App\Domain\Analytics\Detectors\AnomalyDetector;
use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\DTOs\AnomalyAlert;
use App\Helpers\SettingsHelper;
use App\Models\User;
use App\Notifications\AnalyticsAnomalyNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Job de détection d'anomalies financières (toutes les 6h via scheduler).
 * Si au moins 1 alerte CRITICAL, notifie superAdmin + comptables par mail
 * + database. Déduplication par signature 24h pour éviter spam (B3).
 */
class DetectAnalyticsAnomaliesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public array $backoff = [60, 180, 300];

    private const DEDUP_TTL_SECONDS = 86400;
    private const NOTIFY_ROLES = ['superAdmin', 'comptable'];

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(AnomalyDetector $detector): void
    {
        if (!$this->notificationsEnabled()) {
            Log::info('Analytics anomaly notifications disabled via settings, skipping');
            return;
        }

        $context = AnalyticsContext::empty();
        $alerts = $detector->detect($context);

        if (empty($alerts)) {
            Log::info('Analytics anomaly detection: no anomalies found');
            return;
        }

        $criticalAlerts = array_filter($alerts, fn (AnomalyAlert $a) => $a->isCritical());

        Log::info('Analytics anomaly detection completed', [
            'total' => count($alerts),
            'critical' => count($criticalAlerts),
        ]);

        if (empty($criticalAlerts)) {
            return;
        }

        $newAlerts = $this->dedupAlerts($criticalAlerts);
        if (empty($newAlerts)) {
            Log::info('All critical anomalies already notified within dedup window');
            return;
        }

        $this->notifyAdmins($newAlerts);
    }

    private function notificationsEnabled(): bool
    {
        return (string) SettingsHelper::get('analytics.anomaly.notifications_enabled', '1') === '1';
    }

    /**
     * @param  array<int, AnomalyAlert>  $alerts
     * @return array<int, AnomalyAlert>
     */
    private function dedupAlerts(array $alerts): array
    {
        $newAlerts = [];

        foreach ($alerts as $alert) {
            $scoreBucket = (int) floor($alert->score);
            $signature = sprintf(
                '%s:%s:%d:%s:%d',
                $alert->type,
                $alert->entityType,
                $alert->entityId,
                $alert->severity,
                $scoreBucket,
            );
            $cacheKey = "analytics:anomaly:notified:{$signature}";

            if (Cache::has($cacheKey)) {
                continue;
            }

            Cache::put($cacheKey, true, self::DEDUP_TTL_SECONDS);
            $newAlerts[] = $alert;
        }

        return $newAlerts;
    }

    /**
     * @param  array<int, AnomalyAlert>  $alerts
     */
    private function notifyAdmins(array $alerts): void
    {
        try {
            $users = User::role(self::NOTIFY_ROLES)->get();
            if ($users->isEmpty()) {
                Log::warning('No users with admin/comptable roles to notify', ['alerts_count' => count($alerts)]);
                return;
            }

            Notification::send($users, new AnalyticsAnomalyNotification($alerts));

            Log::info('Analytics anomaly notification sent', [
                'recipients' => $users->count(),
                'alerts_count' => count($alerts),
            ]);
        } catch (\Throwable $e) {
            Log::error('Analytics anomaly notification failed to dispatch', [
                'error' => $e->getMessage(),
                'alerts_count' => count($alerts),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DetectAnalyticsAnomaliesJob failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
