<?php

namespace App\Services\MailPulse;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MailPulseClient
{
    public function createOrUpdateContact(array $contact): MailPulseResult
    {
        return $this->post(
            $this->setting('mailpulse_contacts_endpoint', 'contacts_endpoint', '/api/v1/contacts'),
            $contact,
            'contact_upsert'
        );
    }

    public function sendEmailMessage(array $message, ?string $requestId = null): MailPulseResult
    {
        return $this->post(
            $this->setting('mailpulse_messages_endpoint', 'messages_endpoint', '/api/v1/messages'),
            $message,
            'email_send',
            $requestId
        );
    }

    public function sendWhatsAppMessage(array $message, ?string $requestId = null): MailPulseResult
    {
        return $this->post(
            $this->setting('mailpulse_messages_endpoint', 'messages_endpoint', '/api/v1/messages'),
            $message,
            'whatsapp_send',
            $requestId
        );
    }

    /**
     * Submit an SMS intent to MailPulse. KLASSCI never calls an SMS provider directly.
     */
    public function sendSmsMessage(array $message, ?string $requestId = null): MailPulseResult
    {
        return $this->post(
            $this->setting('mailpulse_messages_endpoint', 'messages_endpoint', '/api/v1/messages'),
            $message,
            'sms_send',
            $requestId
        );
    }

    /**
     * Submit a conversational command on the MailPulse external-application rail.
     *
     * This rail is deliberately distinct from /api/v1/messages: it is the only
     * one where MailPulse owns the WhatsApp 24h service window and relays the
     * parent's replies back to KLASSCI. Notifications keep using the API key
     * rail above.
     *
     * @param  array<string, mixed>  $command
     */
    public function sendExternalApplicationCommand(array $command, ?string $requestId = null): MailPulseResult
    {
        $requestId ??= 'klassci-'.(string) Str::uuid();

        if (! $this->enabled()) {
            return $this->failure('disabled', null, $requestId, 'MailPulse est desactive par MAILPULSE_ENABLED=false.', 'Activez MAILPULSE_ENABLED pour lancer un test reel.');
        }

        $applicationKey = $this->setting('mailpulse_external_application_key', 'external_application_key', '');
        $organizationId = $this->setting('mailpulse_external_organization_id', 'external_organization_id', '');
        $keyId = $this->setting('mailpulse_external_command_key_id', 'external_command_key_id', '');
        $secret = $this->setting('mailpulse_external_command_secret', 'external_command_secret', '');

        if ($applicationKey === '' || $organizationId === '' || $keyId === '' || $secret === '') {
            return $this->failure(
                'missing_external_application_credentials',
                null,
                $requestId,
                "L'application externe MailPulse n'est pas configurée.",
                'Renseignez MAILPULSE_EXTERNAL_APPLICATION_KEY, MAILPULSE_EXTERNAL_ORGANIZATION_ID, MAILPULSE_EXTERNAL_COMMAND_KEY_ID et MAILPULSE_EXTERNAL_COMMAND_SECRET.'
            );
        }

        // The signature covers the exact bytes sent, so the body is serialized
        // once here rather than left to the HTTP client.
        $body = json_encode($command, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            return $this->failure('invalid_payload', null, $requestId, 'La commande MailPulse est inserialisable.', 'Vérifiez le contenu du message avant de relancer.');
        }

        $timestamp = (string) time();

        try {
            $response = Http::acceptJson()
                ->timeout((int) $this->setting('mailpulse_timeout', 'timeout', '20'))
                ->withHeaders([
                    'x-external-organization-id' => $organizationId,
                    'x-external-timestamp' => $timestamp,
                    'x-external-signature' => 'v1:'.$keyId.'='.hash_hmac('sha256', $timestamp.'.'.$body, $secret),
                    'X-KLASSCI-Request-Id' => $requestId,
                ])
                ->withBody($body, 'application/json')
                ->post($this->url('/api/v1/external-applications/'.rawurlencode($applicationKey).'/commands'));
        } catch (ConnectionException $e) {
            Log::warning('MailPulse external command failed', [
                'operation' => 'external_command',
                'request_id' => $requestId,
                'status' => 'connection_failed',
                'error' => $e->getMessage(),
            ]);

            return $this->failure('connection_failed', null, $requestId, 'MailPulse est injoignable.', 'Vérifiez le réseau, MAILPULSE_BASE_URL et le statut Vercel.');
        }

        $result = $this->mapCommandResponse($response, $requestId);

        Log::info('MailPulse external command completed', [
            'operation' => 'external_command',
            'request_id' => $requestId,
            'http_status' => $response->status(),
            'status' => $result->status,
            'dispatch_state' => $result->dispatchState,
        ]);

        return $result;
    }

