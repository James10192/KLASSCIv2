<?php

namespace App\Services\MailPulse;

use App\Domain\Notifications\PhoneNormalizer;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPParent;
use App\Models\ESBTPNote;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class MailPulseWorkflowNotificationService
{
    public function __construct(private MailPulseClient $client) {}

    public function notifyPaymentReceived(ESBTPPaiement $paiement): void
    {
        $paiement->loadMissing(['inscription.classe', 'etudiant.parents', 'fraisCategory']);

        $etudiant = $paiement->etudiant;
        if (! $etudiant) {
            return;
        }

        $this->notifyTutor(
            'payment_received',
            $etudiant,
            [
                'subject' => 'Recu de paiement KLASSCI',
                'summary' => 'Paiement recu pour ' . $this->studentName($etudiant) . '.',
                'body' => sprintf(
                    'Bonjour {parent}, nous confirmons la reception du paiement de %s pour %s. Reference: %s. Recu: %s.',
                    $this->money((float) $paiement->montant),
                    $this->studentName($etudiant),
                    $paiement->reference_paiement ?: 'N/A',
                    $paiement->numero_recu ?: 'N/A'
                ),
                'metadata' => [
                    'paiement_id' => $paiement->id,
                    'inscription_id' => $paiement->inscription_id,
                    'amount' => $paiement->montant,
                    'receipt_number' => $paiement->numero_recu,
                    'fee_category' => $paiement->fraisCategory?->name,
                ],
            ]
        );
    }

    public function notifyAbsenceReported(ESBTPAttendance $attendance): void
    {
        $attendance->loadMissing(['etudiant.parents', 'matiere']);
        $etudiant = $attendance->etudiant;
        if (! $etudiant) {
            return;
        }

        $date = $attendance->date ? $attendance->date->format('d/m/Y') : now()->format('d/m/Y');
        $matiere = $attendance->matiere?->name ?? $attendance->matiere?->nom ?? 'Cours';

        $this->notifyTutor(
            'absence_reported',
            $etudiant,
            [
                'subject' => 'Absence signalee',
                'summary' => 'Absence signalee pour ' . $this->studentName($etudiant) . '.',
                'body' => sprintf(
                    'Bonjour {parent}, une absence a ete signalee pour %s le %s en %s. Merci de consulter KLASSCI pour les details et justificatifs.',
                    $this->studentName($etudiant),
                    $date,
                    $matiere
                ),
                'metadata' => [
                    'attendance_id' => $attendance->id,
                    'student_id' => $etudiant->id,
                    'date' => $date,
                    'matiere' => $matiere,
                ],
            ]
        );
    }

    public function notifyGradePublished(ESBTPNote $note): void
    {
        $note->loadMissing(['etudiant.parents', 'evaluation', 'matiere']);
        $etudiant = $note->etudiant;
        if (! $etudiant || $note->is_absent) {
            return;
        }

        $matiere = $note->matiere?->name ?? $note->matiere?->nom ?? $note->evaluation?->matiere?->name ?? 'Matiere';
        $bareme = $note->evaluation?->bareme ?? 20;

        $this->notifyTutor(
            'grade_published',
            $etudiant,
            [
                'subject' => 'Note publiee',
                'summary' => 'Nouvelle note disponible pour ' . $this->studentName($etudiant) . '.',
                'body' => sprintf(
                    'Bonjour {parent}, une note de %s/%s en %s vient d etre publiee pour %s.',
                    $this->formatNumber((float) $note->note),
                    $this->formatNumber((float) $bareme),
                    $matiere,
                    $this->studentName($etudiant)
                ),
                'metadata' => [
                    'note_id' => $note->id,
                    'evaluation_id' => $note->evaluation_id,
                    'student_id' => $etudiant->id,
                    'matiere' => $matiere,
                ],
            ]
        );
    }

    public function notifyFeeReminder(ESBTPPaiement $paiement, int $daysPending, int $reminderCount): void
    {
        $paiement->loadMissing(['inscription.classe', 'etudiant.parents', 'fraisCategory']);
        $etudiant = $paiement->etudiant;
        if (! $etudiant) {
            return;
        }

        $this->notifyTutor(
            'fee_reminder',
            $etudiant,
            [
                'subject' => 'Rappel de frais impayes',
                'summary' => 'Rappel de frais pour ' . $this->studentName($etudiant) . '.',
                'body' => sprintf(
                    'Bonjour {parent}, le paiement de %s pour %s est en attente depuis %d jour(s). Rappel numero %d.',
                    $this->money((float) $paiement->montant),
                    $this->studentName($etudiant),
                    $daysPending,
                    $reminderCount
                ),
                'metadata' => [
                    'paiement_id' => $paiement->id,
                    'days_pending' => $daysPending,
                    'reminder_count' => $reminderCount,
                    'amount' => $paiement->montant,
                ],
            ]
        );
    }

    private function notifyTutor(string $event, ESBTPEtudiant $etudiant, array $message): void
    {
        if (! $this->realWorkflowsEnabled()) {
            return;
        }

        $tuteur = $etudiant->tuteur;
        if (! $tuteur) {
            return;
        }

        $preferences = $tuteur->getOrCreateNotificationPreferences();
        if (! $preferences->isNotificationEnabled($this->preferenceType($event))) {
            return;
        }

        $channels = $preferences->preferred_channels ?? ['email'];
        $contact = $this->upsertContact($tuteur, $etudiant);
        if (! $contact->ok) {
            $this->logResult($event, 'contact', $contact, $tuteur, $etudiant);
            return;
        }

        $contactId = $contact->id ?? ('klassci-parent-' . $tuteur->id);
        if (in_array('email', $channels, true) && $tuteur->email) {
            $result = $this->client->sendEmailMessage($this->emailPayload($tuteur, $etudiant, $contactId, $event, $message));
            $this->logResult($event, 'email', $result, $tuteur, $etudiant);
        }

        if (in_array('whatsapp', $channels, true) && $tuteur->telephone) {
            $phone = PhoneNormalizer::toE164((string) $tuteur->telephone);
            if ($phone) {
                $result = $this->client->sendWhatsAppMessage($this->whatsAppPayload($phone, $etudiant, $contactId, $event, $message, $tuteur));
                $this->logResult($event, 'whatsapp', $result, $tuteur, $etudiant);
            }
        }
    }

    private function upsertContact(ESBTPParent $tuteur, ESBTPEtudiant $etudiant): MailPulseResult
    {
        return $this->client->createOrUpdateContact(array_filter([
            'email' => $tuteur->email,
            'phone' => PhoneNormalizer::toE164((string) $tuteur->telephone),
            'first_name' => $tuteur->prenoms ?: $tuteur->nom,
            'last_name' => $tuteur->nom,
            'external_id' => 'klassci-parent-' . $tuteur->id,
            'language' => $this->client->getSetting('mailpulse_default_language', 'default_language', 'fr'),
            'subscribed' => true,
            'metadata' => [
                'parent_id' => $tuteur->id,
                'student_id' => $etudiant->id,
                'student_name' => $this->studentName($etudiant),
                'source' => 'klassci-real-workflow',
            ],
        ], fn ($value) => $value !== null && $value !== ''));
    }

    private function emailPayload(ESBTPParent $tuteur, ESBTPEtudiant $etudiant, string $contactId, string $event, array $message): array
    {
        return [
            'channel' => 'email',
            'recipient' => ['type' => 'email', 'value' => $tuteur->email],
            'content' => [
                'type' => 'text',
                'text' => $message['subject'] . "\n\n" . $this->personalize($message['body'], $tuteur),
            ],
            'metadata' => $this->metadata($event, $etudiant, $contactId, $message),
        ];
    }

    private function whatsAppPayload(string $phone, ESBTPEtudiant $etudiant, string $contactId, string $event, array $message, ESBTPParent $tuteur): array
    {
        return [
            'channel' => 'whatsapp',
            'recipient' => ['type' => 'phone', 'value' => $phone],
            'content' => [
                'type' => 'text',
                'text' => $this->personalize($message['body'], $tuteur),
            ],
            'metadata' => $this->metadata($event, $etudiant, $contactId, $message),
        ];
    }

    private function metadata(string $event, ESBTPEtudiant $etudiant, string $contactId, array $message): array
    {
        return array_filter([
            'source' => 'klassci',
            'workflow_event' => $event,
            'contact_id' => $contactId,
            'student_id' => $etudiant->id,
            'student_name' => $this->studentName($etudiant),
            'class_name' => $etudiant->classe?->name,
            'sender_name' => $this->client->getSetting('mailpulse_sender_name', 'sender_name', 'KLASSCI'),
            'event_summary' => $message['summary'],
        ] + ($message['metadata'] ?? []), fn ($value) => $value !== null && $value !== '');
    }

    private function preferenceType(string $event): string
    {
        return match ($event) {
            'payment_received', 'fee_reminder' => 'paiements',
            'absence_reported' => 'absences',
            'grade_published' => 'notes',
            default => 'annonces',
        };
    }

    private function realWorkflowsEnabled(): bool
    {
        $value = $this->setting('mailpulse_real_workflows_enabled', 'real_workflows_enabled', '0');

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function setting(string $settingKey, string $configKey, string $default = ''): string
    {
        try {
            $value = Setting::get($settingKey, null);
            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        } catch (\Throwable) {
        }

        $configValue = config('services.mailpulse.' . $configKey, $default);

        return is_bool($configValue) ? ($configValue ? '1' : '0') : (string) $configValue;
    }

    private function personalize(string $body, ESBTPParent $tuteur): string
    {
        return str_replace('{parent}', trim($tuteur->nom . ' ' . $tuteur->prenoms), $body);
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

    private function logResult(string $event, string $channel, MailPulseResult $result, ESBTPParent $tuteur, ESBTPEtudiant $etudiant): void
    {
        Log::info('MailPulse workflow notification result', [
            'event' => $event,
            'channel' => $channel,
            'status' => $result->status,
            'request_id' => $result->requestId,
            'http_status' => $result->httpStatus,
            'parent_id' => $tuteur->id,
            'student_id' => $etudiant->id,
        ]);
    }
}
