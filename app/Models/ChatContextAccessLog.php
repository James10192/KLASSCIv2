<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatContextAccessLog extends Model
{
    public $timestamps = false;

    protected $table = 'chat_context_access_logs';

    protected $fillable = [
        'user_id',
        'chat_conversation_id',
        'entity_type',
        'entity_id',
        'purpose',
        'allowed',
        'permission_checked',
        'created_at',
    ];

    protected $casts = [
        'allowed' => 'boolean',
        'created_at' => 'datetime',
    ];
}
