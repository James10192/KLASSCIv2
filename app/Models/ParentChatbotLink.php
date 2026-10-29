<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentChatbotLink extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_STOPPED = 'stopped';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'parent_id', 'phone_hash', 'selected_student_id', 'status', 'last_inbound_at', 'stopped_at', 'revoked_at',
    ];

    protected $casts = [
        'last_inbound_at' => 'datetime',
        'stopped_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ESBTPParent::class, 'parent_id');
    }

    public function selectedStudent(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'selected_student_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
