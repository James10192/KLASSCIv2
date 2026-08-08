<?php

namespace App\Services\MailPulse;

use App\Models\ESBTPAttendance;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;

/**
 * Workflow events that used to stay confined to email and in-app notifications.
 *
 * They reuse MailPulseWorkflowNotificationService::notifyTutor() so consent,
 * channel selection, the outbox and the kill switch behave exactly like the
 * five events that were wired first. Message bodies stay ASCII only, matching
 * the existing MailPulse copy so SMS stays inside the GSM-7 alphabet.
 */
class MailPulseExtraWorkflowNotifications
{
    public function __construct(private MailPulseWorkflowNotificationService $workflow) {}

    public function notifyPaymentRejected(ESBTPPaiement $paiement): void
    {
        $paiement->loadMissing(['etudiant.parents', 'inscription']);
        $etudiant = $paiement->etudiant ?? $paiement->inscription?->etudiant;
        if (! $etudiant) {
            return;
        }

        $motif = $paiement->motif_rejet ?: ($paiement->commentaire ?: 'Aucun motif precise');

        $this->workflow->notifyTutor(MailPulseWorkflowIntent::PAYMENT_REJECTED, $etudiant, [
            'subject' => 'Paiement rejete',
            'summary' => 'Paiement rejete pour ' . $this->studentName($etudiant) . '.',
            'body' => sprintf(
                'Bonjour {parent}, le paiement de %s pour %s a ete rejete. Motif: %s. Reference: %s. Merci de contacter le service scolarite.',
                $this->money((float) $paiement->montant),
                $this->studentName($etudiant),
                $motif,
                $paiement->reference_paiement ?: 'N/A'
            ),
            'metadata' => [
                'paiement_id' => $paiement->id,
                'inscription_id' => $paiement->inscription_id,
                'rejection_occurrence' => $this->rejectionOccurrence($paiement),
            ],
        ]);
    }

    public function notifyEnrollmentCreated(ESBTPInscription $inscription): void
    {
        $inscription->loadMissing(['etudiant.parents', 'classe', 'anneeUniversitaire']);
        $etudiant = $inscription->etudiant;
        if (! $etudiant) {
            return;
        }

        $this->workflow->notifyTutor(MailPulseWorkflowIntent::ENROLLMENT_CREATED, $etudiant, [
            'subject' => 'Inscription confirmee',
            'summary' => 'Inscription enregistree pour ' . $this->studentName($etudiant) . '.',
            'body' => sprintf(
                'Bonjour {parent}, l inscription de %s en %s pour l annee %s est enregistree. Les identifiants de connexion ont ete transmis par email. Consultez KLASSCI pour la situation financiere.',
                $this->studentName($etudiant),
                $this->className($inscription),
                $this->academicYear($inscription)
            ),
            'metadata' => [
                'inscription_id' => $inscription->id,
                'classe_id' => $inscription->classe_id,
                'annee_universitaire_id' => $inscription->annee_universitaire_id,
            ],
        ]);
    }

    public function notifyReEnrollmentCreated(ESBTPInscription $inscription, string $decision, float $reliquatAmount): void
    {
        $inscription->loadMissing(['etudiant.parents', 'classe', 'anneeUniversitaire']);
        $etudiant = $inscription->etudiant;
        if (! $etudiant) {
            return;
        }

        $reliquat = $reliquatAmount > 0
            ? ' Reliquat reporte: ' . $this->money($reliquatAmount) . '.'
            : '';

        $this->workflow->notifyTutor(MailPulseWorkflowIntent::REENROLLMENT_CREATED, $etudiant, [
            'subject' => 'Reinscription confirmee',
            'summary' => 'Reinscription enregistree pour ' . $this->studentName($etudiant) . '.',
            'body' => sprintf(
                'Bonjour {parent}, la reinscription de %s en %s pour l annee %s est enregistree (decision: %s).%s',
                $this->studentName($etudiant),
                $this->className($inscription),
                $this->academicYear($inscription),
                $decision !== '' ? $decision : 'passage',
                $reliquat
            ),
            'metadata' => [
                'inscription_id' => $inscription->id,
                'classe_id' => $inscription->classe_id,
                'annee_universitaire_id' => $inscription->annee_universitaire_id,
                'decision' => $decision,
            ],
        ]);
    }

    public function notifyLowGrades(ESBTPBulletin $bulletin): void
    {
        $bulletin->loadMissing(['etudiant.parents', 'classe']);
        $etudiant = $bulletin->etudiant;
        if (! $etudiant) {
            return;
        }

        $moyenne = $bulletin->moyenne_generale === null
            ? 'non renseignee'
            : $this->formatNumber((float) $bulletin->moyenne_generale) . '/20';

        $this->workflow->notifyTutor(MailPulseWorkflowIntent::LOW_GRADES, $etudiant, [
            'subject' => 'Alerte resultats',
            'summary' => 'Resultats en difficulte pour ' . $this->studentName($etudiant) . '.',
            'body' => sprintf(
                'Bonjour {parent}, les resultats de %s pour %s appellent votre attention. Moyenne generale: %s. Connectez vous a KLASSCI pour le detail par matiere et les dispositifs d accompagnement.',
                $this->studentName($etudiant),
                $bulletin->periode ?: 'la periode en cours',
                $moyenne
            ),
            'metadata' => [
                'bulletin_id' => $bulletin->id,
                'classe_id' => $bulletin->classe_id,
            ],
        ]);
    }

    public function notifyLowAttendance(ESBTPAttendance $attendance, float $attendanceRate, int $threshold): void
    {
        $attendance->loadMissing(['etudiant.parents']);
        $etudiant = $attendance->etudiant;
        if (! $etudiant) {
            return;
        }

        $this->workflow->notifyTutor(MailPulseWorkflowIntent::LOW_ATTENDANCE, $etudiant, [
            'subject' => 'Alerte assiduite',
            'summary' => 'Assiduite en baisse pour ' . $this->studentName($etudiant) . '.',
            'body' => sprintf(
                'Bonjour {parent}, le taux de presence de %s est de %s pour cent ce mois ci, sous le seuil de %d pour cent. Merci de consulter KLASSCI pour justifier les absences.',
                $this->studentName($etudiant),
                $this->formatNumber($attendanceRate),
                $threshold
            ),
            // One alert per student and per month: the running rate stays out of
            // the identity so a second absence does not re-trigger the alert.
            'metadata' => [
                'alert' => 'low_attendance',
                'period' => now()->format('Y-m'),
            ],
        ]);
    }

    private function rejectionOccurrence(ESBTPPaiement $paiement): string
    {
        $rejectedAt = $paiement->updated_at?->copy()->utc()->format('Y-m-d\TH:i:s\Z') ?? 'unknown';

        return implode(':', [(string) $paiement->getKey(), $rejectedAt]);
    }

    private function className(ESBTPInscription $inscription): string
    {
        return $inscription->classe?->name
            ?? $inscription->classe?->nom
            ?? 'classe non renseignee';
    }

    private function academicYear(ESBTPInscription $inscription): string
    {
        return $inscription->anneeUniversitaire?->name
            ?? $inscription->anneeUniversitaire?->nom
            ?? 'en cours';
    }

    private function studentName(ESBTPEtudiant $etudiant): string
    {
        return trim($etudiant->nom . ' ' . $etudiant->prenoms);
    }

    private function money(float $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' FCFA';
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',');
    }
}
