<?php

namespace App\Notifications;

use App\Enums\TpeDeclarationStatut;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPTpeDeclaration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie l'étudiant quand sa déclaration TPE passe de EN_ATTENTE à VALIDE
 * ou REJETE (Option 3 — workflow prof).
 *
 * Channels :
 *  - 'database' (toujours) : badge cloche dans le header
 *  - 'mail' (opt-in via Setting `tpe.notify_email = true`)
 */
class TpeDeclarationStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ESBTPTpeDeclaration $declaration,
        public readonly TpeDeclarationStatut $newStatut,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        if ((bool) SettingsHelper::get('tpe.notify_email', false)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $matiereNom = $this->declaration->matiere->name ?? 'Matière';
        $semaine = optional($this->declaration->semaine_debut)->format('d/m/Y') ?? '—';
        $heures = number_format((float) $this->declaration->heures, 2, ',', ' ');

        $message = (new MailMessage())
            ->subject(sprintf(
                '[KLASSCI] Déclaration TPE %s : %s',
                $this->newStatut->label(),
                $matiereNom,
            ))
            ->greeting('Bonjour ' . ($notifiable->name ?? ''))
            ->line(sprintf(
                'Votre déclaration TPE pour **%s** (semaine du %s, %s heures) a été **%s**.',
                $matiereNom,
                $semaine,
                $heures,
                mb_strtolower($this->newStatut->label(), 'UTF-8'),
            ));

        if ($this->newStatut === TpeDeclarationStatut::REJETE && $this->declaration->commentaire_rejet) {
            $message->line('**Motif** : ' . $this->declaration->commentaire_rejet);
        }

        return $message
            ->action('Voir mon journal TPE', route('esbtp.tpe-journal.index'))
            ->line('Vous pouvez consulter le détail et corriger si nécessaire depuis votre journal.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'declaration_id' => $this->declaration->id,
            'matiere_id' => $this->declaration->matiere_id,
            'matiere_nom' => $this->declaration->matiere->name ?? null,
            'semaine_debut' => optional($this->declaration->semaine_debut)->toDateString(),
            'heures' => (float) $this->declaration->heures,
            'statut' => $this->newStatut->value,
            'statut_label' => $this->newStatut->label(),
            'commentaire_rejet' => $this->declaration->commentaire_rejet,
            'validator_id' => $this->declaration->validated_by,
            'validated_at' => optional($this->declaration->validated_at)->toIso8601String(),
            'action_url' => route('esbtp.tpe-journal.index'),
        ];
    }
}