    private function mapCommandResponse(Response $response, string $requestId): MailPulseResult
    {
        $body = $response->json();
        $body = is_array($body) ? $body : [];
        $requestHeader = $response->header('x-request-id') ?: $response->header('x-vercel-id') ?: $requestId;
        $state = is_string($body['dispatch_state'] ?? null) ? $body['dispatch_state'] : null;
        $operationId = is_string($body['operation_id'] ?? null) ? $body['operation_id'] : null;

        if ($response->status() === 202 && in_array($state, ['accepted', 'pending_reconciliation'], true)) {
            return new MailPulseResult(true, $state, 202, $requestHeader, $operationId, null, null, null, $state, false);
        }

        if ($response->status() === 422 && $state === 'rejected') {
            $code = is_string($body['rejection_code'] ?? null) ? $body['rejection_code'] : 'provider_rejected';

            // Every 422 rejection is durable, so it must never be retried. A
            // replayed rejection loses its original code, so the code cannot be
            // what decides retryability.
            return new MailPulseResult(
                false,
                'command_rejected',
                422,
                $requestHeader,
                $operationId,
                $code,
                $code === 'whatsapp_service_window_closed'
                    ? 'La fenêtre WhatsApp de 24h est fermée pour ce parent.'
                    : 'MailPulse a rejeté la commande.',
                $code === 'whatsapp_service_window_closed'
                    ? 'La réponse est abandonnée : le parent doit réécrire pour rouvrir la conversation.'
                    : 'Consultez les logs MailPulse avec le requestId retourné.',
                'rejected',
                false,
            );
        }

        return match ($response->status()) {
            400 => $this->failure('invalid_payload', 400, $requestHeader, 'MailPulse a refusé la structure de la commande.', 'Vérifiez le contrat de la route commands (longueur du texte, format du numéro).'),
            401, 403 => $this->failure('auth_failed', $response->status(), $requestHeader, 'Signature MailPulse refusée.', "Vérifiez la clé de commande, l'horloge du serveur et l'organisation configurée."),
            404 => $this->failure('endpoint_not_found', 404, $requestHeader, 'Application externe MailPulse introuvable.', 'Vérifiez MAILPULSE_EXTERNAL_APPLICATION_KEY.'),
            409 => $this->failure('dispatch_conflict', 409, $requestHeader, 'MailPulse traite déjà cette commande ou a reçu un payload divergent.', "Relancez la réconciliation plutôt qu'un nouvel envoi."),
            429 => $this->failure('rate_limited', 429, $requestHeader, 'MailPulse limite la requête.', 'Réessayez plus tard.'),
            default => $this->failure('provider_unavailable', $response->status(), $requestHeader, 'MailPulse est temporairement indisponible.', 'Réessayez plus tard.'),
        };
    }

    private function post(string $endpoint, array $payload, string $operation, ?string $requestId = null): MailPulseResult
    {
        $requestId ??= 'klassci-' . (string) Str::uuid();

        if (! $this->enabled()) {
            return $this->failure('disabled', null, $requestId, 'MailPulse est desactive par MAILPULSE_ENABLED=false.', 'Activez MAILPULSE_ENABLED pour lancer un test reel.');
        }

        $apiKey = $this->setting('mailpulse_api_key', 'api_key', '');
        if ($apiKey === '') {
            return $this->failure('missing_api_key', null, $requestId, 'MAILPULSE_API_KEY est manquante.', 'Configurez MAILPULSE_API_KEY ou lancez en dry-run.');
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout((int) $this->setting('mailpulse_timeout', 'timeout', '20'))
                ->withHeaders([
                    // MailPulse deduplicates message submissions with this standard header.
                    'Idempotency-Key' => $requestId,
                    // Keep the original correlation header for existing MailPulse observability.
                    'X-KLASSCI-Request-Id' => $requestId,
                ])
                ->post($this->url($endpoint), $payload);
        } catch (ConnectionException $e) {
            Log::warning('MailPulse request failed', [
                'operation' => $operation,
                'request_id' => $requestId,
                'status' => 'connection_failed',
                'error' => $e->getMessage(),
            ]);

            return $this->failure('connection_failed', null, $requestId, 'MailPulse est injoignable.', 'Vérifiez le réseau, MAILPULSE_BASE_URL et le statut Vercel.');
        }

        $result = $this->mapResponse($response, $requestId, $operation);

        Log::info('MailPulse request completed', [
            'operation' => $operation,
            'request_id' => $requestId,
            'http_status' => $response->status(),
            'status' => $result->status,
        ]);

        return $result;
    }

