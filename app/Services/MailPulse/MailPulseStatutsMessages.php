<?php

namespace App\Services\MailPulse;

/**
 * Relit l'etat durable d'un message MailPulse : `GET /api/v1/messages/{id}`,
 * qui rend `{"message": {status, error_code, delivered_at, ...}}`.
 *
 * Statuts MailPulse (en minuscules) : queued, processing, retrying,
 * submission_unknown, sent, delivered, read, failed, cancelled, reconciled,
 * duplicate_confirmed, template_required. Le detail d'un echec (rebond,
 * plainte) vient de `error_code`, pas du statut.
 *
 * Les codes d'echec local reprennent ceux de l'envoi (MessagerieRdv) :
 * `rate_limited`, `provider_unavailable`, `request_timeout`, `connection_failed`
 * sont passagers ; `disabled`, `missing_api_key`, `auth_failed` tiennent a la
 * configuration ; `introuvable` ne concerne que ce message.
 */
class MailPulseStatutsMessages
{
    public function __construct(private readonly MailPulseApi $api) {}

    /** @return array{status: string, error_code: ?string, delivered_at: ?string}|string */
    public function lire(string $messageId): array|string
    {
        $reponse = $this->api->appeler('GET', '/api/v1/messages/'.rawurlencode($messageId), null, 'message_status', 10, 3);

        if (is_string($reponse)) {
            return $reponse;
        }

        $message = $reponse->json('message');
        if ($reponse->successful() && is_array($message) && is_string($message['status'] ?? null)) {
            return [
                'status' => strtolower($message['status']),
                'error_code' => is_string($message['error_code'] ?? null) ? $message['error_code'] : null,
                'delivered_at' => is_string($message['delivered_at'] ?? null) ? $message['delivered_at'] : null,
            ];
        }

        return match (true) {
            $reponse->status() === 404 => 'introuvable',
            in_array($reponse->status(), [401, 403], true) => 'auth_failed',
            $reponse->status() === 408 => 'request_timeout',
            $reponse->status() === 429 => 'rate_limited',
            default => 'provider_unavailable',
        };
    }
}
