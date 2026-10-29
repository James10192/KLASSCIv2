<?php

namespace App\Domain\OfficialDocuments\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class OfficialDocumentEvent extends Model
{
    public const CREATED_AT = null;
    public const UPDATED_AT = null;

    protected $table = 'esbtp_official_document_events';

    protected $guarded = [];

    protected $casts = ['metadata' => 'array', 'occurred_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Le journal officiel est append-only.'));
        static::deleting(fn (): never => throw new LogicException('Le journal officiel est append-only.'));
    }
}
