<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentChatbotLinkCodeIssuance extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PENDING_RECONCILIATION = 'pending_reconciliation';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_FAILED = 'failed';

    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'parent_id',
        'actor_id',
        'parent_chatbot_link_code_id',
        'request_id',
        'status',
        'attempt_count',
        'attempted_at',
        'accepted_at',
        'failed_at',
        'error_code',
        'delivery_payload',
        'delivery_payload_expires_at',
        'delivery_token',
        'delivery_started_at',
        'delivery_lease_expires_at',
        'next_attempt_at',
        'retry_expires_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'failed_at' => 'datetime',
        'delivery_payload_expires_at' => 'datetime',
        'delivery_started_at' => 'datetime',
        'delivery_lease_expires_at' => 'datetime',
        'next_attempt_at' => 'datetime',
        'retry_expires_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ESBTPParent::class, 'parent_id');
    }

    public function linkCode(): BelongsTo
    {
        return $this->belongsTo(ParentChatbotLinkCode::class, 'parent_chatbot_link_code_id');
    }
}