    public function getSetting(string $settingKey, string $configKey, string $default = ''): string
    {
        return $this->setting($settingKey, $configKey, $default);
    }

    public function apiKeyDiagnostics(): array
    {
        try {
            $settingValue = Setting::where('key', 'mailpulse_api_key')
                ->where('is_active', true)
                ->value('value');
            if (is_string($settingValue) && trim($settingValue) !== '') {
                return [
                    'configured' => true,
                    'source' => 'settings',
                ];
            }
        } catch (\Throwable $e) {
            // Keep diagnostics non-blocking.
        }

        $configValue = config('services.mailpulse.api_key', '');
        if (is_string($configValue) && trim($configValue) !== '') {
            return [
                'configured' => true,
                'source' => 'env',
            ];
        }

        return [
            'configured' => false,
            'source' => 'none',
        ];
    }

    private function enabled(): bool
    {
        $value = $this->setting('mailpulse_enabled', 'enabled', '1');

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function setting(string $settingKey, string $configKey, string $default = ''): string
    {
        try {
            $value = Setting::get($settingKey, null);
            if ($value !== null && $value !== '') {
                return is_string($value) ? trim($value) : (string) $value;
            }
        } catch (\Throwable $e) {
            // Settings DB may be unavailable during early bootstrap or tests.
        }

        $configValue = config('services.mailpulse.' . $configKey, $default);

        if (is_bool($configValue)) {
            return $configValue ? '1' : '0';
        }

        return is_string($configValue) ? trim($configValue) : (string) $configValue;
    }

    private function url(string $endpoint): string
    {
        $baseUrl = rtrim($this->setting('mailpulse_base_url', 'base_url', 'https://mailpulse-two.vercel.app'), '/');
        return $baseUrl . '/' . ltrim($endpoint, '/');
    }

    private function mapResponse(Response $response, string $requestId, string $operation): MailPulseResult
    {
        $body = $response->json();
        $requestHeader = $response->header('x-request-id') ?: $response->header('x-vercel-id') ?: $requestId;

        if (in_array($operation, ['email_send', 'whatsapp_send', 'sms_send'], true)) {
            $dispatchResult = $this->mapMessageDispatchResponse($response, is_array($body) ? $body : [], $requestHeader);
            if ($dispatchResult !== null) {
                return $dispatchResult;
            }

            if ($response->successful()) {
                return $this->failure(
                    'invalid_dispatch_contract',
                    $response->status(),
                    $requestHeader,
                    'MailPulse a accepté la requête sans état de dispatch exploitable.',
                    'Vérifiez la version du contrat MailPulse avant de considérer le message comme envoyé.'
                );
            }
        }

        if ($response->successful()) {
            return new MailPulseResult(
                true,
                (string) ($body['status'] ?? 'sent'),
                $response->status(),
                $requestHeader,
                $this->extractId(is_array($body) ? $body : [])
            );
        }

        return match ($response->status()) {
            401, 403 => $this->failure('auth_failed', $response->status(), $requestHeader, 'Authentification MailPulse refusée.', 'Vérifiez MAILPULSE_API_KEY et les droits API v1.'),
            404 => $this->failure('endpoint_not_found', $response->status(), $requestHeader, 'Endpoint MailPulse introuvable.', 'Vérifiez MAILPULSE_*_ENDPOINT dans .env.'),
            405 => $this->failure('endpoint_not_supported', $response->status(), $requestHeader, 'Méthode non acceptée par MailPulse.', 'Confirmez le vrai endpoint/méthode MailPulse pour ce canal.'),
            408 => $this->failure('request_timeout', $response->status(), $requestHeader, 'MailPulse a expiré la requête.', 'Réessayez plus tard.'),
            429 => $this->failure('rate_limited', $response->status(), $requestHeader, 'MailPulse limite la requête.', 'Réessayez plus tard.'),
            500, 501, 502, 503, 504 => $this->failure('provider_unavailable', $response->status(), $requestHeader, 'MailPulse est temporairement indisponible.', 'Réessayez plus tard.'),
            default => $this->providerFailure($response, is_array($body) ? $body : [], $requestHeader),
        };
    }

    private function mapMessageDispatchResponse(Response $response, array $body, string $requestId): ?MailPulseResult
    {
        $dispatch = $body['dispatch'] ?? null;
        if (! is_array($dispatch)) {
            return null;
        }

        $state = $dispatch['state'] ?? null;
        $smsFallbackEligible = $dispatch['sms_fallback_eligible'] ?? null;
        $message = $body['message'] ?? null;
        $messageStatus = is_array($message) && is_string($message['status'] ?? null) ? $message['status'] : null;

        if (! is_string($state) || ! is_bool($smsFallbackEligible) || ! in_array($state, ['accepted', 'pending', 'pending_reconciliation', 'failed'], true)) {
            return $this->failure(
                'invalid_dispatch_contract',
                $response->status(),
                $requestId,
                'MailPulse a retourné un état de dispatch invalide.',
                'Vérifiez la réponse MailPulse avant de considérer le message comme envoyé.'
            );
        }

        $id = $this->extractId($body);
        if ($state === 'failed') {
            $code = $this->stringValue($body['code'] ?? (is_array($message) ? $message['error_code'] ?? null : null), 'provider_error');
            $status = $this->dispatchFailureStatus($code, $messageStatus);

            return new MailPulseResult(
                false,
                $status,
                $response->status(),
                $requestId,
                $id,
                $code,
                $this->providerMessage($body),
                'Consultez l’état durable retourné par MailPulse avant toute relance.',
                $state,
                $smsFallbackEligible,
            );
        }

        return new MailPulseResult(
            true,
            $messageStatus ?? $state,
            $response->status(),
            $requestId,
            $id,
            null,
            null,
            null,
            $state,
            false,
        );
    }

    private function dispatchFailureStatus(string $code, ?string $messageStatus): string
    {
        $normalizedCode = strtoupper($code);
        if ($messageStatus === 'template_required' || str_contains($normalizedCode, 'TEMPLATE_REQUIRED')) {
            return 'template_required';
        }

        if (str_contains($normalizedCode, 'RECIPIENT_NOT_ACTIVATED')) {
            return 'whatsapp_not_activated';
        }

        return 'provider_error';
    }

    private function providerFailure(Response $response, array $body, string $requestId): MailPulseResult
    {
        $code = strtoupper($this->stringValue($body['code'] ?? $body['error'] ?? 'provider_error', 'provider_error'));
        $message = $this->providerMessage($body);

        if (str_contains($code, 'TEMPLATE_REQUIRED') || str_contains(strtoupper($message), 'TEMPLATE_REQUIRED')) {
            return $this->failure('template_required', $response->status(), $requestId, $message, 'Configurez un template WhatsApp approuvé dans Meta/MailPulse pour les messages hors fenêtre 24h.');
        }

        if (
            str_contains($code, 'WHATSAPP_NOT_ACTIVATED')
            || str_contains($code, 'RECIPIENT_NOT_ACTIVATED')
            || str_contains($code, 'WHATSAPP_WINDOW_CLOSED')
            || str_contains(strtolower($message), 'whatsapp is not activated')
            || str_contains(strtolower($message), 'recipient is not activated')
        ) {
            return $this->failure('whatsapp_not_activated', $response->status(), $requestId, $message, "Le parent WhatsApp n'est pas actif. Utilisez le fallback SMS MailPulse si ce canal est autorisé.");
        }

        if (str_contains($code, 'CHANNEL_NOT_CONFIGURED') || str_contains(strtoupper($message), 'CANAL NON CONFIGURE')) {
            return $this->failure('channel_not_configured', $response->status(), $requestId, $message, 'Connectez le canal WhatsApp ou email dans MailPulse avant de relancer.');
        }

        if (str_contains(strtolower($message), 'domain is not verified')) {
            return $this->failure('provider_error', $response->status(), $requestId, $message, 'Vérifiez le domaine expéditeur dans Resend/MailPulse, puis relancez le test email.');
        }

        return $this->failure('provider_error', $response->status(), $requestId, $message, 'Consultez les logs MailPulse avec le requestId retourné.');
    }

    private function providerMessage(array $body): string
    {
        $message = $this->stringValue($body['message'] ?? $body['error'] ?? null, 'MailPulse a retourné une erreur.');
        $decoded = json_decode($message, true);

        if (is_array($decoded)) {
            return $this->stringValue(
                $decoded['error_message'] ?? $decoded['message'] ?? $decoded['error'] ?? null,
                $message
            );
        }

        return $message;
    }

    private function stringValue(mixed $value, string $default): string
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $default;
    }

    private function failure(string $status, ?int $httpStatus, string $requestId, string $message, string $action): MailPulseResult
    {
        return new MailPulseResult(false, $status, $httpStatus, $requestId, null, $status, $message, $action);
    }

    private function extractId(array $body): ?string
    {
        $id = $body['id'] ?? $body['contactId'] ?? $body['messageId'] ?? $body['data']['id'] ?? $body['contact']['id'] ?? $body['message']['id'] ?? null;
        return $id === null ? null : (string) $id;
    }
}
