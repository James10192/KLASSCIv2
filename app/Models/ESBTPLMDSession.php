<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPLMDSession extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_lmd_sessions';

    protected $fillable = [
        'annee_universitaire_id',
        'parcours_id',
        'type',
        'parent_session_id',
        'semestre',
        'libelle',
        'date_debut',
        'date_fin',
        'status',
        'published_at',
        'published_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'published_at' => 'datetime',
        'semestre' => 'integer',
    ];

    protected $auditInclude = [
        'libelle',
        'type',
        'date_debut',
        'date_fin',
        'status',
        'published_at',
        'published_by',
    ];

    public const TYPES = ['normale', 'rattrapage', 'extra'];

    public const STATUSES = [
        'draft', 'planned', 'in_progress', 'completed', 'published', 'archived',
    ];

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function parcours(): BelongsTo
    {
        return $this->belongsTo(ESBTPLMDParcours::class, 'parcours_id');
    }

    public function parentSession(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_session_id');
    }

    public function childrenSessions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_session_id');
    }

    public function examens(): HasMany
    {
        return $this->hasMany(ESBTPExamenPlanifie::class, 'session_id');
    }

    public function scopeForAnnee(Builder $query, int $anneeId): Builder
    {
        return $query->where('annee_universitaire_id', $anneeId);
    }

    public function scopeNormales(Builder $query): Builder
    {
        return $query->where('type', 'normale');
    }

    public function scopeRattrapages(Builder $query): Builder
    {
        return $query->where('type', 'rattrapage');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
