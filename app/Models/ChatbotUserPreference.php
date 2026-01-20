<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotUserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'preferred_name',
        'response_style',
        'response_tone',
        'clarification_mode',
        'notes',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
