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

            return $this->failure('connection_failed', null, $requestId, 'MailPulse est injoignable.', 'Verifiez le reseau, MAILPULSE_BASE_URL et le statut Vercel.');
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
                return (string) $value;
            }
        } catch (\Throwable $e) {
            // Settings DB may be unavailable during early bootstrap or tests.
        }

        $configValue = config('services.mailpulse.' . $configKey, $default);

        return is_bool($configValue) ? ($configValue ? '1' : '0') : (string) $configValue;
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
            401, 403 => $this->failure('auth_failed', $response->status(), $requestHeader, 'Authentification MailPulse refusee.', 'Verifiez MAILPULSE_API_KEY et les droits API v1.'),
            404 => $this->failure('endpoint_not_found', $response->status(), $requestHeader, 'Endpoint MailPulse introuvable.', 'Verifiez MAILPULSE_*_ENDPOINT dans .env.'),
            405 => $this->failure('endpoint_not_supported', $response->status(), $requestHeader, 'Methode non acceptee par MailPulse.', 'Confirmez le vrai endpoint/methode MailPulse pour ce canal.'),
            408, 429 => $this->failure('rate_limited', $response->status(), $requestHeader, 'MailPulse limite ou expire la requete.', 'Reessayez plus tard ou reduisez la frequence des tests.'),
            default => $this->providerFailure($response, is_array($body) ? $body : [], $requestHeader),
        };
    }

    private function providerFailure(Response $response, array $body, string $requestId): MailPulseResult
    {
        $code = strtoupper((string) ($body['code'] ?? $body['error'] ?? 'provider_error'));
        $message = (string) ($body['message'] ?? 'MailPulse a retourne une erreur.');

        if (str_contains($code, 'TEMPLATE_REQUIRED') || str_contains(strtoupper($message), 'TEMPLATE_REQUIRED')) {
            return $this->failure('template_required', $response->status(), $requestId, $message, 'Configurez un template WhatsApp approuve dans Meta/MailPulse pour les messages hors fenetre 24h.');
        }

        if (str_contains($code, 'CHANNEL_NOT_CONFIGURED') || str_contains(strtoupper($message), 'CANAL NON CONFIGURE')) {
            return $this->failure('channel_not_configured', $response->status(), $requestId, $message, 'Connectez le canal WhatsApp ou email dans MailPulse avant de relancer.');
        }

        return $this->failure('provider_error', $response->status(), $requestId, $message, 'Consultez les logs MailPulse avec le requestId retourne.');
    }

    private function failure(string $status, ?int $httpStatus, string $requestId, string $message, string $action): MailPulseResult
    {
        return new MailPulseResult(false, $status, $httpStatus, $requestId, null, $status, $message, $action);
    }

    private function extractId(array $body): ?string
    {
        $id = $body['id'] ?? $body['contactId'] ?? $body['messageId'] ?? $body['data']['id'] ?? $body['contact']['id'] ?? null;
        return $id === null ? null : (string) $id;
    }
}
