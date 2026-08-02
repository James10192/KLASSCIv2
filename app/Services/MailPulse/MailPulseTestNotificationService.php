<?php

namespace App\Services\MailPulse;

use App\Domain\Notifications\PhoneNormalizer;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;

class MailPulseTestNotificationService
{
    private const EVENTS = [
        'payment_received',
        'payment_submitted',
        'payment_rejected',
        'absence_reported',
        'grade_published',
        'fee_reminder',
        'registration_confirmed',
        're_registration_confirmed',
        'bulletin_published',
        'low_grades_alert',
        'low_attendance_alert',
    ];

    private const CHANNELS = ['email', 'whatsapp', 'sms', 'both'];

    public function __construct(private MailPulseClient $client) {}

    public function send(string $event, string $channel, bool $dryRun): array
    {
        $this->validateInput($event, $channel);

        $emails = $this->activeEmails();
        $phones = $this->activePhones();
        $primaryEmail = $emails[0] ?? '';
        $primaryPhone = $phones[0] ?? null;
        $shouldEmail = $channel === 'email' || $channel === 'both';
        $shouldWhatsApp = $channel === 'whatsapp' || $channel === 'both';
        $shouldSms = $channel === 'sms';
        $config = [
            'api_key' => $this->client->apiKeyDiagnostics(),
        ];

        if ($shouldEmail && $emails === []) {
            throw ValidationException::withMessages([
                'TEST_NOTIFICATION_EMAIL' => 'Au moins un email de test actif est requis pour éviter tout envoi à de vrais parents.',
            ]);
        }

        if ($shouldWhatsApp && $phones === []) {
            throw ValidationException::withMessages([
                'TEST_NOTIFICATION_PHONE' => 'Au moins un numéro de test WhatsApp ivoirien valide est requis.',
            ]);
        }

        if ($shouldSms && $phones === []) {
            throw ValidationException::withMessages([
                'TEST_NOTIFICATION_PHONE' => 'Au moins un numéro de test SMS ivoirien valide est requis.',
            ]);
        }

        $scenario = $this->scenario($event);
        $contact = $dryRun
            ? MailPulseResult::dryRun()
            : $this->client->createOrUpdateContact($this->contactPayload($primaryEmail, $primaryPhone, $channel, $scenario));
        $contactId = $contact->id ?? 'dry-run-contact';

        $emailResult = MailPulseResult::skipped('skipped', 'Canal email non demandé.');
        $whatsAppResult = MailPulseResult::skipped('skipped', 'Canal WhatsApp non demandé.');

        $smsResult = MailPulseResult::skipped('skipped', 'Canal SMS non demandé.');

        if (! $contact->ok) {
            return [
                'ok' => false,
                'event' => $event,
                'channel' => $channel,
                'dryRun' => $dryRun,
                'contactId' => null,
                'contact' => $contact->toArray(),
                'config' => $config,
                'email' => array_merge(
                    ['attempted' => $shouldEmail],
                    MailPulseResult::skipped('skipped_contact_failed', 'Envoi ignoré car la mise à jour du contact MailPulse a échoué.')->toArray()
                ),
                'whatsapp' => array_merge(
                    ['attempted' => $shouldWhatsApp],
                    MailPulseResult::skipped('skipped_contact_failed', 'Envoi ignoré car la mise à jour du contact MailPulse a échoué.')->toArray()
                ),
                'sms' => array_merge(
                    ['attempted' => $shouldSms],
                    MailPulseResult::skipped('skipped_contact_failed', 'Envoi ignoré car la mise à jour du contact MailPulse a échoué.')->toArray()
                ),
                'preview' => $this->previewPayload($scenario),
            ];
        }

        $emailRecipients = [];
        if ($shouldEmail) {
            $emailResult = $this->sendEmailMessages($emails, $contactId, $scenario, $dryRun, $emailRecipients);
        }

        $whatsAppRecipients = [];
        if ($shouldWhatsApp) {
            $whatsAppResult = $this->sendPhoneMessages($phones, 'whatsapp', $contactId, $scenario, $dryRun, $whatsAppRecipients);
        }

        $smsRecipients = [];
        if ($shouldSms) {
            $smsResult = $this->sendPhoneMessages($phones, 'sms', $contactId, $scenario, $dryRun, $smsRecipients);
        }

        return [
            'ok' => $contact->ok && $emailResult->ok && $whatsAppResult->ok && $smsResult->ok,
            'event' => $event,
            'channel' => $channel,
            'dryRun' => $dryRun,
            'contactId' => $contactId,
            'contact' => $contact->toArray(),
            'config' => $config,
            'email' => array_merge(
                ['attempted' => $shouldEmail],
                $emailResult->toArray(),
                $emailRecipients === [] ? [] : ['recipients' => $emailRecipients]
            ),
            'whatsapp' => array_merge(
                ['attempted' => $shouldWhatsApp],
                $whatsAppResult->toArray(),
                $whatsAppRecipients === [] ? [] : ['recipients' => $whatsAppRecipients]
            ),
            'sms' => array_merge(
                ['attempted' => $shouldSms],
                $smsResult->toArray(),
                $smsRecipients === [] ? [] : ['recipients' => $smsRecipients]
            ),
            'preview' => $this->previewPayload($scenario),
        ];
    }

