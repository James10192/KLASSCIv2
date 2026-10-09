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
    ];

    protected $casts = [
        'http_status' => 'integer',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ESBTPCandidatureWorkflow::class, 'workflow_id');
    }
}
