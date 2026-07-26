<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ESBTPLMDCreditWalletEntry extends Model
{
    protected $table = 'esbtp_lmd_credit_wallet_entries';

    protected $guarded = [];

    protected $casts = [
        'credit_delta' => 'integer',
        'credits_expected_delta' => 'integer',
        'annee_universitaire_id' => 'integer',
        'classe_id' => 'integer',
        'parcours_id' => 'integer',
        'semestre' => 'integer',
        'moyenne_generale' => 'decimal:2',
        'source_published_at' => 'datetime',
        'source_snapshot' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Une ligne de wallet credit LMD est immuable.'));
        static::deleting(fn (): never => throw new LogicException('Une ligne de wallet credit LMD ne peut pas etre supprimee.'));
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(ESBTPLMDBulletin::class, 'bulletin_id');
    }
}