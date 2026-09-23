<?php

namespace App\Services\MailPulse;

/**
 * Relit l'etat durable d'un message MailPulse : `GET /api/v1/messages/{id}`,
 * qui rend `{"message": {status, error_code, delivered_at, failed_at, ...}}`
 * (statuts en minuscules : queued, sent, delivered, read, failed, cancelled,
 * template_required...).
 *
 * @return array{status: string, error_code: ?string, delivered_at: ?string}|string
 *         l'etat distant, ou un code d'echec local (`disabled`, `introuvable`...)
 */
class MailPulseStatutsMessages
{
    public function __construct(private readonly MailPulseApi $api) {}

    public function lire(string $messageId): array|string
    {
        $reponse = $this->api->appeler('GET', '/api/v1/messages/'.rawurlencode($messageId), null, 'message_status', 10, 3);

        if (is_string($reponse)) {
            return $reponse;
        }

        if ($reponse->status() === 404) {
            return 'introuvable';
        }

        $message = $reponse->json('message');
        if (! $reponse->successful() || ! is_array($message) || ! is_string($message['status'] ?? null)) {
            return $reponse->status() === 401 || $reponse->status() === 403 ? 'auth_failed' : 'provider_unavailable';
        }

        return [
            'status' => strtolower($message['status']),
            'error_code' => is_string($message['error_code'] ?? null) ? $message['error_code'] : null,
            'delivered_at' => is_string($message['delivered_at'] ?? null) ? $message['delivered_at'] : null,
        ];
    }
}
