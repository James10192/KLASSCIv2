<?php

namespace App\Services\ParentChatbot;

use App\Enums\ParentChatbotIntent;
use App\Services\MailPulse\MailPulseClient;
use App\Services\MailPulse\MailPulseResult;
use App\Services\MailPulse\MailPulseTenantContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ParentChatbotDispatcher
{
    public function __construct(private readonly MailPulseClient $mailPulseClient) {}

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
                'template_key' => $templateName,
                'locale' => $languageCode,
                'variables' => $this->templateVariables($parameters),
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
        $requestId = MailPulseTenantContext::scopedIdentifier(
            $requestId ?? 'parent-chatbot-'.(string) Str::uuid()
        );

        $payload = [
            'channel' => 'whatsapp',
            'recipient' => ['type' => 'phone', 'value' => $phone],
            'content' => $content,
            'metadata' => [
                'source' => 'klassci_parent_chatbot',
                'tenant_code' => MailPulseTenantContext::code(),
                'intent' => $intent->value,
                'event_id' => $eventId,
            ],
        ];
        $result = $this->mailPulseClient->sendWhatsAppMessage($payload, $requestId);

        Log::info('Parent chatbot dispatch completed', [
            'event_id' => $eventId,
            'intent' => $intent->value,
            'request_id' => $requestId,
            'http_status' => $result->httpStatus,
            'status' => $result->status,
            'dispatch_state' => $result->dispatchState,
        ]);

        if ($this->isAccepted($result)) {
            return ParentChatbotDispatchOutcome::accepted($result->id ?? $requestId);
        }

        if ($this->requiresReconciliation($result)) {
            return ParentChatbotDispatchOutcome::pendingReconciliation($result->id ?? $requestId);
        }

        return ParentChatbotDispatchOutcome::failed();
    }

    private function isAccepted(MailPulseResult $result): bool
    {
        return $result->ok && ($result->dispatchState === null || $result->dispatchState === ParentChatbotDispatchOutcome::STATE_ACCEPTED);
    }

    private function requiresReconciliation(MailPulseResult $result): bool
    {
        if ($result->ok && in_array($result->dispatchState, ['pending', ParentChatbotDispatchOutcome::STATE_PENDING_RECONCILIATION], true)) {
            return true;
        }

        return $result->status === 'connection_failed'
            || $result->httpStatus === 408
            || $result->httpStatus === 429
            || ($result->httpStatus !== null && $result->httpStatus >= 500);
    }

    /**
     * @param array<int, string> $parameters
     * @return array<string, string>
     */
    private function templateVariables(array $parameters): array
    {
        $variables = [];
        foreach (array_values($parameters) as $index => $parameter) {
            $variables[(string) ($index + 1)] = $parameter;
        }

        return $variables;
    }
}
