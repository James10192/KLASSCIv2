<?php

namespace App\Domain\Comptabilite\Reconciliation\Notifications;

use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReconciliationOverdueNotification extends Notification
{
    use Queueable;

    /**
     * @param \Illuminate\Support\Collection<int, ReconciliationSession> $overdueSessions
     */
    public function __construct(
        public \Illuminate\Support\Collection $overdueSessions,
        public int $thresholdDays
    ) {}

    /**
     * @return array<int,string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->overdueSessions->count();
        $mail = (new MailMessage)
            ->subject("[KLASSCI] {$count} session(s) de réconciliation overdue")
            ->greeting('Bonjour ' . ($notifiable->name ?? 'comptable') . ',')
            ->line("Vous avez **{$count} session(s)** de réconciliation caisse ouverte(s) depuis plus de {$this->thresholdDays} jour(s) sans clôture.")
            ->line('Pour respecter le cycle comptable OHADA et garantir l\'audit fiscal, ces sessions doivent être traitées rapidement.');

        foreach ($this->overdueSessions->take(5) as $session) {
            $opened = optional($session->opened_at)->format('d/m/Y');
            $mail->line("• **{$session->code}** — ouverte le {$opened} (statut : {$session->status->label()})");
        }

        if ($count > 5) {
            $more = $count - 5;
            $mail->line("… et {$more} autre(s).");
        }

        return $mail
            ->action('Voir les sessions à traiter', route('esbtp.comptabilite.reconciliation.index', ['status' => 'draft']))
            ->salutation('— KLASSCI Comptabilité');
    }

    /**
     * @return array<string,mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'reconciliation_overdue',
            'count' => $this->overdueSessions->count(),
            'threshold_days' => $this->thresholdDays,
            'sample_codes' => $this->overdueSessions->take(5)->pluck('code')->all(),
            'message' => "{$this->overdueSessions->count()} session(s) de réconciliation overdue (> {$this->thresholdDays}j).",
            'action_url' => route('esbtp.comptabilite.reconciliation.index', ['status' => 'draft']),
        ];
    }
}