    private function activeEmails(): array
    {
        $recipients = $this->parseRecipients(
            $this->client->getSetting('mailpulse_test_email_recipients', 'mailpulse_test_email_recipients', '')
        );

        if ($recipients === []) {
            $legacyEmail = trim($this->client->getSetting('mailpulse_test_email', 'test_notification_email', ''));
            if ($legacyEmail !== '') {
                $recipients[] = ['value' => $legacyEmail, 'enabled' => true];
            }
        }

        $emails = [];
        foreach ($recipients as $recipient) {
            $email = trim((string) ($recipient['value'] ?? ''));
            if (($recipient['enabled'] ?? true) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[strtolower($email)] = $email;
            }
        }

        return array_values($emails);
    }

    private function activePhones(): array
    {
        $recipients = $this->parseRecipients(
            $this->client->getSetting('mailpulse_test_phone_recipients', 'mailpulse_test_phone_recipients', '')
        );

        if ($recipients === []) {
            $rawList = $this->client->getSetting('mailpulse_test_phones', 'test_notification_phones', '');
            if ($rawList === '') {
                $rawList = $this->client->getSetting('mailpulse_test_phone', 'test_notification_phone', '');
            }

            foreach (preg_split('/[\r\n,;]+/', $rawList) ?: [] as $item) {
                $recipients[] = ['value' => $item, 'enabled' => true];
            }
        }

        $phones = [];
        foreach ($recipients as $recipient) {
            $phone = PhoneNormalizer::toE164((string) ($recipient['value'] ?? ''));
            if (($recipient['enabled'] ?? true) && $phone !== null) {
                $phones[$phone] = $phone;
            }
        }

        return array_values($phones);
    }

