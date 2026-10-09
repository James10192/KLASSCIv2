<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AdmissionActivationDispatch extends Model
{
    protected $fillable = [
        'workflow_id',
        'channel',
        'request_id',
        'status',
        'provider_message_id',
        'provider_dispatch_state',
        'http_status',
        'error_code',
        'encrypted_payload',
        'token_version',
        'attempt_count',
        'next_attempt_at',
        'locked_until',
        'expires_at',
    ];

    protected $casts = [
        'http_status' => 'integer',
        'attempt_count' => 'integer',
        'next_attempt_at' => 'datetime',
        'locked_until' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ESBTPCandidatureWorkflow::class, 'workflow_id');
    }
}
