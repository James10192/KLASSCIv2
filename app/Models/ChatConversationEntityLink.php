<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatConversationEntityLink extends Model
{
    protected $table = 'chat_conversation_entity_links';

    protected $fillable = [
        'chat_conversation_id',
        'entity_type',
        'entity_id',
        'entity_label',
        'relation_type',
        'confidence',
        'source_type',
        'source_message_id',
        'related_user_id',
        'required_view_permission',
        'required_detail_permission',
        'created_by',
        'verified_by',
        'verified_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'verified_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    public function sourceMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'source_message_id');
    }

    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return $this->confidence === 'verified' && $this->verified_at !== null;
    }
}
