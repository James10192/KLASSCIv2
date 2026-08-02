<?php

namespace App\Services\ParentChatbot;

use App\Models\ParentChatbotLinkCodeIssuance;

final class ParentChatbotLinkCodeDeliveryState
{
    /** @return array<string, mixed> */
    public static function terminal(string $status, ?string $errorCode, ?string $commandId = null): array
    {
        return [
            'status' => $status,
            'accepted_at' => $status === ParentChatbotLinkCodeIssuance::STATUS_ACCEPTED ? now() : null,
            'failed_at' => $status === ParentChatbotLinkCodeIssuance::STATUS_FAILED ? now() : null,
            'manual_reconciliation_at' => null,
            'error_code' => $errorCode,
            'provider_command_id' => $commandId,
            'delivery_payload' => null,
            'delivery_payload_expires_at' => null,
            'delivery_token' => null,
            'delivery_started_at' => null,
            'delivery_lease_expires_at' => null,
            'next_attempt_at' => null,
            'retry_expires_at' => null,
        ];
    }

    /** @return array<string, mixed> */
    public static function manual(
        ParentChatbotLinkCodeIssuance $issuance,
        string $errorCode,
        ?string $commandId = null,
    ): array {
        return array_merge(self::terminal(
            ParentChatbotLinkCodeIssuance::STATUS_MANUAL_RECONCILIATION,
            $errorCode,
            $commandId ?? $issuance->provider_command_id,
        ), ['manual_reconciliation_at' => now()]);
    }

    public static function backoffSeconds(int $attemptCount): int
    {
        return min(300, 5 * (2 ** max(0, $attemptCount - 1)));
    }
}
