<?php

namespace App\Services\MailPulse;

final class MailPulseResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $status,
        public readonly ?int $httpStatus = null,
        public readonly ?string $requestId = null,
        public readonly ?string $id = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $message = null,
        public readonly ?string $action = null,
        public readonly ?string $dispatchState = null,
        public readonly bool $smsFallbackEligible = false,
    ) {}

    public static function dryRun(?string $requestId = null): self
    {
        return new self(true, 'dry_run', null, $requestId);
    }

    public static function skipped(string $status, string $message): self
    {
        return new self(true, $status, null, null, null, null, $message);
    }

    public function toArray(): array
    {
        return array_filter([
            'ok' => $this->ok,
            'status' => $this->status,
            'httpStatus' => $this->httpStatus,
            'requestId' => $this->requestId,
            'id' => $this->id,
            'errorCode' => $this->errorCode,
            'message' => $this->message,
            'action' => $this->action,
            'dispatchState' => $this->dispatchState,
            'smsFallbackEligible' => $this->smsFallbackEligible,
        ], fn ($value) => $value !== null);
    }

    public function isDispatchAccepted(): bool
    {
        return $this->dispatchState === null ? $this->ok : $this->dispatchState === 'accepted';
    }
}
