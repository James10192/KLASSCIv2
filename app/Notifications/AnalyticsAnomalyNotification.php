<?php

namespace App\Notifications;

use App\Domain\Analytics\DTOs\AnomalyAlert;
use App\Mail\Equipe\AlerteEncaissementsMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
        // Le canal mail ne filtre plus une adresse vide quand `toMail` rend une
        // Mailable : sans adresse, la notification reste en base seulement.
        return filled($notifiable->email ?? null) ? ['mail', 'database'] : ['database'];
    }

    /**
     * Le courriel passe par le gabarit des avis (`AlerteEncaissementsMail`),
     * pas par le `MailMessage` de Laravel, qui affichait le nom d'application
     * en tête et des lignes « [CRITICAL] » brutes.
     */
    public function toMail($notifiable): AlerteEncaissementsMail
    {
        return (new AlerteEncaissementsMail(
            $this->alerts,
            trim((string) ($notifiable->name ?? '')),
            route('esbtp.comptabilite.analytics.index'),
        ))->to($notifiable->email);
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
