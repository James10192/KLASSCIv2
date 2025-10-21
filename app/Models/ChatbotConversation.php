<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotConversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'session_id',
        'title',
        'context',
        'last_activity_at',
        'is_active',
    ];

    protected $casts = [
        'context' => 'array',
        'last_activity_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Relation: Appartient à un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation: A plusieurs messages
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'conversation_id');
    }

    /**
     * Relation: A plusieurs actions loguées
     */
    public function actions(): HasMany
    {
        return $this->hasMany(ChatbotActionLog::class, 'conversation_id');
    }

    /**
     * Scope: Conversations actives uniquement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Conversations pour un utilisateur
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
