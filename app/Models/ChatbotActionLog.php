<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotActionLog extends Model
{
    use HasFactory;

    protected $table = 'chatbot_actions_log';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'approved_by',
        'action_type',
        'model_type',
        'model_id',
        'action_data',
        'idempotency_key',
        'status',
        'error_message',
        'approved_at',
        'rejected_at',
        'expires_at',
    ];

    protected $casts = [
        'action_data' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeProposed($query)
    {
        return $query->where('status', 'proposed');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
                ->orWhere(fn ($nested) => $nested->where('status', 'proposed')->where('expires_at', '<=', now()));
        });
    }

    public function scopeByActionType($query, string $type)
    {
        return $query->where('action_type', $type);
    }
}
