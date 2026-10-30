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
                'delivery_delays' => is_array($message['delivery_delays'] ?? null) ? array_slice($message['delivery_delays'], 0, 20) : [],
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
    /**
     * Recherche strictement en lecture seule une soumission dont la réponse
     * HTTP a été perdue. Le 404 n'est jamais une preuve de non-expédition.
     *
     * @return array<string,mixed>|string
     */
    public function lireParReference(string $reference, string $tenant): array|string
    {
        if (! preg_match('/^klassci-annonce-[1-9][0-9]*-courriel-[1-9][0-9]*$/D', $reference)
            || trim($tenant) === '' || $tenant === 'default') {
            return 'invalid_dispatch_contract';
        }

        $chemin = '/api/v1/messages/by-idempotency-key?key='.rawurlencode($reference)
            .'&tenant='.rawurlencode($tenant);
        $reponse = $this->api->appeler('GET', $chemin, null, 'announcement_lookup', 10, 2);
        if (is_string($reponse)) {
            return $reponse;
        }

        if ($reponse->status() === 404) {
            return 'introuvable'; // Le message peut encore être en vol !
        }
        if (in_array($reponse->status(), [401, 403], true)) {
            return 'auth_failed';
        }
        if ($reponse->status() === 429) {
            return 'rate_limited';
        }
        if (! $reponse->successful()) {
            return 'provider_unavailable';
        }

        $message = $reponse->json('message');
        if (! is_array($message) || ! is_string($message['id'] ?? null)
            || trim($message['id']) === ''
            || ! is_string($message['status'] ?? null)
            || ! is_array($message['recipient'] ?? null)
            || ($message['recipient']['type'] ?? null) !== 'email'
            || ! is_string($message['recipient']['value'] ?? null)
            || ($message['external_tenant_id'] ?? null) !== $tenant
            || ($message['external_reference'] ?? null) !== $reference) {
            return 'invalid_dispatch_contract';
        }

        return [
            'id' => $message['id'],
            'recipient' => strtolower(trim($message['recipient']['value'])),
            'status' => strtolower($message['status']),
            'delivered_at' => is_string($message['delivered_at'] ?? null) ? $message['delivered_at'] : null,
            'error_code' => is_string($message['error_code'] ?? null) ? $message['error_code'] : null,
            'delivery_delays' => is_array($message['delivery_delays'] ?? null) ? array_slice($message['delivery_delays'], 0, 20) : [],
        ];
    }

}
