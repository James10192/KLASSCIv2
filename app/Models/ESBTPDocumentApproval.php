<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ESBTPDocumentApproval extends Model
{
    public const TYPE_CERTIFICAT = 'certificat';
    public const TYPE_ATTESTATION = 'attestation';
    public const TYPE_BULLETIN = 'bulletin';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'esbtp_document_approvals';

    protected $fillable = [
        'document_type',
        'etudiant_id',
        'document_id',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approbateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * La demande en attente qui porte deja sur ce document, s'il y en a une.
     *
     * Deux demandes en attente pour le meme document ne veulent rien dire de
     * plus qu'une seule : elles noient la file de qui doit accorder.
     */
    public static function demandeEnAttentePour(string $documentType, int $etudiantId, ?int $documentId = null): ?self
    {
        return static::query()
            ->enAttente()
            ->where('document_type', $documentType)
            ->where('etudiant_id', $etudiantId)
            ->where(fn ($q) => $documentId === null ? $q->whereNull('document_id') : $q->where('document_id', $documentId))
            ->first();
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}