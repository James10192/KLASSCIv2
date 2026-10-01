<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $table = 'chat_messages';

    protected $fillable = ['chat_conversation_id', 'sender_id', 'type', 'body', 'payload'];

    protected $casts = ['payload' => 'array'];

    protected static function booted(): void
    {
        static::created(function (ChatMessage $message): void {
            if ($message->type !== 'action_card') {
                return;
            }

            try {
                app(\App\Services\Messages\ConversationEntityLinkService::class)->ensureForMessage($message);
            } catch (\Throwable $e) {
                // Une liaison de contexte ne doit jamais faire échouer l'envoi du message.
                // Le message restera visible comme « Lien à vérifier » jusqu'à réparation.
                \Illuminate\Support\Facades\Log::warning('messages.entity_link_creation_failed', [
                    'message_id' => $message->id,
                    'conversation_id' => $message->chat_conversation_id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
