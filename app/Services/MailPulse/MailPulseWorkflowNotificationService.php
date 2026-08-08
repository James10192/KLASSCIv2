<?php

namespace App\Services\MailPulse;

use App\Domain\Notifications\PhoneNormalizer;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPParent;
use App\Models\ESBTPNote;
use App\Services\ParentChatbot\ParentChatbotPublicationPolicy;
use Illuminate\Support\Facades\Log;

class MailPulseWorkflowNotificationService
{

    public function __construct(
        private MailPulseClient $client,
        private MailPulseParentNotificationLog $notificationLogs,
        private MailPulseWorkflowPolicy $workflowPolicy,
        private ParentChatbotPublicationPolicy $publicationPolicy,
    ) {}

    public function notifyPaymentReceived(ESBTPPaiement $paiement): void
    {
        if ($paiement->status !== 'validé' || $paiement->date_validation === null) {
            return;
        }

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
                    'fee_category_id' => $paiement->frais_category_id,
                    'validation_occurrence' => $this->paymentValidationOccurrence($paiement),
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
                    'attendance_occurrence' => $this->attendanceOccurrence($attendance),
                    'student_id' => $etudiant->id,
                    'matiere_id' => $attendance->matiere_id,
                ],
            ]
        );
    }

    public function notifyGradePublished(ESBTPNote $note): void
    {
        $note->loadMissing(['etudiant.parents', 'evaluation', 'matiere']);
        $etudiant = $note->etudiant;
        if (! $etudiant || $note->is_absent || ! $this->publicationPolicy->gradeIsPublished($note)) {
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
                    'matiere_id' => $note->matiere_id ?? $note->evaluation?->matiere_id,
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
                    'inscription_id' => $paiement->inscription_id,
                    'fee_category_id' => $paiement->frais_category_id,
                    'reminder_count' => $reminderCount,
                ],
            ]
        );
    }

    public function notifyBulletinPublished(ESBTPBulletin $bulletin): void
    {
        $bulletin->loadMissing(['etudiant.parents', 'classe', 'anneeUniversitaire']);

        $etudiant = $bulletin->etudiant;
        if (! $etudiant || ! $this->publicationPolicy->reportCardIsPublished($bulletin)) {
            return;
        }

        $className = $bulletin->classe?->nom ?? $bulletin->classe?->name ?? 'Classe non renseignee';
        $average = $bulletin->moyenne_generale === null
            ? 'non renseignee'
            : $this->formatNumber((float) $bulletin->moyenne_generale) . '/20';
        $rank = $bulletin->rang && $bulletin->effectif_classe
            ? $bulletin->rang . '/' . $bulletin->effectif_classe
            : 'non renseigne';

        $this->notifyTutor(
            'bulletin_published',
            $etudiant,
            [
                'subject' => 'Bulletin disponible',
                'summary' => 'Bulletin publie.',
                'body' => sprintf(
                    'Bonjour {parent}, le bulletin de %s pour %s (%s) est disponible. Moyenne generale : %s. Rang : %s. Consultez le detail officiel depuis votre compte KLASSCI.',
                    $this->studentName($etudiant),
                    $bulletin->periode ?: 'la periode en cours',
                    $className,
                    $average,
                    $rank,
                ),
                'metadata' => [
                    'bulletin_id' => $bulletin->id,
                    'classe_id' => $bulletin->classe_id,
                    'annee_universitaire_id' => $bulletin->annee_universitaire_id,
                ],
            ]
        );
    }

    /**
     * Shared emission path: kill switch, parent preferences, per channel
     * consent, outbox and MailPulse call. Public so the workflow events that
     * live in MailPulseExtraWorkflowNotifications reuse the exact same gates.
     */
    public function notifyTutor(string $event, ESBTPEtudiant $etudiant, array $message): void
    {
        if (! $this->workflowPolicy->realWorkflowsEnabled()) {
            return;
        }

        $tuteur = $etudiant->tuteur;
        if (! $tuteur) {
            return;
        }

        $preferences = $tuteur->getOrCreateNotificationPreferences();
        if (! $preferences->isNotificationEnabled($this->workflowPolicy->preferenceType($event))) {
            return;
        }

        $channels = array_values(array_filter(
            $preferences->preferred_channels ?? ['email'],
            fn (string $channel): bool => $this->workflowPolicy->parentAllows($tuteur, $event, $channel)
        ));
        if ($channels === []) {
            return;
        }

        $contact = $this->upsertContact($tuteur, $etudiant);
        if (! $contact->ok) {
            $this->logResult($event, 'contact', $contact, $tuteur, $etudiant);
            return;
        }

        $contactId = $contact->id ?? MailPulseTenantContext::scopedIdentifier('parent-' . $tuteur->id);
        if (in_array('email', $channels, true) && $tuteur->email) {
            $this->dispatchMessage(
                $event,
                'email',
                $this->emailPayload($tuteur, $etudiant, $contactId, $event, $message),
                $tuteur,
                $etudiant,
                $message
            );
        }

        $phone = PhoneNormalizer::toE164((string) $tuteur->telephone);
        if (! $phone) {
            return;
        }

        $whatsAppEnabled = in_array('whatsapp', $channels, true);
        $smsEnabled = in_array('sms', $channels, true);

        if ($whatsAppEnabled) {
            $whatsAppResult = $this->dispatchMessage(
                $event,
                'whatsapp',
                $this->whatsAppPayload($phone, $etudiant, $contactId, $event, $message, $tuteur),
                $tuteur,
                $etudiant,
                $message
            );

            // SMS is an opt-in fallback for a parent whose WhatsApp channel is not active.
            if ($smsEnabled && $this->shouldFallBackToSms($whatsAppResult)) {
                $this->dispatchMessage(
                    $event,
                    'sms',
                    $this->smsPayload($phone, $etudiant, $contactId, $event, $message, $tuteur, true),
                    $tuteur,
                    $etudiant,
                    $message
                );
            }

            return;
        }

        if ($smsEnabled) {
            $this->dispatchMessage(
                $event,
                'sms',
                $this->smsPayload($phone, $etudiant, $contactId, $event, $message, $tuteur),
                $tuteur,
                $etudiant,
                $message
            );
        }
    }

    private function upsertContact(ESBTPParent $tuteur, ESBTPEtudiant $etudiant): MailPulseResult
    {
        return $this->client->createOrUpdateContact(array_filter([
            'email' => $tuteur->email,
            'phone' => PhoneNormalizer::toE164((string) $tuteur->telephone),
            'first_name' => $tuteur->prenoms ?: $tuteur->nom,
            'last_name' => $tuteur->nom,
            'external_id' => MailPulseTenantContext::scopedIdentifier('parent-' . $tuteur->id),
            'language' => $this->client->getSetting('mailpulse_default_language', 'default_language', 'fr'),
            'metadata' => [
                'parent_id' => $tuteur->id,
                'student_id' => $etudiant->id,
                'source' => 'klassci-real-workflow',
                'tenant_code' => MailPulseTenantContext::code(),
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

    private function smsPayload(
        string $phone,
        ESBTPEtudiant $etudiant,
        string $contactId,
        string $event,
        array $message,
        ESBTPParent $tuteur,
        bool $isFallback = false
    ): array
    {
        $metadata = $this->metadata($event, $etudiant, $contactId, $message);
        if ($isFallback) {
            $metadata['fallback_from'] = 'whatsapp';
        }

        return [
            'channel' => 'sms',
            'recipient' => ['type' => 'phone', 'value' => $phone],
            'content' => [
                'type' => 'text',
                'text' => $this->personalize($message['body'], $tuteur),
            ],
            'metadata' => $metadata,
        ];
    }

    private function metadata(string $event, ESBTPEtudiant $etudiant, string $contactId, array $message): array
    {
        return array_filter([
            'source' => 'klassci',
            'tenant_code' => MailPulseTenantContext::code(),
            'workflow_event' => $event,
            'contact_id' => $contactId,
            'student_id' => $etudiant->id,
        ] + ($message['metadata'] ?? []), fn ($value) => $value !== null && $value !== '');
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

    private function paymentValidationOccurrence(ESBTPPaiement $paiement): string
    {
        return implode(':', [
            (string) $paiement->getKey(),
            $paiement->date_validation->copy()->utc()->format('Y-m-d\TH:i:s.u\Z'),
            (string) ($paiement->validateur_id ?? 'system'),
        ]);
    }

    private function attendanceOccurrence(ESBTPAttendance $attendance): string
    {
        if ($attendance->exists && $attendance->getKey() !== null) {
            return 'attendance:'.$attendance->getKey();
        }

        $date = $attendance->date?->format('Y-m-d') ?? 'unknown-date';

        return implode(':', [
            'session',
            (string) ($attendance->getAttribute('seance_cours_id') ?? 'unknown'),
            (string) $attendance->etudiant_id,
            $date,
            (string) ($attendance->heure_debut ?? 'unknown-start'),
            (string) ($attendance->heure_fin ?? 'unknown-end'),
        ]);
    }

    private function dispatchMessage(
        string $event,
        string $channel,
        array $payload,
        ESBTPParent $tuteur,
        ESBTPEtudiant $etudiant,
        array $message
    ): MailPulseResult {
        $requestId = $this->notificationLogs->requestId($event, $channel, $tuteur, $etudiant, $message);
        [$notificationLog, $alreadyRecorded, $persisted] = $this->notificationLogs->start(
            $requestId,
            $event,
            $channel,
            $payload,
            $tuteur,
            $etudiant,
            $message
        );

        if (! $persisted) {
            return MailPulseResult::skipped('outbox_unavailable', 'The notification was not sent because its outbox record could not be stored.');
        }

        if ($alreadyRecorded) {
            return MailPulseResult::skipped('already_recorded', 'Cette notification est déjà suivie par l’outbox MailPulse.');
        }

        $gate = $this->workflowPolicy->dispatchIfAllowed(
            $tuteur,
            (int) $etudiant->id,
            $event,
            $channel,
            $payload,
            fn (): MailPulseResult => match ($channel) {
                'email' => $this->client->sendEmailMessage($payload, $requestId),
                'whatsapp' => $this->client->sendWhatsAppMessage($payload, $requestId),
                'sms' => $this->client->sendSmsMessage($payload, $requestId),
            },
        );
        $result = ! $gate['allowed'] || ! $gate['publication_eligible']
            ? MailPulseResult::skipped(
                $gate['publication_eligible'] ? 'messaging_consent_stopped' : 'academic_content_unpublished',
                'The notification is no longer authorized at dispatch time.',
            )
            : $gate['result'];

        $this->notificationLogs->finish($notificationLog, $result, $requestId);
        $this->logResult($event, $channel, $result, $tuteur, $etudiant);

        if ($result->isDispatchAccepted()) {
            $this->incrementNotificationCount($tuteur);
        }

        return $result;
    }

    private function shouldFallBackToSms(MailPulseResult $result): bool
    {
        return $result->dispatchState === 'failed' && $result->smsFallbackEligible;
    }

    private function incrementNotificationCount(ESBTPParent $tuteur): void
    {
        try {
            $tuteur->getOrCreateNotificationPreferences()->incrementNotificationCount();
        } catch (\Throwable $e) {
            Log::warning('MailPulse notification count could not be updated', [
                'parent_id' => $tuteur->id,
                'error' => $e->getMessage(),
            ]);
        }
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
