<?php

namespace App\Services\ParentChatbot;

final class ParentChatbotDispatchOutcome
{
    public const STATE_ACCEPTED = 'accepted';

    public const STATE_PENDING_RECONCILIATION = 'pending_reconciliation';

    public const STATE_FAILED = 'failed';

    public const STATE_DEAD_LETTERED = 'dead_lettered';

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

    /**
     * A durable rejection that no retry can fix, such as a closed WhatsApp
     * service window.
     */
    public static function deadLettered(?string $commandId = null): self
    {
        return new self(self::STATE_DEAD_LETTERED, $commandId, false);
    }

    public function isAccepted(): bool
    {
        return $this->state === self::STATE_ACCEPTED;
    }

    public function isPendingReconciliation(): bool
    {
        return $this->state === self::STATE_PENDING_RECONCILIATION;
    }

    public function isDeadLettered(): bool
    {
        return $this->state === self::STATE_DEAD_LETTERED;
    }
}
