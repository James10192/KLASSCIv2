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
    /** WhatsApp refuses text bodies beyond this length. */
    private const MAX_TEXT_LENGTH = 4096;

    public const OPERATION_REPLY = 'parent_chatbot.reply';

    public const OPERATION_LINK_CODE = 'parent_chatbot.link_code';

    /**
     * Activation without a code. Meta only allows a one-time passcode inside an
     * AUTHENTICATION template, whose body is fixed and cannot carry the reply
     * instruction the flow needs, so activation invites the parent to answer
     * instead of sending them a code.
     */
    public const OPERATION_INVITATION = 'parent_chatbot.invitation';

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
            self::OPERATION_REPLY,
            ['type' => 'text', 'text' => $this->boundedText($message)],
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
        string $operationKey = self::OPERATION_LINK_CODE,
    ): ParentChatbotDispatchOutcome {
        if ($templateName === '' || $languageCode === '') {
            Log::warning('Parent chatbot template dispatch is not configured', ['event_id' => $eventId, 'intent' => $intent->value]);

            return ParentChatbotDispatchOutcome::failed();
        }

        // The approved Meta template name lives in the MailPulse template
        // configuration, keyed by operation and locale. The local template name
        // stays an operator go/no-go switch only.
        return $this->dispatchContent(
            $phone,
            $operationKey,
            [
                'type' => 'template',
                'locale' => $languageCode,
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
        string $operationKey,
        array $content,
        ParentChatbotIntent $intent,
        string $eventId,
        ?string $requestId = null,
    ): ParentChatbotDispatchOutcome {
        $requestId = MailPulseTenantContext::scopedIdentifier(
            $requestId ?? 'parent-chatbot-'.(string) Str::uuid()
        );

        $recipient = $this->normalizedRecipient($phone);
        if ($recipient === null) {
            Log::warning('Parent chatbot dispatch skipped an unusable recipient', [
                'event_id' => $eventId,
                'intent' => $intent->value,
                'request_id' => $requestId,
            ]);

            return ParentChatbotDispatchOutcome::failed();
        }

        $result = $this->mailPulseClient->sendExternalApplicationCommand([
            'operation_key' => $operationKey,
            'channel' => 'whatsapp',
            'recipient' => ['type' => 'phone', 'value' => $recipient],
            'content' => $content,
            'metadata' => ['idempotency_key' => $requestId],
        ], $requestId);

        Log::info('Parent chatbot dispatch completed', [
            'event_id' => $eventId,
            'intent' => $intent->value,
            'operation_key' => $operationKey,
            'request_id' => $requestId,
            'http_status' => $result->httpStatus,
            'status' => $result->status,
            'dispatch_state' => $result->dispatchState,
        ]);

        // A durable rejection is never worth replaying: a conversational answer
        // that arrives a day later is already stale.
        if ($result->status === 'command_rejected') {
            return ParentChatbotDispatchOutcome::deadLettered($result->id);
        }

        if ($this->isAccepted($result)) {
            return ParentChatbotDispatchOutcome::accepted($result->id ?? $requestId);
        }

        if ($this->requiresReconciliation($result)) {
            return ParentChatbotDispatchOutcome::pendingReconciliation($result->id ?? $requestId);
        }

        return ParentChatbotDispatchOutcome::failed();
    }

    /**
     * MailPulse only accepts E.164 recipients and rejects anything else with an
     * opaque 400. A national number keeping its trunk prefix is rejected here
     * rather than guessed into a wrong destination.
     */
    private function normalizedRecipient(string $phone): ?string
    {
        $candidate = '+'.(preg_replace('/\D/', '', $phone) ?? '');

        return preg_match('/^\+[1-9]\d{6,14}$/', $candidate) === 1 ? $candidate : null;
    }

    private function boundedText(string $message): string
    {
        return mb_strlen($message) <= self::MAX_TEXT_LENGTH
            ? $message
            : mb_substr($message, 0, self::MAX_TEXT_LENGTH - 1).'…';
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

}
