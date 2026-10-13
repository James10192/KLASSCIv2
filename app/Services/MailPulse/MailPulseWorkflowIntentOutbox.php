<?php

namespace App\Services\MailPulse;

use App\Jobs\MailPulse\DispatchMailPulseDurableWorkflowJob;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Durable handoff from school transactions to MailPulse.
 *
 * A row is committed with the school's business operation BEFORE a worker
 * needs to be available. The optional queued job is only an accelerator:
 * mailpulse:drain-workflow-intents can recover the same row from cron.
 *
 * An intent being processed does NOT mean provider acceptance or delivery.
 * Channel-level parent_notification_logs track provider dispatch separately.
 */
final class MailPulseWorkflowIntentOutbox
{
    private const TABLE = 'mailpulse_workflow_intents';
    private const MAX_ATTEMPTS = 8;
    private const LEASE_SECONDS = 600;

    public function enabled(): bool
    {
        return (bool) config('services.mailpulse.workflow_intents_outbox_enabled', false);
    }

    public function record(MailPulseWorkflowIntent $intent): int
    {
        $data = json_encode([
            'event' => $intent->event,
            'payload' => $intent->payload,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        // Normal DB insert: if caller rolls back an inscription/payment update,
        // the notification intent is rolled back in the SAME transaction.
        $id = DB::table(self::TABLE)->insertGetId([
            'event' => $intent->event,
            'payload_ciphertext' => Crypt::encryptString($data),
            'status' => 'queued',
            'attempts' => 0,
            'next_attempt_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) $id;
    }

    /**
     * Best-effort fast path; cron still sees the persisted row if the queue
     * connection is down or scheduling the callback fails.
     */
    public function enqueueAfterCommit(int $id): void
    {
        // cPanel frequently uses QUEUE_CONNECTION=sync. A synchronous job
        // would still call MailPulse in the payment HTTP request. When no
        // genuine worker is configured, the minute cron is the primary path.
        if (config('queue.default') === 'sync') {
            return;
        }

        try {
            DB::afterCommit(static function () use ($id): void {
                try {
                    DispatchMailPulseDurableWorkflowJob::dispatch($id);
                } catch (Throwable $e) {
                    Log::warning('MailPulse intent persisted but fast queue unavailable', [
                        'intent_id' => $id,
                        'exception' => $e::class,
                    ]);
                }
            });
        } catch (Throwable $e) {
            // The row was already inserted, cron is independent of Redis or
            // the jobs table. Never turn a payment validation into a 500.
            Log::warning('MailPulse intent persisted but enqueue callback unavailable', [
                'intent_id' => $id,
                'exception' => $e::class,
            ]);
        }
    }

    /** @return list<int> */
    public function dueIds(int $limit): array
    {
        $now = now();
        return DB::table(self::TABLE)
            ->where(function ($query) use ($now): void {
                $query->where(function ($q) use ($now): void {
                    $q->whereIn('status', ['queued', 'retry'])
                        ->where('next_attempt_at', '<=', $now);
                })->orWhere(function ($q) use ($now): void {
                    $q->where('status', 'processing')
                        ->where('lease_until', '<=', $now);
                });
            })
            ->orderBy('id')
            ->limit(max(1, min(100, $limit)))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Atomically acquire one row: queued worker + cron can run concurrently
     * without sending the same business intent twice.
     */
    public function process(int $id, MailPulseWorkflowIntentRunner $runner, MailPulseWorkflowPolicy $policy): bool
    {
        if (! $this->enabled() || ! $policy->realWorkflowsEnabled()) {
            return false;
        }

        $now = now();
        $affected = DB::table(self::TABLE)
            ->where('id', $id)
            ->where(function ($query) use ($now): void {
                $query->where(function ($q) use ($now): void {
                    $q->whereIn('status', ['queued', 'retry'])
                        ->where('next_attempt_at', '<=', $now);
                })->orWhere(function ($q) use ($now): void {
                    $q->where('status', 'processing')
                        ->where('lease_until', '<=', $now);
                });
            })
            ->update([
                'status' => 'processing',
                'attempts' => DB::raw('attempts + 1'),
                'lease_until' => now()->addSeconds(self::LEASE_SECONDS),
                'updated_at' => now(),
            ]);

        if ($affected !== 1) {
            return false;
        }

        try {
            $row = DB::table(self::TABLE)->where('id', $id)->first();
            if (! $row) {
                return false;
            }
            $data = json_decode(Crypt::decryptString($row->payload_ciphertext), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($data) || ! is_string($data['event'] ?? null)
                || ! is_array($data['payload'] ?? null)
                || $row->event !== $data['event']) {
                throw new \UnexpectedValueException('Malformed workflow intent payload');
            }

            $runner->run(MailPulseWorkflowIntent::restore($data['event'], $data['payload']));
            // Mark that this intent was handed to its channel-level outboxes;
            // this is NOT a delivered/sent status.
            DB::table(self::TABLE)->where('id', $id)->where('status', 'processing')->update([
                'status' => 'processed',
                'processed_at' => now(),
                'lease_until' => null,
                'next_attempt_at' => null,
                'updated_at' => now(),
            ]);

            return true;
        } catch (Throwable $e) {
            $attempts = (int) (DB::table(self::TABLE)->where('id', $id)->value('attempts') ?? self::MAX_ATTEMPTS);
            $terminal = $attempts >= self::MAX_ATTEMPTS;
            $delay = min(3600, 60 * (2 ** max(0, $attempts - 1)));
            DB::table(self::TABLE)->where('id', $id)->where('status', 'processing')->update([
                'status' => $terminal ? 'failed' : 'retry',
                'lease_until' => null,
                'next_attempt_at' => $terminal ? null : now()->addSeconds($delay),
                'last_error' => $e::class, // never persist exception messages with family PII
                'updated_at' => now(),
            ]);
            Log::error('MailPulse durable workflow intent failed', [
                'intent_id' => $id,
                'event' => $row->event ?? null,
                'attempts' => $attempts,
                'terminal' => $terminal,
                'exception' => $e::class,
            ]);

            return false;
        }
    }
}
