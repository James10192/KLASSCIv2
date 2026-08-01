<?php

namespace App\Models;

final class ParentChatbotInboundEventClaim
{
    private function __construct(
        public readonly ?ParentChatbotInboundEvent $event,
        private readonly string $status,
    ) {}

    public static function claimed(ParentChatbotInboundEvent $event): self
    {
        return new self($event, 'claimed');
    }

    public static function processedDuplicate(): self
    {
        return new self(null, 'processed_duplicate');
    }

    public static function busy(ParentChatbotInboundEvent $event): self
    {
        return new self($event, 'busy');
    }

    public static function payloadConflict(): self
    {
        return new self(null, 'payload_conflict');
    }

    public function isClaimed(): bool
    {
        return $this->status === 'claimed';
    }

    public function hasPayloadConflict(): bool
    {
        return $this->status === 'payload_conflict';
    }

    public function isProcessedDuplicate(): bool
    {
        return $this->status === 'processed_duplicate';
    }

    public function isBusy(): bool
    {
        return $this->status === 'busy';
    }

    public function retryAfterSeconds(): ?int
    {
        if (! $this->isBusy() || ! $this->event?->processing_expires_at) {
            return null;
        }

        return max(1, $this->event->processing_expires_at->getTimestamp() - now()->getTimestamp());
    }
}
