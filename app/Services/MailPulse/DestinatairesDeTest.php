<?php

namespace App\Services\MailPulse;

use App\Domain\Notifications\PhoneNormalizer;

/**
 * Les adresses et numéros de test actifs de l'école, et rien d'autre.
 *
 * Seule source des destinataires des envois d'essai : la notification de test
 * MailPulse comme l'essai des avis aux parents n'écrivent qu'ici, jamais à un
 * parent réel.
 */
class DestinatairesDeTest
{
    public function __construct(private MailPulseClient $client) {}

    /** @return list<string> Adresses actives et valides, sans doublon (casse ignorée). */
    public function courriels(): array
    {
        $recipients = $this->parse(
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

    /** @return list<string> Numéros actifs au format E.164, sans doublon. */
    public function telephones(): array
    {
        $recipients = $this->parse(
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

    private function parse(string $json): array
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
}
