<?php

namespace App\Services\ParentChatbot;

final class ParentChatbotDispatchOutcome
{
    public const STATE_ACCEPTED = 'accepted';

    public const STATE_PENDING_RECONCILIATION = 'pending_reconciliation';

    public const STATE_FAILED = 'failed';

    private function __construct(
        public readonly string $state,
        public readonly ?string $commandId,
        public readonly bool $reconciliationRequired,
    ) {}

    public static function accepted(string $commandId): self
    {
        return new self(self::STATE_ACCEPTED, $commandId, false);
    }

    public static function pendingReconciliation(?string $commandId = null): self
    {
        return new self(self::STATE_PENDING_RECONCILIATION, $commandId, true);
    }

    public static function failed(): self
    {
        return new self(self::STATE_FAILED, null, false);
    }

    public function isAccepted(): bool
    {
        return $this->state === self::STATE_ACCEPTED;
    }

    public function isPendingReconciliation(): bool
    {
        return $this->state === self::STATE_PENDING_RECONCILIATION;
    }
}
