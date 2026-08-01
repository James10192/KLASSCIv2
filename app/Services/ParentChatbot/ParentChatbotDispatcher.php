<?php

namespace App\Services\ParentChatbot;

use App\Enums\ParentChatbotIntent;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ParentChatbotDispatcher
{
    public function dispatch(
        string $phone,
        string $message,
        ParentChatbotIntent $intent,
        string $eventId,
        ?string $requestId = null,
    ): ParentChatbotDispatchOutcome {
        return $this->dispatchContent(
            $phone,
            ['type' => 'text', 'text' => $message],
            $intent,
            $eventId,
            $requestId,
        );
    }

    /**
     * @param  array<int, string>  $parameters
     */
    public function dispatchTemplate(
        string $phone,
        string $templateName,
        string $languageCode,
        array $parameters,
        ParentChatbotIntent $intent,
        string $eventId,
        ?string $requestId = null,
    ): ParentChatbotDispatchOutcome {
        if ($templateName === '' || $languageCode === '') {
            Log::warning('Parent chatbot template dispatch is not configured', ['event_id' => $eventId, 'intent' => $intent->value]);

            return ParentChatbotDispatchOutcome::failed();
        }

        return $this->dispatchContent(
            $phone,
            [
                'type' => 'template',
                'template_name' => $templateName,
                'language_code' => $languageCode,
                'parameters' => array_values($parameters),
            ],
            $intent,
            $eventId,
            $requestId,
        );
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function dispatchContent(
        string $phone,
        array $content,
        ParentChatbotIntent $intent,
        string $eventId,
        ?string $requestId = null,
    ): ParentChatbotDispatchOutcome {
        $baseUrl = rtrim((string) config('services.mailpulse.base_url'), '/');
        $endpoint = (string) config('services.mailpulse.parent_chatbot_dispatch_endpoint');
        $serviceSecret = (string) config('services.mailpulse.parent_chatbot_service_secret');
        $requestId ??= 'klassci-parent-chatbot-'.(string) Str::uuid();

        if ($baseUrl === '' || $endpoint === '' || strlen($serviceSecret) < 32) {
            Log::warning('Parent chatbot dispatch is not configured', ['event_id' => $eventId, 'intent' => $intent->value]);

            return ParentChatbotDispatchOutcome::failed();
        }

        $payload = [
            'channel' => 'whatsapp',
            'recipient' => ['type' => 'phone', 'value' => $phone],
            'content' => $content,
            'metadata' => ['source' => 'klassci_parent_chatbot', 'intent' => $intent->value, 'event_id' => $eventId],
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $serviceSecret);

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('services.mailpulse.timeout', 20))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Idempotency-Key' => $requestId,
                    'X-KLASSCI-Request-Id' => $requestId,
                    'X-KLASSCI-Service-Timestamp' => $timestamp,
                    'X-KLASSCI-Service-Signature' => $signature,
                ])
                ->withBody($body, 'application/json')
                ->post($baseUrl.'/'.ltrim($endpoint, '/'));
        } catch (ConnectionException $e) {
            Log::warning('Parent chatbot dispatch connection failed', ['event_id' => $eventId, 'intent' => $intent->value]);

            return ParentChatbotDispatchOutcome::pendingReconciliation();
        }

        Log::info('Parent chatbot dispatch completed', [
            'event_id' => $eventId,
            'intent' => $intent->value,
            'request_id' => $requestId,
            'http_status' => $response->status(),
        ]);

        if ($this->hasAmbiguousSubmissionStatus($response->status())) {
            return ParentChatbotDispatchOutcome::pendingReconciliation();
        }

        $body = $response->json();
        if (! $response->successful() || ! is_array($body)) {
            return ParentChatbotDispatchOutcome::failed();
        }

        $commandId = $body['command_id'] ?? null;
        $state = $body['dispatch_state'] ?? null;
        $reconciliationRequired = $body['reconciliation_required'] ?? null;

        if (! is_string($commandId) || $commandId === '' || ! is_string($state) || ! is_bool($reconciliationRequired)) {
            return ParentChatbotDispatchOutcome::failed();
        }

        if ($state === ParentChatbotDispatchOutcome::STATE_ACCEPTED && $reconciliationRequired === false) {
            return ParentChatbotDispatchOutcome::accepted($commandId);
        }

        if ($state === ParentChatbotDispatchOutcome::STATE_PENDING_RECONCILIATION && $reconciliationRequired === true) {
            return ParentChatbotDispatchOutcome::pendingReconciliation($commandId);
        }

        return ParentChatbotDispatchOutcome::failed();
    }

    private function hasAmbiguousSubmissionStatus(int $status): bool
    {
        return $status === 408 || $status === 429 || $status >= 500;
    }
}
