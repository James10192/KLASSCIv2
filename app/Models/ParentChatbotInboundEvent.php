<?php

namespace App\Models;

use App\Enums\ParentChatbotIntent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class ParentChatbotInboundEvent extends Model
{
    private const LEASE_SECONDS = 300;

    protected $fillable = [
        'source_event_id',
        'payload_hash',
        'outcome',
        'received_at',
        'processed_at',
        'processing_token',
        'processing_started_at',
        'processing_expires_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processing_expires_at' => 'datetime',
    ];

    public static function claim(string $sourceEventId, string $payloadHash): ParentChatbotInboundEventClaim
    {
        $event = static::findOrCreate($sourceEventId, $payloadHash);

        while (true) {
            $unavailableClaim = static::unavailableClaim($event, $payloadHash);

            if ($unavailableClaim) {
                return $unavailableClaim;
            }

            $claimedEvent = static::takeLease($event);

            if ($claimedEvent) {
                return ParentChatbotInboundEventClaim::claimed($claimedEvent);
            }

            $event = static::query()->find($event->getKey());

            if ($event === null) {
                return ParentChatbotInboundEventClaim::processedDuplicate();
            }
        }
    }

    private static function unavailableClaim(self $event, string $payloadHash): ?ParentChatbotInboundEventClaim
    {
        if (! hash_equals($event->payload_hash, $payloadHash)) {
            return ParentChatbotInboundEventClaim::payloadConflict();
        }

        if ($event->processed_at !== null) {
            return ParentChatbotInboundEventClaim::processedDuplicate();
        }

        if ($event->processing_expires_at?->isFuture()) {
            return ParentChatbotInboundEventClaim::busy($event);
        }

        return null;
    }

    private static function takeLease(self $event): ?self
    {
        $token = (string) Str::uuid();
        $now = now();
        $claimed = static::query()
            ->whereKey($event->getKey())
            ->whereNull('processed_at')
            ->where(function ($query) use ($now): void {
                $query->whereNull('processing_expires_at')
                    ->orWhere('processing_expires_at', '<=', $now);
            })
            ->update([
                'processing_token' => $token,
                'processing_started_at' => $now,
                'processing_expires_at' => $now->copy()->addSeconds(self::LEASE_SECONDS),
                'updated_at' => $now,
            ]);

        if ($claimed !== 1) {
            return null;
        }

        return static::query()
            ->whereKey($event->getKey())
            ->where('processing_token', $token)
            ->first();
    }

    public function complete(string $token, string $outcome): bool
    {
        return static::query()
            ->whereKey($this->getKey())
            ->where('processing_token', $token)
            ->whereNull('processed_at')
            ->update([
                'outcome' => $outcome,
                'processed_at' => now(),
                'processing_token' => null,
                'processing_started_at' => null,
                'processing_expires_at' => null,
                'updated_at' => now(),
            ]) === 1;
    }

    public function release(string $token): void
    {
        static::query()
            ->whereKey($this->getKey())
            ->where('processing_token', $token)
            ->whereNull('processed_at')
            ->update([
                'outcome' => 'dispatch_pending',
                'processing_token' => null,
                'processing_started_at' => null,
                'processing_expires_at' => null,
                'updated_at' => now(),
            ]);
    }

    public function hasRecordedResponse(): bool
    {
        return is_string($this->response_ciphertext) && $this->response_ciphertext !== '';
    }

    /**
     * @return array{phone: string, intent: string, outcome: string, reply: string, idempotency_key: string, should_dispatch: bool, disclosure: ?array, authorization_claim: ?array}
     * @throws \UnexpectedValueException When the persisted response cannot be trusted.
     */
    public function recordedResponse(): array
    {
        if (! $this->hasRecordedResponse()) {
            throw new \LogicException('No persisted inbound response is available.');
        }

        try {
            $response = json_decode(Crypt::decryptString($this->response_ciphertext), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            throw new \UnexpectedValueException('The persisted inbound response is unreadable.', previous: $exception);
        }

        if (! is_array($response)) {
            throw new \UnexpectedValueException('The persisted inbound response is invalid.');
        }

        foreach (['phone', 'intent', 'outcome', 'reply', 'idempotency_key'] as $field) {
            if (! is_string($response[$field] ?? null)) {
                throw new \UnexpectedValueException('The persisted inbound response is invalid.');
            }
        }

        if (ParentChatbotIntent::tryFrom($response['intent']) === null) {
            throw new \UnexpectedValueException('The persisted inbound response is invalid.');
        }

        if (! is_bool($response['should_dispatch'] ?? null)) {
            throw new \UnexpectedValueException('The persisted inbound response is invalid.');
        }

        if (isset($response['disclosure']) && ! is_array($response['disclosure'])) {
            throw new \UnexpectedValueException('The persisted inbound response is invalid.');
        }

        if (isset($response['authorization_claim']) && ! is_array($response['authorization_claim'])) {
            throw new \UnexpectedValueException('The persisted inbound response is invalid.');
        }

        $response['disclosure'] = $response['disclosure'] ?? null;
        $response['authorization_claim'] = $response['authorization_claim'] ?? null;

        return $response;
    }

    public function recordResponse(
        string $token,
        string $phone,
        string $intent,
        string $outcome,
        string $reply,
        string $idempotencyKey,
        bool $shouldDispatch,
        ?array $disclosure = null,
        ?array $authorizationClaim = null,
    ): bool {
        $ciphertext = Crypt::encryptString(json_encode([
            'phone' => $phone,
            'intent' => $intent,
            'outcome' => $outcome,
            'reply' => $reply,
            'idempotency_key' => $idempotencyKey,
            'should_dispatch' => $shouldDispatch,
            'disclosure' => $disclosure,
            'authorization_claim' => $authorizationClaim,
        ], JSON_THROW_ON_ERROR));

        return static::query()
            ->whereKey($this->getKey())
            ->where('processing_token', $token)
            ->whereNull('processed_at')
            ->whereNull('response_ciphertext')
            ->update([
                'response_ciphertext' => $ciphertext,
                'response_recorded_at' => now(),
                'updated_at' => now(),
            ]) === 1;
    }

    public function discardRecordedResponse(string $token): bool
    {
        return static::query()
            ->whereKey($this->getKey())
            ->where('processing_token', $token)
            ->whereNull('processed_at')
            ->whereNotNull('response_ciphertext')
            ->update([
                'response_ciphertext' => null,
                'response_recorded_at' => null,
                'updated_at' => now(),
            ]) === 1;
    }

    private static function findOrCreate(string $sourceEventId, string $payloadHash): self
    {
        try {
            return static::query()->firstOrCreate(
                ['source_event_id' => $sourceEventId],
                ['payload_hash' => $payloadHash, 'received_at' => now()],
            );
        } catch (QueryException $exception) {
            $event = static::query()->where('source_event_id', $sourceEventId)->first();

            if ($event) {
                return $event;
            }

            throw $exception;
        }
    }
}
