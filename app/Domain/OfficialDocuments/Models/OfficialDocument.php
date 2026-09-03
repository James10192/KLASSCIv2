<?php

namespace App\Domain\OfficialDocuments\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class OfficialDocument extends Model
{
    public const TYPE_LMD_JURY_PV = 'lmd_jury_pv';

    /**
     * Releve de notes LMD.
     *
     * La colonne `document_type` est une chaine libre (string 64), donc l'ajout
     * d'un type n'appelle aucune migration.
     *
     * Portee d'une serie : un etudiant, une annee universitaire. Le releve couvre
     * tous les semestres de l'annee, conformement a la directive 03/2007/CM/UEMOA
     * qui fait de la transferabilite des credits un principe fondateur.
     */
    public const TYPE_LMD_TRANSCRIPT = 'lmd_releve_notes';

    public const STATUS_VALID = 'valid';
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_SUPERSEDED = 'superseded';
    public const STATUS_LEGACY = 'legacy';

    protected $table = 'esbtp_official_documents';

    protected $guarded = [];

    protected $casts = [
        'snapshot' => 'array',
        'lifecycle_metadata' => 'array',
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $document): void {
            $immutable = ['document_type', 'source_type', 'source_id', 'series_key', 'version', 'reference', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'checksum_sha256', 'snapshot', 'snapshot_sha256', 'rules_version', 'template_version', 'renderer_version', 'verification_code_digest', 'issued_by', 'issued_at', 'supersedes_document_id'];
            if ($document->isDirty($immutable)) {
                throw new LogicException('Le contenu d un document officiel est immuable.');
            }

            if ($document->isDirty('status') && ! in_array($document->status, [self::STATUS_REVOKED, self::STATUS_SUPERSEDED], true)) {
                throw new LogicException('Transition de cycle de vie invalide.');
            }
        });

        static::deleting(fn (): never => throw new LogicException('Un document officiel ne peut pas être supprimé.'));
    }

    public function events(): HasMany
    {
        return $this->hasMany(OfficialDocumentEvent::class, 'official_document_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_document_id');
    }

    public function isAccessible(bool $allowLegacy = false): bool
    {
        return $this->status === self::STATUS_VALID
            || ($allowLegacy && $this->status === self::STATUS_LEGACY);
    }

    public function scopeForSource(Builder $query, string $type, int $sourceId): Builder
    {
        return $query->where('source_type', $type)->where('source_id', $sourceId);
    }
}
