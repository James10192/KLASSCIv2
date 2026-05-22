<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPLMDJuryMembre extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_lmd_jury_membres';

    protected $fillable = [
        'jury_id', 'user_id', 'role', 'present',
        'signature_data', 'signature_at', 'signature_ip', 'signature_user_agent',
        'notes',
    ];

    protected $casts = [
        'present' => 'boolean',
        'signature_at' => 'datetime',
    ];

    protected $auditInclude = [
        'role',
        'present',
        'signature_at',
        'signature_ip',
    ];

    public const ROLES = ['president', 'assesseur', 'secretaire', 'consultatif'];

    public function jury(): BelongsTo
    {
        return $this->belongsTo(ESBTPLMDJury::class, 'jury_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hasSigned(): bool
    {
        return $this->signature_at !== null;
    }
}
