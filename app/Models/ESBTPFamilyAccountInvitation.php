<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ESBTPFamilyAccountInvitation extends Model
{
    protected $table = 'esbtp_family_account_invitations';

    protected $fillable = [
        'grant_id', 'parent_id', 'user_id', 'issued_by', 'token_hash',
        'request_id', 'recipient_hash', 'encrypted_url', 'status',
        'attempt_count', 'next_attempt_at', 'locked_until', 'expires_at',
        'used_at', 'revoked_at', 'provider_message_id', 'error_code',
    ];

    protected $hidden = ['token_hash', 'encrypted_url', 'recipient_hash'];

    protected $casts = [
        'next_attempt_at' => 'datetime', 'locked_until' => 'datetime',
        'expires_at' => 'datetime', 'used_at' => 'datetime',
        'revoked_at' => 'datetime', 'attempt_count' => 'integer',
    ];

    public function grant(): BelongsTo
    {
        return $this->belongsTo(ESBTPFamilyAccessGrant::class, 'grant_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ESBTPParent::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
