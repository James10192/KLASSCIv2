<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentChatbotOnboardingItem extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_AWAITING = 'awaiting';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'batch_id',
        'parent_id',
        'issuance_id',
        'request_id',
        'status',
        'attempt_count',
        'attempted_at',
        'error_code',
        'lease_token',
        'lease_expires_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
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
}
