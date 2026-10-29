<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ParentChatbotOnboardingItem extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_AWAITING = 'awaiting';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_MANUAL_RECONCILIATION = 'manual_reconciliation';
    public const MAX_ACTIVATION_ATTEMPTS = 5;

    protected $fillable = [
        'batch_id',
        'parent_id',
        'issuance_id',
        'request_id',
        'status',
        'attempt_count',
        'attempted_at',
        'next_attempt_at',
        'error_code',
        'lease_token',
        'lease_expires_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'next_attempt_at' => 'datetime',
        'lease_expires_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ParentChatbotOnboardingBatch::class, 'batch_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ESBTPParent::class, 'parent_id');
    }

    public function issuance(): BelongsTo
    {
        return $this->belongsTo(ParentChatbotLinkCodeIssuance::class, 'issuance_id');
    }

    public function releaseAfterActivationFailure(): void
    {
        DB::transaction(function (): void {
            $batch = ParentChatbotOnboardingBatch::query()->whereKey($this->batch_id)->lockForUpdate()->first();
            $claimed = self::query()
                ->whereKey($this->id)
                ->where('lease_token', $this->lease_token)
                ->where('status', self::STATUS_SUBMITTED)
                ->lockForUpdate()
                ->first();
            if ($batch === null || $claimed === null) {
                return;
            }

            $cancelled = ! $batch->isProcessing();
            $exhausted = $claimed->attempt_count >= self::MAX_ACTIVATION_ATTEMPTS;
            $claimed->update([
                'status' => $cancelled ? self::STATUS_SKIPPED : ($exhausted ? self::STATUS_FAILED : self::STATUS_PENDING),
                'error_code' => $cancelled ? 'batch_cancelled' : ($exhausted ? 'activation_retry_exhausted' : 'activation_retry_pending'),
                'next_attempt_at' => $cancelled || $exhausted ? null : now()->addSeconds(self::retryDelaySeconds($claimed->attempt_count)),
                'lease_token' => null,
                'lease_expires_at' => null,
            ]);
        }, 3);
    }

    private static function retryDelaySeconds(int $attemptCount): int
    {
        return min(3600, 30 * (2 ** max(0, $attemptCount - 1)));
    }
}
