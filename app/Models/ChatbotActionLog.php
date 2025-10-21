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
        'action_type',
        'model_type',
        'model_id',
        'action_data',
        'status',
        'error_message',
    ];

    protected $casts = [
        'action_data' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByActionType($query, string $type)
    {
        return $query->where('action_type', $type);
    }
}
