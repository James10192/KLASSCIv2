<?php

namespace App\Services\MailPulse;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPParent;
use App\Models\ParentNotificationLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MailPulseParentNotificationLog
{
    private const MAX_ATTEMPTS = 5;
    private const RETRY_TTL_HOURS = 24;
    private const LEASE_SECONDS = 120;

    public function __construct(private MailPulseWorkflowPolicy $workflowPolicy) {}

    public function requestId(string $event, string $channel, ESBTPParent $parent, ESBTPEtudiant $student, array $message): string
    {
        $identity = [
            'tenant_code' => MailPulseTenantContext::code(),
            'event' => $event,
            'channel' => $channel,
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'metadata' => $this->canonicalize($message['metadata'] ?? []),
        ];
        $encoded = json_encode($identity, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return MailPulseTenantContext::scopedIdentifier(
            $channel . '-' . substr(hash('sha256', $encoded ?: serialize($identity)), 0, 48)
        );
    }

    /** @return array{0: ?ParentNotificationLog, 1: bool, 2: bool} */
    public function start(
        string $requestId,
        string $event,
        string $channel,
        array $payload,
        ESBTPParent $parent,
        ESBTPEtudiant $student,
        array $message
    ): array {
        try {
            $log = ParentNotificationLog::firstOrCreate(
                ['request_id' => $requestId],
                $this->newOutboxAttributes($requestId, $event, $channel, $payload, $parent, $student, $message)
            );

            return [$log, ! $log->wasRecentlyCreated, true];
        } catch (QueryException) {
            try {
                $log = ParentNotificationLog::where('request_id', $requestId)->first();
                if ($log) {
                    return [$log, true, true];
                }
            } catch (QueryException $exception) {
                Log::warning('MailPulse parent notification outbox recovery failed', [
                    'request_id' => $requestId,
                    'error' => $exception->getMessage(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('MailPulse parent notification outbox could not be created', [
                'request_id' => $requestId,
                'error' => $exception->getMessage(),
            ]);
        }

        return [null, false, false];
    }

    public function finish(?ParentNotificationLog $log, MailPulseResult $result, string $requestId): void
    {
        if (! $log) {
            return;
        }

        $metadata = $this->resultMetadata($log, $result, $requestId);
        if ($result->isDispatchAccepted()) {
            $this->complete($log, array_merge($metadata, $this->terminalAttributes('sent', null, $result->id)));
            return;
        }

        if ($this->isRetryable($result)) {
            $this->scheduleRetry($log, $metadata, $result);
            return;
        }

        $error = $result->errorCode ?? $result->status;
        $this->complete($log, array_merge($metadata, $this->terminalAttributes('failed', $error, $result->id)));
    }

    public function retryPending(ParentNotificationLog $log, MailPulseClient $client): bool
    {
        $claimed = $this->claim($log->id);
        if (! $claimed) {
            return false;
        }

        $payload = $this->decryptPayload($claimed);
        if (! $this->hasValidPayload($claimed->request_id, $claimed->channel, $payload)) {
            $this->complete($claimed, $this->terminalAttributes('failed', 'mailpulse_retry_payload_missing'));
            return true;
        }

        $event = ($claimed->metadata ?? [])['workflow_event'] ?? null;
        $parent = ESBTPParent::find($claimed->parent_id);
        if (! is_string($event) || ! $parent) {
            $metadata = $claimed->metadata ?? [];
            $metadata['mailpulse_retry_suppressed_at'] = now()->toIso8601String();
            $this->complete($claimed, array_merge(
                ['metadata' => $metadata],
                $this->terminalAttributes('failed', 'mailpulse_retry_suppressed_by_consent')
            ));
            return true;
        }

        $gate = $this->workflowPolicy->dispatchIfAllowed(
            $parent,
            (int) $claimed->etudiant_id,
            $event,
            $claimed->channel,
            $payload,
            fn (): MailPulseResult => $this->dispatch($client, $claimed->channel, $payload, $claimed->request_id),
        );
        if (! $gate['allowed'] || ! $gate['publication_eligible']) {
            $metadata = $claimed->metadata ?? [];
            $metadata['mailpulse_retry_suppressed_at'] = now()->toIso8601String();
            $this->complete($claimed, array_merge(
                ['metadata' => $metadata],
                $this->terminalAttributes(
                    'failed',
                    $gate['publication_eligible'] ? 'mailpulse_retry_suppressed_by_consent' : 'mailpulse_retry_suppressed_by_publication',
                )
            ));

            return true;
        }

        $result = $gate['result'];
        $this->finish($claimed, $result, $claimed->request_id);

        return true;
    }

    public function expireOutboxEntries(): int
    {
        return ParentNotificationLog::query()
            ->where('status', 'pending')
            ->where('metadata->provider', 'mailpulse')
            ->where(function ($query): void {
                $query->where('attempt_count', '>=', self::MAX_ATTEMPTS)
                    ->orWhere('retry_expires_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('dispatch_lease_expires_at')
                    ->orWhere('dispatch_lease_expires_at', '<=', now());
            })
            ->update($this->terminalAttributes('failed', 'mailpulse_retry_exhausted'));
    }

    private function newOutboxAttributes(
        string $requestId,
        string $event,
        string $channel,
        array $payload,
        ESBTPParent $parent,
        ESBTPEtudiant $student,
        array $message
    ): array {
        return [
            'parent_id' => $parent->id,
            'etudiant_id' => $student->id,
            'notification_type' => $this->notificationType($event),
            'channel' => $channel,
            'status' => 'pending',
            'recipient' => $this->auditRecipient((string) ($payload['recipient']['value'] ?? '')),
            'message_preview' => 'workflow:' . $event,
            'metadata' => [
                'provider' => 'mailpulse',
                'tenant_code' => MailPulseTenantContext::code(),
                'request_id' => $requestId,
                'workflow_event' => $event,
            ],
            'retry_payload' => Crypt::encryptString($this->encodePayload($payload)),
            'attempt_count' => 1,
            'next_attempt_at' => now(),
            'retry_expires_at' => now()->addHours(self::RETRY_TTL_HOURS),
            'dispatch_lease_token' => (string) Str::uuid(),
            'dispatch_lease_expires_at' => now()->addSeconds(self::LEASE_SECONDS),
        ];
    }

    private function auditRecipient(string $recipient): ?string
    {
        $normalized = trim(mb_strtolower($recipient));
        if ($normalized === '') {
            return null;
        }

        return 'sha256:' . substr(hash_hmac('sha256', $normalized, (string) config('app.key')), 0, 32);
    }

    private function claim(int $id): ?ParentNotificationLog
    {
        $now = now();
        $token = (string) Str::uuid();
        $updated = ParentNotificationLog::query()
            ->whereKey($id)
            ->where('status', 'pending')
            ->where('metadata->provider', 'mailpulse')
            ->where('attempt_count', '<', self::MAX_ATTEMPTS)
            ->where('retry_expires_at', '>', $now)
            ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('dispatch_lease_expires_at')->orWhere('dispatch_lease_expires_at', '<=', $now))
            ->update([
                'attempt_count' => DB::raw('attempt_count + 1'),
                'dispatch_lease_token' => $token,
                'dispatch_lease_expires_at' => $now->copy()->addSeconds(self::LEASE_SECONDS),
                'next_attempt_at' => null,
                'updated_at' => $now,
            ]);

        return $updated === 1
            ? ParentNotificationLog::whereKey($id)->where('dispatch_lease_token', $token)->first()
            : null;
    }

    private function scheduleRetry(ParentNotificationLog $log, array $metadata, MailPulseResult $result): void
    {
        if ($log->attempt_count >= self::MAX_ATTEMPTS || $log->retry_expires_at?->isPast()) {
            $this->complete($log, array_merge(
                $metadata,
                $this->terminalAttributes('failed', 'mailpulse_retry_exhausted', $result->id)
            ));
            return;
        }

        $metadata['metadata']['mailpulse_dispatch_state'] = 'pending_reconciliation';
        $this->complete($log, array_merge($metadata, [
            'status' => 'pending',
            'external_id' => $result->id,
            'error_message' => null,
            'next_attempt_at' => now()->addSeconds($this->backoffSeconds($log->attempt_count)),
            'dispatch_lease_token' => null,
            'dispatch_lease_expires_at' => null,
        ]));
    }

    private function complete(ParentNotificationLog $log, array $attributes): void
    {
        $query = ParentNotificationLog::query()->whereKey($log->id);
        if ($log->dispatch_lease_token) {
            $query->where('dispatch_lease_token', $log->dispatch_lease_token);
        }
        $query->update($attributes);
    }

    private function terminalAttributes(string $status, ?string $error, ?string $externalId = null): array
    {
        return [
            'status' => $status,
            'external_id' => $externalId,
            'error_message' => $error,
            'retry_payload' => null,
            'next_attempt_at' => null,
            'retry_expires_at' => null,
            'dispatch_lease_token' => null,
            'dispatch_lease_expires_at' => null,
            'sent_at' => $status === 'sent' ? now() : null,
            'failed_at' => $status === 'failed' ? now() : null,
        ];
    }

    private function resultMetadata(ParentNotificationLog $log, MailPulseResult $result, string $requestId): array
    {
        $metadata = $log->metadata ?? [];
        $metadata['mailpulse_request_id'] = $result->requestId ?? $requestId;
        $metadata['mailpulse_status'] = $result->status;
        $metadata['mailpulse_http_status'] = $result->httpStatus;
        $metadata['mailpulse_dispatch_state'] = $result->dispatchState;

        return ['metadata' => array_filter($metadata, fn ($value) => $value !== null)];
    }

    private function isRetryable(MailPulseResult $result): bool
    {
        if (in_array($result->dispatchState, ['pending', 'pending_reconciliation'], true)) {
            return true;
        }

        return $result->httpStatus === null
            || in_array($result->httpStatus, [408, 429], true)
            || ($result->httpStatus >= 500 && $result->httpStatus <= 599)
            || in_array($result->status, ['connection_failed', 'request_timeout', 'rate_limited', 'provider_unavailable'], true);
    }

    private function decryptPayload(ParentNotificationLog $log): ?array
    {
        try {
            $decoded = json_decode(
                Crypt::decryptString((string) $log->retry_payload),
                true,
                32,
                JSON_THROW_ON_ERROR
            );

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function hasValidPayload(?string $requestId, string $channel, ?array $payload): bool
    {
        return is_string($requestId)
            && $requestId !== ''
            && is_array($payload)
            && ($payload['channel'] ?? null) === $channel
            && is_array($payload['recipient'] ?? null)
            && is_array($payload['content'] ?? null);
    }

    private function dispatch(MailPulseClient $client, string $channel, array $payload, string $requestId): MailPulseResult
    {
        return match ($channel) {
            'email' => $client->sendEmailMessage($payload, $requestId),
            'whatsapp' => $client->sendWhatsAppMessage($payload, $requestId),
            'sms' => $client->sendSmsMessage($payload, $requestId),
            default => new MailPulseResult(false, 'unsupported_channel', 422, $requestId),
        };
    }

    private function notificationType(string $event): string
    {
        return match ($event) {
            'payment_received' => 'paiement_valide',
            'fee_reminder' => 'rappel_paiement',
            'absence_reported' => 'absence',
            'grade_published' => 'note_publiee',
            'bulletin_published' => 'bulletin_publie',
            default => $event,
        };
    }

    private function encodePayload(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function backoffSeconds(int $attemptCount): int
    {
        return min(3600, 30 * (2 ** max(0, $attemptCount - 1)));
    }

    private function canonicalize(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->canonicalize($item);
            }
        }
        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
