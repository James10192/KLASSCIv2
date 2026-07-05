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

    public function sendEmailMessage(array $message): MailPulseResult
    {
        return $this->post(
            $this->setting('mailpulse_messages_endpoint', 'messages_endpoint', '/api/v1/messages'),
            $message,
            'email_send'
        );
    }

    public function sendWhatsAppMessage(array $message): MailPulseResult
    {
        return $this->post(
            $this->setting('mailpulse_messages_endpoint', 'messages_endpoint', '/api/v1/messages'),
            $message,
            'whatsapp_send'
        );
    }

    private function post(string $endpoint, array $payload, string $operation): MailPulseResult
    {
        $requestId = 'klassci-' . (string) Str::uuid();

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
                ->withHeaders(['X-KLASSCI-Request-Id' => $requestId])
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

        $result = $this->mapResponse($response, $requestId);

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

    private function mapResponse(Response $response, string $requestId): MailPulseResult
    {
        $body = $response->json();
        $requestHeader = $response->header('x-request-id') ?: $response->header('x-vercel-id') ?: $requestId;

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
            408, 429 => $this->failure('rate_limited', $response->status(), $requestHeader, 'MailPulse limite ou expire la requête.', 'Réessayez plus tard ou réduisez la fréquence des tests.'),
            default => $this->providerFailure($response, is_array($body) ? $body : [], $requestHeader),
        };
    }

    private function providerFailure(Response $response, array $body, string $requestId): MailPulseResult
    {
        $code = strtoupper($this->stringValue($body['code'] ?? $body['error'] ?? 'provider_error', 'provider_error'));
        $message = $this->providerMessage($body);

        if (str_contains($code, 'TEMPLATE_REQUIRED') || str_contains(strtoupper($message), 'TEMPLATE_REQUIRED')) {
            return $this->failure('template_required', $response->status(), $requestId, $message, 'Configurez un template WhatsApp approuvé dans Meta/MailPulse pour les messages hors fenêtre 24h.');
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
