<?php

namespace App\Notifications;

use App\Domain\Analytics\DTOs\AnomalyAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnalyticsAnomalyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, AnomalyAlert>  $alerts
     */
    public function __construct(
        public readonly array $alerts,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $criticalCount = count(array_filter($this->alerts, fn ($a) => $a->isCritical()));
        $totalCount = count($this->alerts);

        $mail = (new MailMessage)
            ->subject(sprintf('[KLASSCI Analytics] %d anomalie(s) détectée(s)%s', $totalCount, $criticalCount > 0 ? ' (dont '.$criticalCount.' critique(s))' : ''))
            ->greeting('Bonjour ' . ($notifiable->name ?? ''))
            ->line('Le moteur Analytics a détecté des anomalies dans vos flux financiers.');

        foreach (array_slice($this->alerts, 0, 10) as $alert) {
            $mail->line(sprintf('• [%s] %s', strtoupper($alert->severity), $alert->message));
        }

        if ($totalCount > 10) {
            $mail->line(sprintf('... et %d autres anomalies. Voir le détail dans le tableau de bord.', $totalCount - 10));
        }

        return $mail
            ->action('Voir les analytics', route('esbtp.comptabilite.analytics.index'))
            ->line('Cette notification est envoyée automatiquement aux administrateurs et comptables. Configurez les seuils via les paramètres Analytics.');
    }

    public function toArray($notifiable): array
    {
        return [
            'alerts_count' => count($this->alerts),
            'critical_count' => count(array_filter($this->alerts, fn ($a) => $a->isCritical())),
            'alerts' => array_map(fn (AnomalyAlert $a) => $a->toArray(), array_slice($this->alerts, 0, 20)),
            'detected_at' => now()->toISOString(),
            'action_url' => route('esbtp.comptabilite.analytics.index'),
        ];
    }
}
