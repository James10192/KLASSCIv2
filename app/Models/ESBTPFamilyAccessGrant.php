<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ESBTPFamilyAccessGrant extends Model
{
    protected $table = 'esbtp_family_access_grants';

    protected $fillable = [
        'parent_id', 'etudiant_id', 'verified_by', 'verified_at',
        'student_consent_at', 'evidence_type', 'evidence_hash', 'consent_hash',
        'expires_at', 'revoked_at', 'revoked_by',
    ];

    protected $hidden = ['evidence_hash', 'consent_hash'];

    protected $casts = [
        'verified_at' => 'datetime',
        'student_consent_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ESBTPParent::class, 'parent_id');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }
}
