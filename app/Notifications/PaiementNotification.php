<?php

namespace App\Notifications;

use App\Models\ESBTPPaiement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaiementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $paiement;

    /**
     * Create a new notification instance.
     */
    public function __construct(ESBTPPaiement $paiement)
    {
        $this->paiement = $paiement;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouveau paiement reçu')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Un paiement de ' . number_format($this->paiement->montant) . ' FCFA a été reçu.')
            ->line('Étudiant: ' . ($this->paiement->etudiant->nom_complet ?? 'Inconnu'))
            ->line('Date de paiement: ' . $this->paiement->date_paiement?->format('d/m/Y'))
            ->action('Voir détails', url('/esbtp/comptabilite/paiements/' . $this->paiement->id))
            ->line('Merci d\'utiliser ESBTP KLASSCI!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'paiement_id' => $this->paiement->id,
            'montant' => $this->paiement->montant,
            'etudiant' => $this->paiement->etudiant->nom_complet ?? 'Inconnu',
            'type_paiement' => $this->paiement->type_paiement,
            'date_paiement' => $this->paiement->date_paiement?->format('d/m/Y'),
            'action_url' => url('/esbtp/comptabilite/paiements/' . $this->paiement->id)
        ];
    }
}
