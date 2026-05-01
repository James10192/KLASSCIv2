<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatConversation extends Model
{
    protected $table = 'chat_conversations';

    protected $fillable = ['type', 'title', 'context', 'last_message_at'];

    protected $casts = [
        'context' => 'array',
        'last_message_at' => 'datetime',
    ];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_conversation_participants', 'chat_conversation_id', 'user_id')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_conversation_id')->orderBy('created_at');
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'chat_conversation_id')->latestOfMany();
    }

    public function unreadCountFor(User $user): int
    {
        $pivot = $this->participants()->where('user_id', $user->id)->first()?->pivot;
        if (!$pivot) {
            return 0;
        }
        $query = $this->messages()->where('sender_id', '!=', $user->id);
        if ($pivot->last_read_at) {
            $query->where('created_at', '>', $pivot->last_read_at);
        }
        return $query->count();
    }
}
