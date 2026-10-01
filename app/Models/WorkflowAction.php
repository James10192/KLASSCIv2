<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowAction extends Model
{
    protected $fillable = [
        'action_type',
        'title',
        'description',
        'priority',
        'status',
        'service',
        'assigned_to',
        'created_by',
        'chat_conversation_id',
        'context_type',
        'context_id',
        'context_data',
        'due_at',
        'completed_at',
    ];

    protected $casts = [
        'context_data' => 'array',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(WorkflowActionActivity::class)->latest();
    }
}
