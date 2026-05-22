<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPLMDJury extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_lmd_jurys';

    protected $fillable = [
        'annee_universitaire_id', 'session_id', 'parcours_id', 'classe_id', 'semestre',
        'libelle', 'date_jury',
        'pv_numero', 'pv_path', 'pv_genere_at', 'pv_genere_par',
        'status', 'clos_at', 'publie_at', 'publie_par',
        'observations',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'date_jury' => 'date',
        'pv_genere_at' => 'datetime',
        'clos_at' => 'datetime',
        'publie_at' => 'datetime',
        'semestre' => 'integer',
    ];

    protected $auditInclude = [
        'libelle',
        'date_jury',
        'status',
        'pv_numero',
        'pv_path',
        'pv_genere_at',
        'pv_genere_par',
        'publie_at',
        'publie_par',
        'clos_at',
    ];

    public const STATUSES = ['preparation', 'en_cours', 'clos', 'publie', 'archive'];

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ESBTPLMDSession::class, 'session_id');
    }

    public function parcours(): BelongsTo
    {
        return $this->belongsTo(ESBTPLMDParcours::class, 'parcours_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    public function membres(): HasMany
    {
        return $this->hasMany(ESBTPLMDJuryMembre::class, 'jury_id');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(ESBTPLMDJuryDecision::class, 'jury_id');
    }

    public function scopeNotLocked(Builder $query): Builder
    {
        return $query->whereNull('pv_genere_at');
    }

    public function isLocked(): bool
    {
        return $this->pv_genere_at !== null;
    }

    public function isPublished(): bool
    {
        return $this->status === 'publie';
    }
}