    private function parseRecipients(string $json): array
    {
        if (trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, fn ($item) => is_array($item)));
    }

    private function sendEmailMessages(array $emails, string $contactId, array $scenario, bool $dryRun, array &$recipients): MailPulseResult
    {
        $result = MailPulseResult::dryRun();

        foreach ($emails as $email) {
            $current = $dryRun
                ? MailPulseResult::dryRun()
                : $this->client->sendEmailMessage($this->emailPayload($email, $contactId, $scenario));

            $recipients[] = array_merge(['email' => $email], $current->toArray());

            if (! $current->ok) {
                return $current;
            }

            $result = $current;
        }

        return $result;
    }

    private function sendPhoneMessages(array $phones, string $channel, string $contactId, array $scenario, bool $dryRun, array &$recipients): MailPulseResult
    {
        $result = MailPulseResult::dryRun();

        foreach ($phones as $phone) {
            $payload = $this->phonePayload($channel, $phone, $contactId, $scenario);
            $current = $dryRun ? MailPulseResult::dryRun() : match ($channel) {
                'sms' => $this->client->sendSmsMessage($payload),
                default => $this->client->sendWhatsAppMessage($payload),
            };

            $recipients[] = array_merge(['phone' => $phone], $current->toArray());

            if (! $current->ok) {
                return $current;
            }

            $result = $current;
        }

        return $result;
    }

    private function validateInput(string $event, string $channel): void
    {
        $errors = [];

        if (! in_array($event, self::EVENTS, true)) {
            $errors['event'] = 'Événement invalide. Valeurs: ' . implode(', ', self::EVENTS) . '.';
        }

        if (! in_array($channel, self::CHANNELS, true)) {
            $errors['channel'] = 'Canal invalide. Valeurs: ' . implode(', ', self::CHANNELS) . '.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function scenario(string $event): array
    {
        $base = [
            'event' => $event,
            'parent_name' => 'Parent Test KLASSCI',
            'student_name' => 'Awa Kouadio',
            'class_name' => 'Licence 2 Gestion',
            'school_name' => 'KLASSCI Demo',
            'school_phone' => '+225 27 00 00 00 00',
            'school_email' => 'scolarite@test.klassci.com',
            'academic_year' => '2025-2026',
        ];

        return match ($event) {
            'payment_received' => $base + [
                'subject' => 'Paiement validé',
                'summary' => 'Paiement validé pour Awa Kouadio.',
                'intro' => "Le paiement de Awa Kouadio a été validé par l'administration.",
                'body' => 'Bonjour Parent Test KLASSCI, le paiement de 150 000 FCFA pour Awa Kouadio a été validé. Référence: PAY-TEST-2026-001. Reçu: REC-TEST-2026-001. Reste à payer: 75 000 FCFA.',
                'details' => [
                    'Étudiant' => 'Awa Kouadio',
                    'Classe' => 'Licence 2 Gestion',
                    'Montant payé' => '150 000 FCFA',
                    'Référence' => 'PAY-TEST-2026-001',
                    'Numéro de reçu' => 'REC-TEST-2026-001',
                    'Mode de paiement' => 'Espèces',
                    'Reste à payer' => '75 000 FCFA',
                ],
            ],
            'payment_submitted' => $base + [
                'subject' => 'Paiement en attente',
                'summary' => 'Paiement soumis en attente de validation.',
                'intro' => "Le paiement de Awa Kouadio a ete enregistre et attend la validation de l'administration.",
                'body' => 'Bonjour Parent Test KLASSCI, le paiement de 150 000 FCFA pour Awa Kouadio est en attente de validation. Reference: PAY-TEST-2026-002.',
                'details' => [
                    'Etudiant' => 'Awa Kouadio',
                    'Classe' => 'Licence 2 Gestion',
                    'Montant' => '150 000 FCFA',
                    'Reference' => 'PAY-TEST-2026-002',
                    'Mode de paiement' => 'Mobile Money',
                    'Date de soumission' => '05/07/2026',
                ],
            ],
            'payment_rejected' => $base + [
                'subject' => 'Paiement rejete',
                'summary' => 'Paiement rejete pour piece non conforme.',
                'intro' => "Le paiement de Awa Kouadio a ete rejete par l'administration.",
                'body' => 'Bonjour Parent Test KLASSCI, le paiement de 150 000 FCFA pour Awa Kouadio a ete rejete. Motif: justificatif illisible.',
                'details' => [
                    'Etudiant' => 'Awa Kouadio',
                    'Classe' => 'Licence 2 Gestion',
                    'Montant' => '150 000 FCFA',
                    'Reference' => 'PAY-TEST-2026-003',
                    'Motif' => 'Justificatif illisible',
                    'Date de rejet' => '05/07/2026',
                ],
            ],
            'absence_reported' => $base + [
                'subject' => 'Notification d absence',
                'summary' => 'Absence signalée en Comptabilité générale.',
                'intro' => 'Votre enfant Awa Kouadio a été marqué absent en cours.',
                'body' => 'Bonjour Parent Test KLASSCI, une absence a été signalée pour Awa Kouadio le 02/07/2026 en Comptabilité générale, de 08:00 à 10:00. Total du mois: 2 absences.',
                'details' => [
                    'Étudiant' => 'Awa Kouadio',
                    'Classe' => 'Licence 2 Gestion',
                    'Date' => '02/07/2026',
                    'Heure' => '08:00 - 10:00',
                    'Matière' => 'Comptabilité générale',
                    'Absences du mois' => '2',
                ],
            ],
            'grade_published' => $base + [
                'subject' => 'Nouvelle note disponible',
                'summary' => 'Nouvelle note disponible.',
                'intro' => 'Une nouvelle note a été publiée pour Awa Kouadio.',
                'body' => 'Bonjour Parent Test KLASSCI, une note de 15/20 en Droit des affaires vient d être publiée pour Awa Kouadio. Évaluation: Contrôle continu S2.',
                'details' => [
                    'Étudiant' => 'Awa Kouadio',
                    'Matière' => 'Droit des affaires',
                    'Évaluation' => 'Contrôle continu S2',
                    'Note obtenue' => '15/20',
                    'Moyenne de classe' => '12,50/20',
                ],
            ],
            'registration_confirmed' => $base + [
                'subject' => 'Inscription confirmee',
                'summary' => 'Inscription confirmee pour Awa Kouadio.',
                'intro' => "L'inscription de Awa Kouadio a ete confirmee pour l'annee universitaire 2025-2026.",
                'body' => 'Bonjour Parent Test KLASSCI, inscription confirmee pour Awa Kouadio en Licence 2 Gestion. Identifiant de test: parent.test.',
                'details' => [
                    'Etudiant' => 'Awa Kouadio',
                    'Matricule' => 'KLASSCI-TEST-001',
                    'Classe' => 'Licence 2 Gestion',
                    'Filiere' => 'Gestion',
                    'Annee universitaire' => '2025-2026',
                    'Date inscription' => '05/07/2026',
                ],
            ],
            're_registration_confirmed' => $base + [
                'subject' => 'Reinscription confirmee',
                'summary' => 'Reinscription confirmee pour Awa Kouadio.',
                'intro' => "La reinscription de Awa Kouadio a ete confirmee pour l'annee universitaire 2025-2026.",
                'body' => 'Bonjour Parent Test KLASSCI, reinscription confirmee pour Awa Kouadio en Licence 3 Gestion. Decision: passage.',
                'details' => [
                    'Etudiant' => 'Awa Kouadio',
                    'Matricule' => 'KLASSCI-TEST-001',
                    'Nouvelle classe' => 'Licence 3 Gestion',
                    'Filiere' => 'Gestion',
                    'Decision' => 'Passage',
                    'Date reinscription' => '05/07/2026',
                ],
            ],
            'bulletin_published' => $base + [
                'subject' => 'Bulletin disponible',
                'summary' => 'Bulletin disponible pour le semestre 2.',
                'intro' => 'Le bulletin de Awa Kouadio pour le semestre 2 est disponible.',
                'body' => 'Bonjour Parent Test KLASSCI, le bulletin semestre 2 de Awa Kouadio est disponible. Moyenne generale: 13,75/20, rang: 8/42.',
                'details' => [
                    'Etudiant' => 'Awa Kouadio',
                    'Classe' => 'Licence 2 Gestion',
                    'Periode' => 'Semestre 2',
                    'Moyenne generale' => '13,75/20',
                    'Rang' => '8/42',
                    'Mention' => 'Assez bien',
                ],
            ],
            'low_grades_alert' => $base + [
                'subject' => 'Alerte performance academique',
                'summary' => 'Resultats academiques sous le seuil.',
                'intro' => 'Les resultats de Awa Kouadio sont en dessous du seuil attendu sur plusieurs matieres.',
                'body' => 'Bonjour Parent Test KLASSCI, alerte academique pour Awa Kouadio. Moyenne generale: 8,75/20. Une rencontre pedagogique est recommandee.',
                'details' => [
                    'Etudiant' => 'Awa Kouadio',
                    'Classe' => 'Licence 2 Gestion',
                    'Periode' => 'Semestre 2',
                    'Moyenne generale' => '8,75/20',
                    'Rang' => '38/42',
                    'Decision' => 'Suivi pedagogique recommande',
                ],
            ],
            'low_attendance_alert' => $base + [
                'subject' => 'Alerte presence faible',
                'summary' => 'Taux de presence sous le seuil recommande.',
                'intro' => 'Le taux de presence de Awa Kouadio est sous le seuil recommande.',
                'body' => 'Bonjour Parent Test KLASSCI, le taux de presence de Awa Kouadio est de 64%. Total absences: 28h, dont 18h non justifiees.',
                'details' => [
                    'Etudiant' => 'Awa Kouadio',
                    'Classe' => 'Licence 2 Gestion',
                    'Periode' => 'Mois en cours',
                    'Taux de presence' => '64%',
                    'Total absences' => '28h',
                    'Absences non justifiees' => '18h',
                ],
            ],
            'fee_reminder' => $base + [
                'subject' => 'Rappel de paiement',
                'summary' => 'Rappel de frais restant dus.',
                'intro' => "Ce message vous rappelle qu'un montant reste dû pour les frais de scolarité de Awa Kouadio.",
                'body' => 'Bonjour Parent Test KLASSCI, un solde de 75 000 FCFA reste dû pour Awa Kouadio en Licence 2 Gestion. Échéance recommandée: 10/07/2026.',
                'details' => [
                    'Étudiant' => 'Awa Kouadio',
                    'Classe' => 'Licence 2 Gestion',
                    'Année universitaire' => '2025-2026',
                    'Montant total' => '225 000 FCFA',
                    'Montant payé' => '150 000 FCFA',
                    'Reste à payer' => '75 000 FCFA',
                    'Échéance' => '10/07/2026',
                ],
            ],
        };
    }

    private function contactPayload(string $email, ?string $phone, string $channel, array $scenario): array
    {
        return array_filter([
            'email' => $email,
            'phone' => $phone,
            'first_name' => 'Parent',
            'last_name' => 'Test KLASSCI',
            'external_id' => MailPulseTenantContext::scopedIdentifier('test-parent'),
            'language' => $this->client->getSetting('mailpulse_default_language', 'default_language', 'fr'),
            'preferred_channel' => $this->preferredChannel($channel, $phone),
            'subscribed' => true,
            'metadata' => [
                'source' => 'klassci-test-notification',
                'tenant_code' => MailPulseTenantContext::code(),
                'environment' => 'test',
            ],
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function preferredChannel(string $channel, ?string $phone): string
    {
        if ($channel === 'sms') {
            return 'sms';
        }

        return $phone && in_array($channel, ['whatsapp', 'both'], true) ? 'whatsapp' : 'email';
    }

    private function emailPayload(string $email, string $contactId, array $scenario): array
    {
        return [
            'channel' => 'email',
            'recipient' => [
                'type' => 'email',
                'value' => $email,
            ],
            'content' => [
                'type' => 'text',
                'text' => $this->emailText($scenario),
            ],
            'metadata' => [
                'source' => 'klassci',
                'contact_id' => $contactId,
                'workflow_event' => $scenario['event'],
                'environment' => 'test',
            ],
        ];
    }

    private function phonePayload(string $channel, string $phone, string $contactId, array $scenario): array
    {
        return [
            'channel' => $channel,
            'recipient' => [
                'type' => 'phone',
                'value' => $phone,
            ],
            'content' => [
                'type' => 'text',
                'text' => $this->shortText($scenario),
            ],
            'metadata' => [
                'source' => 'klassci',
                'contact_id' => $contactId,
                'workflow_event' => $scenario['event'],
                'environment' => 'test',
            ],
        ];
    }

    private function previewPayload(array $scenario): array
    {
        return [
            'subject' => '[TEST KLASSCI] ' . $scenario['subject'],
            'email_text' => $this->emailText($scenario),
            'email_html' => $this->emailHtml($scenario),
            'whatsapp_text' => $this->shortText($scenario),
            'sms_text' => $this->shortText($scenario),
        ];
    }

    private function emailHtml(array $scenario): string
    {
        $view = match ($scenario['event']) {
            'payment_received' => 'esbtp.emails.parents.paiement-valide',
            'payment_submitted' => 'esbtp.emails.parents.paiement-created',
            'payment_rejected' => 'esbtp.emails.parents.paiement-rejete',
            'absence_reported' => 'esbtp.emails.parents.absence-notification',
            'grade_published' => 'esbtp.emails.parents.note-published',
            'fee_reminder' => 'esbtp.emails.parents.paiement-relance',
            'registration_confirmed' => 'esbtp.emails.parents.inscription-confirmation',
            're_registration_confirmed' => 'esbtp.emails.parents.reinscription-confirmation',
            'bulletin_published' => 'esbtp.emails.parents.bulletin-published',
            'low_grades_alert' => 'esbtp.emails.parents.low-grades',
            'low_attendance_alert' => 'esbtp.emails.parents.low-attendance',
        };

        return View::make($view, $this->emailViewData($scenario))->render();
    }

    private function emailViewData(array $scenario): array
    {
        $school = $this->schoolIdentity();
        $base = [
            'parentName' => $scenario['parent_name'],
            'studentName' => $scenario['student_name'],
            'classe' => $scenario['class_name'],
            'schoolName' => $school['name'],
            'schoolAddress' => $school['address'],
            'schoolPhone' => $school['phone'],
            'schoolEmail' => $school['email'],
            'schoolLogoPath' => $school['logo'],
            'emailPrimaryColor' => SettingsHelper::get('pdf_primary_color', '#0453cb'),
            'emailHeaderBgColor' => SettingsHelper::get('pdf_header_bg_color', SettingsHelper::get('pdf_primary_color', '#0453cb')),
            'emailHeaderTextColor' => SettingsHelper::get('pdf_header_text_color', '#ffffff'),
            'emailSecondaryColor' => SettingsHelper::get('pdf_secondary_color', '#64748b'),
            'message' => new class {
                public function embed(string $path): string
                {
                    return $path;
                }
            },
        ];

        return $base + match ($scenario['event']) {
            'payment_received' => [
                'montant' => 150000,
                'reference' => 'PAY-TEST-2026-001',
                'numeroRecu' => 'REC-TEST-2026-001',
                'modePaiement' => 'Espèces',
                'datePaiement' => '05/07/2026',
                'dateValidation' => '05/07/2026',
                'validePar' => 'Test KLASSCI',
                'montantTotal' => 225000,
                'montantPaye' => 150000,
                'resteDu' => 75000,
                'pourcentagePaye' => 67,
                'recuUrl' => '#',
            ],
            'payment_submitted' => [
                'montant' => 150000,
                'reference' => 'PAY-TEST-2026-002',
                'modePaiement' => 'Mobile Money',
                'dateSoumission' => '05/07/2026',
                'suiviUrl' => '#',
            ],
            'payment_rejected' => [
                'montant' => 150000,
                'reference' => 'PAY-TEST-2026-003',
                'dateSoumission' => '04/07/2026',
                'dateRejet' => '05/07/2026',
                'motifRejet' => 'Justificatif de paiement illisible.',
                'paiementUrl' => '#',
            ],
            'absence_reported' => [
                'date' => '02/07/2026',
                'heureDebut' => '08:00',
                'heureFin' => '10:00',
                'matiere' => 'Comptabilité générale',
                'typeActivite' => 'Cours',
                'commentaire' => 'Test MailPulse KLASSCI',
                'periodeStats' => 'mois en cours',
                'absencesJustifiees' => 0,
                'absencesNonJustifiees' => 2,
                'totalAbsences' => 4,
                'tauxPresence' => 92,
                'justificationUrl' => '#',
            ],
            'grade_published' => [
                'matiere' => 'Droit des affaires',
                'typeEvaluation' => 'Contrôle continu S2',
                'dateEvaluation' => '02/07/2026',
                'note' => 15,
                'bareme' => 20,
                'moyenneClasse' => 12.5,
                'rang' => 3,
                'effectifClasse' => 42,
                'appreciation' => 'Bon résultat.',
                'noteUrl' => '#',
            ],
            'registration_confirmed' => [
                'matricule' => 'KLASSCI-TEST-001',
                'filiere' => 'Gestion',
                'niveauEtude' => 'Licence 2',
                'anneeUniversitaire' => $scenario['academic_year'],
                'dateInscription' => '05/07/2026',
                'username' => 'parent.test',
                'password' => 'Test-2026',
                'platformUrl' => '#',
                'montantTotal' => 225000,
                'montantPaye' => 150000,
                'montantDu' => 75000,
            ],
            're_registration_confirmed' => [
                'matricule' => 'KLASSCI-TEST-001',
                'classe' => 'Licence 3 Gestion',
                'filiere' => 'Gestion',
                'niveauEtude' => 'Licence 3',
                'anneeUniversitaire' => $scenario['academic_year'],
                'dateReinscription' => '05/07/2026',
                'decision' => 'passage',
                'platformUrl' => '#',
                'reliquatMontant' => 25000,
            ],
            'bulletin_published' => [
                'periode' => 'Semestre 2',
                'anneeUniversitaire' => $scenario['academic_year'],
                'moyenneGenerale' => 13.75,
                'rang' => 8,
                'effectifClasse' => 42,
                'totalAbsences' => 6,
                'noteAssiduite' => 1.5,
                'mention' => 'Assez bien',
                'mentionColor' => 'success',
                'appreciationGenerale' => 'Travail regulier, progression encourageante.',
                'decision' => 'Admis',
                'bulletinUrl' => '#',
                'requiresSignature' => true,
            ],
            'low_grades_alert' => [
                'periode' => 'Semestre 2',
                'moyenneGenerale' => 8.75,
                'rang' => 38,
                'effectifClasse' => 42,
                'decision' => 'Suivi pedagogique recommande',
                'matieresEnDifficulte' => [
                    ['nom' => 'Comptabilite generale', 'moyenne' => 7.5, 'coefficient' => 3],
                    ['nom' => 'Droit des affaires', 'moyenne' => 8.0, 'coefficient' => 2],
                ],
                'tauxPresence' => 72,
                'coursDisponibles' => true,
                'bulletinUrl' => '#',
                'contactUrl' => '#',
            ],
            'low_attendance_alert' => [
                'periode' => 'Mois en cours',
                'tauxPresence' => 64,
                'totalAbsences' => 28,
                'absencesNonJustifiees' => 18,
                'absencesUrl' => '#',
            ],
            'fee_reminder' => [
                'anneeUniversitaire' => $scenario['academic_year'],
                'montantTotal' => 225000,
                'montantPaye' => 150000,
                'montantDu' => 75000,
                'pourcentagePaye' => 67,
                'echeance' => '10/07/2026',
                'joursRestants' => 5,
                'historiqueRelances' => 1,
                'paiementUrl' => '#',
                'modesPaiement' => ['Espèces', 'Mobile Money', 'Virement'],
            ],
        };
    }

    private function schoolIdentity(): array
    {
        $logo = (string) SettingsHelper::get('school_logo', '');
        $logoUrl = '';
        if ($logo !== '') {
            $logoUrl = str_starts_with($logo, 'http')
                ? $logo
                : asset('storage/' . ltrim($logo, '/'));
        }

        return [
            'name' => SettingsHelper::get('school_name', 'KLASSCI'),
            'address' => SettingsHelper::get('school_address', ''),
            'phone' => SettingsHelper::get('school_phone', ''),
            'email' => SettingsHelper::get('school_email', ''),
            'logo' => $logoUrl,
        ];
    }

    private function emailText(array $scenario): string
    {
        $lines = [
            '[TEST KLASSCI] ' . $scenario['subject'],
            '',
            'Bonjour ' . $scenario['parent_name'] . ',',
            '',
            $scenario['intro'],
            '',
            'Détails',
        ];

        foreach (($scenario['details'] ?? []) as $label => $value) {
            $lines[] = $label . ': ' . $value;
        }

        $lines[] = '';
        $lines[] = 'Ce message est un test envoyé depuis KLASSCI vers un destinataire configuré dans MailPulse.';
        $lines[] = 'Aucun parent réel ne reçoit ce test.';
        $lines[] = '';
        $lines[] = $scenario['school_name'];
        $lines[] = $scenario['school_phone'];
        $lines[] = $scenario['school_email'];

        return implode("\n", $lines);
    }

    private function shortText(array $scenario): string
    {
        return '[TEST KLASSCI] ' . $scenario['body'];
    }
}
