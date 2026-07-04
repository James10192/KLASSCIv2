<?php

namespace App\Services\MailPulse;

use App\Domain\Notifications\PhoneNormalizer;
use Illuminate\Validation\ValidationException;

class MailPulseTestNotificationService
{
    private const EVENTS = [
        'payment_received',
        'absence_reported',
        'grade_published',
        'fee_reminder',
    ];

    private const CHANNELS = ['email', 'whatsapp', 'both'];

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

        $scenario = $this->scenario($event);
        $contact = $dryRun
            ? MailPulseResult::dryRun()
            : $this->client->createOrUpdateContact($this->contactPayload($primaryEmail, $primaryPhone, $scenario));
        $contactId = $contact->id ?? 'dry-run-contact';

        $emailResult = MailPulseResult::skipped('skipped', 'Canal email non demandé.');
        $whatsAppResult = MailPulseResult::skipped('skipped', 'Canal WhatsApp non demandé.');

        if (! $contact->ok) {
            return [
                'ok' => false,
                'event' => $event,
                'channel' => $channel,
                'dryRun' => $dryRun,
                'contactId' => null,
                'contact' => $contact->toArray(),
                'email' => array_merge(
                    ['attempted' => $shouldEmail],
                    MailPulseResult::skipped('skipped_contact_failed', 'Envoi ignoré car l’upsert contact MailPulse a échoué.')->toArray()
                ),
                'whatsapp' => array_merge(
                    ['attempted' => $shouldWhatsApp],
                    MailPulseResult::skipped('skipped_contact_failed', 'Envoi ignoré car l’upsert contact MailPulse a échoué.')->toArray()
                ),
            ];
        }

        $emailRecipients = [];
        if ($shouldEmail) {
            $emailResult = $this->sendEmailMessages($emails, $contactId, $scenario, $dryRun, $emailRecipients);
        }

        $whatsAppRecipients = [];
        if ($shouldWhatsApp) {
            $whatsAppResult = $this->sendWhatsAppMessages($phones, $contactId, $scenario, $dryRun, $whatsAppRecipients);
        }

        return [
            'ok' => $contact->ok && $emailResult->ok && $whatsAppResult->ok,
            'event' => $event,
            'channel' => $channel,
            'dryRun' => $dryRun,
            'contactId' => $contactId,
            'contact' => $contact->toArray(),
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

    private function sendWhatsAppMessages(array $phones, string $contactId, array $scenario, bool $dryRun, array &$recipients): MailPulseResult
    {
        $result = MailPulseResult::dryRun();

        foreach ($phones as $phone) {
            $current = $dryRun
                ? MailPulseResult::dryRun()
                : $this->client->sendWhatsAppMessage($this->whatsAppPayload($phone, $contactId, $scenario));

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
            $errors['event'] = 'Evenement invalide. Valeurs: ' . implode(', ', self::EVENTS) . '.';
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
            'parent_name' => 'Parent Test KLASSCI',
            'student_name' => 'Awa Kouadio',
            'class_name' => 'Licence 2 Gestion',
            'school_name' => 'KLASSCI Demo',
        ];

        return match ($event) {
            'payment_received' => $base + [
                'subject' => 'Recu de paiement KLASSCI',
                'summary' => 'Paiement recu pour Awa Kouadio.',
                'body' => 'Bonjour Parent Test KLASSCI, nous confirmons la reception du paiement de 150 000 FCFA pour Awa Kouadio en Licence 2 Gestion. Reference: PAY-TEST-2026-001. Solde restant: 75 000 FCFA.',
            ],
            'absence_reported' => $base + [
                'subject' => 'Absence signalee',
                'summary' => 'Absence signalee en Comptabilite generale.',
                'body' => 'Bonjour Parent Test KLASSCI, une absence a ete signalee pour Awa Kouadio le 02/07/2026 au cours de Comptabilite generale. Total du mois: 2 absences.',
            ],
            'grade_published' => $base + [
                'subject' => 'Note publiee',
                'summary' => 'Nouvelle note disponible.',
                'body' => 'Bonjour Parent Test KLASSCI, une note de 15/20 en Droit des affaires vient d etre publiee pour Awa Kouadio. Evaluation: Controle continu S2.',
            ],
            'fee_reminder' => $base + [
                'subject' => 'Rappel de frais impayes',
                'summary' => 'Rappel de frais restant dus.',
                'body' => 'Bonjour Parent Test KLASSCI, un solde de 75 000 FCFA reste du pour Awa Kouadio. Echeance recommandee: 10/07/2026.',
            ],
        };
    }

    private function contactPayload(string $email, ?string $phone, array $scenario): array
    {
        return array_filter([
            'email' => $email,
            'phone' => $phone,
            'first_name' => 'Parent',
            'last_name' => 'Test KLASSCI',
            'external_id' => 'klassci-test-parent',
            'language' => $this->client->getSetting('mailpulse_default_language', 'default_language', 'fr'),
            'preferred_channel' => $phone ? 'whatsapp' : 'email',
            'subscribed' => true,
            'metadata' => [
                'student_name' => $scenario['student_name'],
                'class_name' => $scenario['class_name'],
                'school_name' => $scenario['school_name'],
                'source' => 'klassci-test-notification',
            ],
        ], fn ($value) => $value !== null && $value !== '');
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
                'text' => '[TEST KLASSCI] ' . $scenario['subject'] . "\n\n" . $scenario['body'],
            ],
            'metadata' => [
                'source' => 'klassci',
                'contact_id' => $contactId,
                'sender_email' => $this->client->getSetting('mailpulse_sender_email', 'sender_email', ''),
                'sender_name' => $this->client->getSetting('mailpulse_sender_name', 'sender_name', 'KLASSCI'),
                'event_summary' => $scenario['summary'],
            ],
        ];
    }

    private function whatsAppPayload(string $phone, string $contactId, array $scenario): array
    {
        return [
            'channel' => 'whatsapp',
            'recipient' => [
                'type' => 'phone',
                'value' => $phone,
            ],
            'content' => [
                'type' => 'text',
                'text' => '[TEST KLASSCI] ' . $scenario['body'],
            ],
            'metadata' => [
                'source' => 'klassci',
                'contact_id' => $contactId,
                'event_summary' => $scenario['summary'],
            ],
        ];
    }
}
