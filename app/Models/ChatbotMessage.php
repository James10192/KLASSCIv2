<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'metadata',
        'display_type',
        'display_data',
        'deep_link',
    ];

    protected $casts = [
        'metadata' => 'array',
        'display_data' => 'array',
    ];

    /**
     * Relation: Appartient à une conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }

    /**
     * Scope: Messages utilisateur uniquement
     */
    public function scopeUser($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Scope: Messages assistant uniquement
     */
    public function scopeAssistant($query)
    {
        return $query->where('role', 'assistant');
    }
}
