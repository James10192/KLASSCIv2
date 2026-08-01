<?php

namespace App\Services\ParentChatbot;

use App\Models\ESBTPParent;
use App\Models\ParentChatbotLink;
use App\Models\ParentChatbotLinkCodeIssuance;
use App\Models\ParentChatbotOnboardingBatch;
use App\Models\ParentChatbotOnboardingItem;
use App\Services\MailPulse\MailPulseWorkflowPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ParentChatbotOnboardingService
{
    private const CLAIM_LEASE_MINUTES = 5;
    private const SNAPSHOT_CHUNK_SIZE = 500;
    private const SUBMITTED_STATUS = 'submitted';

    public function __construct(
        private ParentChatbotLinkCodeDeliveryService $delivery,
        private MailPulseWorkflowPolicy $workflowPolicy,
        private ParentChatbotPhoneNormalizer $phones,
    ) {
    }

    public function start(?int $actorId): ParentChatbotOnboardingBatch
    {
        $this->assertStartIsConfigured();

        try {
            return DB::transaction(function () use ($actorId): ParentChatbotOnboardingBatch {
                $existing = ParentChatbotOnboardingBatch::query()
                    ->where('status', ParentChatbotOnboardingBatch::STATUS_PROCESSING)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    throw new RuntimeException('Une activation massive est déjà en cours.');
                }

                $batch = ParentChatbotOnboardingBatch::create([
                    'actor_id' => $actorId,
                    'status' => ParentChatbotOnboardingBatch::STATUS_PROCESSING,
                    'processing_slot' => 1,
                    'started_at' => now(),
                ]);
                $total = $this->snapshotEligibleParents($batch);
                $batch->update(['total_count' => $total, 'pending_count' => $total]);

                if ($total === 0) {
                    $batch->update([
                        'status' => ParentChatbotOnboardingBatch::STATUS_COMPLETED,
                        'processing_slot' => null,
                        'completed_at' => now(),
                    ]);
                }

                return $batch->fresh() ?? $batch;
            });
        } catch (QueryException $exception) {
            if (ParentChatbotOnboardingBatch::query()->where('status', ParentChatbotOnboardingBatch::STATUS_PROCESSING)->exists()) {
                throw new RuntimeException('Une activation massive est déjà en cours.', previous: $exception);
            }

            throw $exception;
        }
    }

    public function cancel(ParentChatbotOnboardingBatch $batch): ParentChatbotOnboardingBatch
    {
        return DB::transaction(function () use ($batch): ParentChatbotOnboardingBatch {
            $batch = ParentChatbotOnboardingBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if (! $batch->isProcessing()) {
                return $batch;
            }

            $now = now();
            $batch->update([
                'status' => ParentChatbotOnboardingBatch::STATUS_CANCELLED,
                'processing_slot' => null,
                'cancelled_at' => $now,
            ]);
            ParentChatbotOnboardingItem::query()
                ->where('batch_id', $batch->id)
                ->where('status', ParentChatbotOnboardingItem::STATUS_PENDING)
                ->update([
                    'status' => ParentChatbotOnboardingItem::STATUS_SKIPPED,
                    'error_code' => 'batch_cancelled',
                    'updated_at' => $now,
                ]);

            $this->refreshBatch($batch->fresh() ?? $batch);

            return $batch->fresh() ?? $batch;
        });
    }

    /** @return array{claimed: int, synced: int} */
    public function process(int $limit = 25): array
    {
        $limit = max(1, min(100, $limit));
        $affectedBatchIds = collect();
        $synced = $this->settleCancelledClaims($limit, $affectedBatchIds);
        $synced += $this->syncAwaitingItems($limit, $affectedBatchIds);
        $claimed = $this->processClaimedItems($limit, $affectedBatchIds);
        $synced += $this->syncAwaitingItems($limit, $affectedBatchIds);
        $this->refreshBatches($affectedBatchIds);

        return ['claimed' => $claimed, 'synced' => $synced];
    }

    private function snapshotEligibleParents(ParentChatbotOnboardingBatch $batch): int
    {
        $total = 0;
        $now = now()->toDateTimeString();
        $this->eligibleParents()->chunkById(self::SNAPSHOT_CHUNK_SIZE, function (Collection $parents) use ($batch, $now, &$total): void {
            $rows = $this->snapshotRows($batch, $parents, $now);
            if ($rows !== []) {
                ParentChatbotOnboardingItem::query()->insert($rows);
            }
            $total += count($rows);
        }, 'esbtp_parents.id', 'id');

        return $total;
    }

    /** @return Builder<ESBTPParent> */
    private function eligibleParents(): Builder
    {
        return ESBTPParent::query()
            ->select('esbtp_parents.*')
            ->whereNotNull('telephone')
            ->where('telephone', '<>', '')
            ->whereHas('pupilles')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('parent_chatbot_links')
                    ->whereColumn('parent_chatbot_links.parent_id', 'esbtp_parents.id')
                    ->whereIn('parent_chatbot_links.status', [
                        ParentChatbotLink::STATUS_ACTIVE,
                        ParentChatbotLink::STATUS_STOPPED,
                    ]);
            })
            ->orderBy('esbtp_parents.id');
    }

    /** @param Collection<int, int> $batchIds */
    private function processClaimedItems(int $limit, Collection $batchIds): int
    {
        $claimed = 0;
        for ($position = 0; $position < $limit; $position++) {
            $item = $this->claimNextItem();
            if ($item === null) {
                break;
            }

            $batchIds->push($item->batch_id);
            $this->deliverClaimedItem($item);
            $claimed++;
        }

        return $claimed;
    }

    private function claimNextItem(): ?ParentChatbotOnboardingItem
    {
        $itemId = $this->claimableItems()->value('id');
        if ($itemId === null) {
            return null;
        }

        $token = (string) Str::uuid();
        $now = now();
        $updated = $this->claimableItems()->whereKey($itemId)->update([
            'status' => ParentChatbotOnboardingItem::STATUS_PROCESSING,
            'attempt_count' => DB::raw('attempt_count + 1'),
            'attempted_at' => $now,
            'lease_token' => $token,
            'lease_expires_at' => $now->copy()->addMinutes(self::CLAIM_LEASE_MINUTES),
            'updated_at' => $now,
        ]);

        return $updated === 1
            ? ParentChatbotOnboardingItem::query()->whereKey($itemId)->where('lease_token', $token)->first()
            : null;
    }

    /** @return Builder<ParentChatbotOnboardingItem> */
    private function claimableItems(): Builder
    {
        $now = now();

        return ParentChatbotOnboardingItem::query()
            ->whereHas('batch', fn (Builder $query) => $query->where('status', ParentChatbotOnboardingBatch::STATUS_PROCESSING))
            ->where(function (Builder $query) use ($now): void {
                $query->where('status', ParentChatbotOnboardingItem::STATUS_PENDING)
                    ->orWhere(function (Builder $query) use ($now): void {
                        $query->whereIn('status', [
                            ParentChatbotOnboardingItem::STATUS_PROCESSING,
                            self::SUBMITTED_STATUS,
                        ])->where('lease_expires_at', '<=', $now);
                    });
            })
            ->orderBy('id');
    }

    private function deliverClaimedItem(ParentChatbotOnboardingItem $item): void
    {
        $item = $this->markClaimSubmitted($item);
        if ($item === null) {
            return;
        }

        $parent = ESBTPParent::query()->find($item->parent_id);
        if ($parent === null) {
            $this->completeClaim($item, ParentChatbotOnboardingItem::STATUS_FAILED, null, 'parent_unavailable');
            return;
        }

        try {
            $issuance = $this->delivery->issueAndDeliver($parent, $item->batch->actor_id, $item->request_id);
        } catch (\Throwable) {
            $issuance = ParentChatbotLinkCodeIssuance::query()->where('request_id', $item->request_id)->first();
        }

        [$status, $errorCode] = $this->itemOutcome($issuance);
        $this->completeClaim($item, $status, $issuance?->id, $errorCode);
    }

    private function markClaimSubmitted(ParentChatbotOnboardingItem $item): ?ParentChatbotOnboardingItem
    {
        return DB::transaction(function () use ($item): ?ParentChatbotOnboardingItem {
            $batch = ParentChatbotOnboardingBatch::query()->whereKey($item->batch_id)->lockForUpdate()->first();
            $claimed = ParentChatbotOnboardingItem::query()->whereKey($item->id)->lockForUpdate()->first();

            if ($batch === null || $claimed === null || $claimed->lease_token !== $item->lease_token) {
                return null;
            }

            if (! $batch->isProcessing()) {
                $claimed->update([
                    'status' => ParentChatbotOnboardingItem::STATUS_SKIPPED,
                    'error_code' => 'batch_cancelled',
                    'lease_token' => null,
                    'lease_expires_at' => null,
                ]);

                return null;
            }

            if ($claimed->status !== ParentChatbotOnboardingItem::STATUS_PROCESSING || $claimed->lease_expires_at?->isPast()) {
                return null;
            }

            $claimed->update(['status' => self::SUBMITTED_STATUS]);

            return $claimed->fresh();
        });
    }

    private function completeClaim(
        ParentChatbotOnboardingItem $item,
        string $status,
        ?int $issuanceId,
        ?string $errorCode,
    ): void {
        ParentChatbotOnboardingItem::query()
            ->whereKey($item->id)
            ->where('lease_token', $item->lease_token)
            ->where('status', self::SUBMITTED_STATUS)
            ->update([
                'issuance_id' => $issuanceId,
                'status' => $status,
                'error_code' => $errorCode,
                'lease_token' => null,
                'lease_expires_at' => null,
                'updated_at' => now(),
            ]);
    }

    /** @return array{0: string, 1: ?string} */
    private function itemOutcome(?ParentChatbotLinkCodeIssuance $issuance): array
    {
        return match ($issuance?->status) {
            ParentChatbotLinkCodeIssuance::STATUS_ACCEPTED => [ParentChatbotOnboardingItem::STATUS_ACCEPTED, null],
            ParentChatbotLinkCodeIssuance::STATUS_PENDING,
            ParentChatbotLinkCodeIssuance::STATUS_PENDING_RECONCILIATION => [ParentChatbotOnboardingItem::STATUS_AWAITING, null],
            ParentChatbotLinkCodeIssuance::STATUS_BLOCKED => [ParentChatbotOnboardingItem::STATUS_SKIPPED, $issuance->error_code],
            ParentChatbotLinkCodeIssuance::STATUS_FAILED => [ParentChatbotOnboardingItem::STATUS_FAILED, $issuance->error_code],
            default => [ParentChatbotOnboardingItem::STATUS_FAILED, 'delivery_unresolved'],
        };
    }

    /** @param Collection<int, int> $batchIds */
    private function settleCancelledClaims(int $limit, Collection $batchIds): int
    {
        $now = now();
        $settled = 0;
        ParentChatbotOnboardingItem::query()
            ->whereIn('status', [ParentChatbotOnboardingItem::STATUS_PROCESSING, self::SUBMITTED_STATUS])
            ->where('lease_expires_at', '<=', $now)
            ->whereHas('batch', fn (Builder $query) => $query->where('status', ParentChatbotOnboardingBatch::STATUS_CANCELLED))
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (ParentChatbotOnboardingItem $item) use ($batchIds, &$settled): void {
                $issuance = ParentChatbotLinkCodeIssuance::query()->where('request_id', $item->request_id)->first();
                [$status, $errorCode] = $issuance === null
                    ? [ParentChatbotOnboardingItem::STATUS_SKIPPED, 'batch_cancelled']
                    : $this->itemOutcome($issuance);
                $updated = ParentChatbotOnboardingItem::query()
                    ->whereKey($item->id)
                    ->whereIn('status', [ParentChatbotOnboardingItem::STATUS_PROCESSING, self::SUBMITTED_STATUS])
                    ->where('lease_expires_at', '<=', now())
                    ->update([
                        'issuance_id' => $issuance?->id,
                        'status' => $status,
                        'error_code' => $errorCode,
                        'lease_token' => null,
                        'lease_expires_at' => null,
                        'updated_at' => now(),
                    ]);

                if ($updated === 1) {
                    $batchIds->push($item->batch_id);
                    $settled++;
                }
            });

        return $settled;
    }

    /** @param Collection<int, int> $batchIds */
    private function syncAwaitingItems(int $limit, Collection $batchIds): int
    {
        $synced = 0;
        ParentChatbotOnboardingItem::query()
            ->with('issuance')
            ->where('status', ParentChatbotOnboardingItem::STATUS_AWAITING)
            ->whereNotNull('issuance_id')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (ParentChatbotOnboardingItem $item) use ($batchIds, &$synced): void {
                [$status, $errorCode] = $this->itemOutcome($item->issuance);
                if ($status === ParentChatbotOnboardingItem::STATUS_AWAITING) {
                    return;
                }

                $item->update(['status' => $status, 'error_code' => $errorCode]);
                $batchIds->push($item->batch_id);
                $synced++;
            });

        return $synced;
    }

    /** @param Collection<int, int> $batchIds */
    private function refreshBatches(Collection $batchIds): void
    {
        $batchIds->filter()->unique()->each(function (int $batchId): void {
            $batch = ParentChatbotOnboardingBatch::query()->find($batchId);
            if ($batch !== null) {
                $this->refreshBatch($batch);
            }
        });
    }

    private function refreshBatch(ParentChatbotOnboardingBatch $batch): void
    {
        $items = ParentChatbotOnboardingItem::query()->where('batch_id', $batch->id);
        $pending = (clone $items)->whereIn('status', [
            ParentChatbotOnboardingItem::STATUS_PENDING,
            ParentChatbotOnboardingItem::STATUS_PROCESSING,
            self::SUBMITTED_STATUS,
            ParentChatbotOnboardingItem::STATUS_AWAITING,
        ])->count();
        $updates = [
            'total_count' => (clone $items)->count(),
            'pending_count' => $pending,
            'accepted_count' => (clone $items)->where('status', ParentChatbotOnboardingItem::STATUS_ACCEPTED)->count(),
            'failed_count' => (clone $items)->where('status', ParentChatbotOnboardingItem::STATUS_FAILED)->count(),
            'skipped_count' => (clone $items)->where('status', ParentChatbotOnboardingItem::STATUS_SKIPPED)->count(),
        ];

        if ($batch->isProcessing() && $pending === 0) {
            $updates['status'] = ParentChatbotOnboardingBatch::STATUS_COMPLETED;
            $updates['processing_slot'] = null;
            $updates['completed_at'] = now();
        }

        $batch->update($updates);
    }

    /** @return array<int, array<string, int|string>> */
    private function snapshotRows(ParentChatbotOnboardingBatch $batch, Collection $parents, string $now): array
    {
        $phoneHashes = $parents->mapWithKeys(function (ESBTPParent $parent): array {
            $phone = $this->phones->normalize((string) $parent->telephone);

            return [$parent->id => $phone === null ? null : $this->phones->hash($phone)];
        });
        $revoked = ParentChatbotLink::query()
            ->whereIn('parent_id', $parents->pluck('id'))
            ->where('status', ParentChatbotLink::STATUS_REVOKED)
            ->get(['parent_id', 'phone_hash'])
            ->groupBy('parent_id');

        return $parents->reject(function (ESBTPParent $parent) use ($phoneHashes, $revoked): bool {
            $phoneHash = $phoneHashes->get($parent->id);

            return $phoneHash !== null && $revoked->get($parent->id, collect())->contains('phone_hash', $phoneHash);
        })->map(fn (ESBTPParent $parent): array => [
            'batch_id' => $batch->id,
            'parent_id' => $parent->id,
            'request_id' => $this->requestId($batch->id, $parent->id),
            'status' => ParentChatbotOnboardingItem::STATUS_PENDING,
            'attempt_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all();
    }

    private function requestId(int $batchId, int $parentId): string
    {
        return "klassci-parent-onboarding-{$batchId}-{$parentId}";
    }

    private function assertStartIsConfigured(): void
    {
        if (! $this->workflowPolicy->realWorkflowsEnabled()) {
            throw new RuntimeException('Les workflows parents MailPulse ne sont pas activés.');
        }

        if (trim((string) config('services.mailpulse.parent_chatbot_link_template_name', '')) === '') {
            throw new RuntimeException('Le modèle de liaison parent MailPulse est requis.');
        }

        $missing = collect([
            config('services.mailpulse.parent_chatbot_service_secret', ''),
            config('services.mailpulse.parent_chatbot_code_pepper', ''),
            config('services.mailpulse.parent_chatbot_phone_hash_key', ''),
        ])->contains(fn ($value): bool => strlen((string) $value) < 32);

        if ($missing) {
            throw new RuntimeException('La configuration de sécurité MailPulse est incomplète.');
        }
    }
}
