<?php

namespace App\Notifications;

use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * S1.6 — Notification "gros montant validé".
 *
 * Envoyée aux users ayant la permission `comptabilite.notifications.high_amount`
 * dès qu'un paiement supérieur au seuil tenant configuré est validé.
 *
 * Vise typiquement le directeur d'école (qui n'est pas comptable mais veut être
 * informé des grosses entrées). L'école assigne la perm via UI custom roles.
 */
class PaiementHighAmountValidatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ESBTPPaiement $paiement,
        public readonly ?User $validateur,
        public readonly int $threshold,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $etudiantNom = $this->paiement->etudiant
            ? trim(($this->paiement->etudiant->prenoms ?? '') . ' ' . ($this->paiement->etudiant->nom ?? ''))
            : 'Étudiant inconnu';

        $montantFmt = number_format((float) $this->paiement->montant, 0, ',', ' ');
        $thresholdFmt = number_format($this->threshold, 0, ',', ' ');
        $validateurNom = $this->validateur?->name ?? 'Comptable';

        return (new MailMessage)
            ->subject(sprintf('[KLASSCI] Paiement de %s FCFA validé', $montantFmt))
            ->greeting('Bonjour ' . ($notifiable->name ?? ''))
            ->line(sprintf(
                'Un paiement supérieur au seuil de notification (%s FCFA) vient d\'être validé.',
                $thresholdFmt,
            ))
            ->line(sprintf('• **Étudiant** : %s', $etudiantNom))
            ->line(sprintf('• **Montant** : %s FCFA', $montantFmt))
            ->line(sprintf('• **Catégorie** : %s', $this->paiement->fraisCategory->name ?? $this->paiement->motif ?? 'Non renseignée'))
            ->line(sprintf('• **Mode de paiement** : %s', $this->paiement->mode_paiement ?? 'Non renseigné'))
            ->line(sprintf('• **Validé par** : %s', $validateurNom))
            ->line(sprintf('• **Date validation** : %s', optional($this->paiement->date_validation)->format('d/m/Y à H:i') ?? 'Maintenant'))
            ->action('Voir le paiement', route('esbtp.paiements.show', $this->paiement->id))
            ->line('Cette notification est envoyée automatiquement aux personnes habilitées à surveiller les gros montants. Vous pouvez modifier ce seuil ou désactiver cette alerte dans les paramètres comptables.');
    }

    public function toArray($notifiable): array
    {
        return [
            'paiement_id' => $this->paiement->id,
            'numero_recu' => $this->paiement->numero_recu,
            'montant' => (float) $this->paiement->montant,
            'threshold' => $this->threshold,
            'etudiant_id' => $this->paiement->etudiant_id ?? null,
            'etudiant_nom' => $this->paiement->etudiant
                ? trim(($this->paiement->etudiant->prenoms ?? '') . ' ' . ($this->paiement->etudiant->nom ?? ''))
                : null,
            'mode_paiement' => $this->paiement->mode_paiement ?? null,
            'validateur_id' => $this->validateur?->id,
            'validateur_nom' => $this->validateur?->name,
            'validated_at' => optional($this->paiement->date_validation)->toIso8601String() ?? now()->toIso8601String(),
            'action_url' => route('esbtp.paiements.show', $this->paiement->id),
        ];
    }
}
