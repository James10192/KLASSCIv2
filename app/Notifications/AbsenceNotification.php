<?php

namespace App\Notifications;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPSeanceCours;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class AbsenceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $etudiant;
    protected $seanceCours;
    protected $date;

    /**
     * Create a new notification instance.
     *
     * @param ESBTPEtudiant $etudiant
     * @param ESBTPSeanceCours $seanceCours
     * @param string $date
     * @return void
     */
    public function __construct(ESBTPEtudiant $etudiant, ESBTPSeanceCours $seanceCours, $date)
    {
        $this->etudiant = $etudiant;
        $this->seanceCours = $seanceCours;
        $this->date = $date instanceof Carbon ? $date : Carbon::parse($date);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = route('esbtp.mes-absences.index');
        $matiereName = $this->seanceCours->matiere->name ?? 'N/A';
        $typeSeance = $this->seanceCours->type_cours ?? 'N/A';
        $date = $this->date->format('d/m/Y');

        return (new MailMessage)
                    ->subject('Notification d\'absence - ' . $matiereName)
                    ->line('Bonjour ' . $this->etudiant->prenoms . ',')
                    ->line('Vous avez été marqué(e) absent(e) pour le cours de ' . $matiereName . ' (' . $typeSeance . ') du ' . $date . '.')
                    ->line('Attention : Cette absence aura un impact sur votre note d\'assiduité et pourrait entraîner un zéro si une évaluation a été réalisée durant cette séance.')
                    ->line('Si cette absence est justifiée, veuillez fournir un justificatif dans les plus brefs délais.')
                    ->action('Justifier mon absence', $url)
                    ->line('Si vous ne justifiez pas votre absence, celle-ci sera considérée comme non justifiée et affectera votre évaluation.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $matiereName = $this->seanceCours->matiere->name ?? 'N/A';
        $typeSeance = $this->seanceCours->type_cours ?? 'N/A';
        $date = $this->date->format('d/m/Y');

        // Générer un lien direct pour la justification d'absence
        $link = route('esbtp.mes-absences.index', [
            'highlight' => 'absence_' . $this->seanceCours->id . '_' . $this->date->format('Y-m-d')
        ]);

        return [
            'title' => 'Absence en cours de ' . $matiereName,
            'message' => 'Vous avez été marqué(e) absent(e) pour le cours de ' . $matiereName . ' (' . $typeSeance . ') du ' . $date . '. Veuillez justifier cette absence rapidement pour éviter des pénalités sur votre note d\'assiduité.',
            'type' => 'danger',
            'link' => $link,
            'data' => [
                'etudiant_id' => $this->etudiant->id,
                'seance_cours_id' => $this->seanceCours->id,
                'date' => $this->date->format('Y-m-d'),
                'matiere' => $matiereName,
                'type_seance' => $typeSeance
            ]
        ];
    }
}
