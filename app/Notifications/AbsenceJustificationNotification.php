<?php

namespace App\Notifications;

use App\Models\ESBTPAttendance;
use App\Models\ESBTPEtudiant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbsenceJustificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $attendance;
    protected $etudiant;
    protected $justification;
    protected $documentPath;

    /**
     * Create a new notification instance.
     *
     * @param ESBTPAttendance $attendance
     * @param ESBTPEtudiant $etudiant
     * @param string $justification
     * @param string|null $documentPath
     * @return void
     */
    public function __construct(ESBTPAttendance $attendance, ESBTPEtudiant $etudiant, string $justification, ?string $documentPath = null)
    {
        $this->attendance = $attendance;
        $this->etudiant = $etudiant;
        $this->justification = $justification;
        $this->documentPath = $documentPath;
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
        $matiereName = $this->attendance->seanceCours->matiere->name ?? 'N/A';
        $date = $this->attendance->date ? $this->attendance->date->format('d/m/Y') : 'N/A';
        $etudiantNom = $this->etudiant->nom . ' ' . $this->etudiant->prenoms;

        $mail = (new MailMessage)
                    ->subject('Demande de justification d\'absence - ' . $etudiantNom)
                    ->greeting('Bonjour,')
                    ->line('L\'étudiant ' . $etudiantNom . ' a justifié son absence du ' . $date . ' au cours de ' . $matiereName . '.')
                    ->line('Justification : ' . $this->justification);

        // Ajouter le lien vers le document si disponible
        if ($this->documentPath) {
            $documentUrl = url('storage/' . $this->documentPath);
            $mail->line('Un document justificatif a été fourni.')
                 ->action('Voir le document', $documentUrl);
        }

        // Ajouter le lien vers la page de gestion des absences
        $mail->line('Veuillez examiner cette justification et mettre à jour le statut de l\'absence en conséquence.')
             ->action('Gérer les absences', route('esbtp.attendances.index'));

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $matiereName = $this->attendance->seanceCours->matiere->name ?? 'N/A';
        $date = $this->attendance->date ? $this->attendance->date->format('d/m/Y') : 'N/A';
        $etudiantNom = $this->etudiant->nom . ' ' . $this->etudiant->prenoms;

        // Générer le lien vers la page de gestion des absences avec un filtre sur l'étudiant
        $link = route('esbtp.attendances.index', [
            'etudiant_id' => $this->etudiant->id,
            'highlight' => 'absence_' . $this->attendance->id
        ]);

        return [
            'title' => 'Justification d\'absence - ' . $etudiantNom,
            'message' => 'L\'étudiant ' . $etudiantNom . ' a justifié son absence du ' . $date . ' au cours de ' . $matiereName . '.',
            'type' => 'warning',
            'link' => $link,
            'data' => [
                'attendance_id' => $this->attendance->id,
                'etudiant_id' => $this->etudiant->id,
                'matiere' => $matiereName,
                'date' => $date,
                'justification' => $this->justification,
                'document_path' => $this->documentPath
            ]
        ];
    }
}
