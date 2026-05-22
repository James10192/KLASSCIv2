<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPExamenPlanifie extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_examens_planifies';

    protected $fillable = [
        'annee_universitaire_id',
        'classe_id',
        'matiere_id',
        'unite_enseignement_id',
        'parcours_id',
        'parcours_ids',
        'scope_type',
        'scope_id',
        'semestre',
        'session_id',
        'type_examen',
        'titre',
        'description',
        'numero_convocation',
        'date_debut',
        'date_fin',
        'duree_minutes',
        'salle',
        'coefficient',
        'bareme',
        'is_anonymous',
        'status',
        'notes_locked',
        'notes_locked_at',
        'notes_locked_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'notes_locked_at' => 'datetime',
        'duree_minutes' => 'integer',
        'semestre' => 'integer',
        'coefficient' => 'decimal:2',
        'bareme' => 'decimal:2',
        'is_anonymous' => 'boolean',
        'notes_locked' => 'boolean',
        'parcours_ids' => 'array',
    ];

    protected $auditInclude = [
        'titre',
        'type_examen',
        'date_debut',
        'date_fin',
        'salle',
        'coefficient',
        'bareme',
        'numero_convocation',
        'is_anonymous',
        'status',
        'notes_locked',
        'notes_locked_at',
        'notes_locked_by',
        'scope_type',
        'scope_id',
        'classe_id',
        'matiere_id',
        'unite_enseignement_id',
        'parcours_ids',
    ];

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];

    public const TYPES = ['EXAMEN', 'PARTIEL', 'RATTRAPAGE', 'SOUTENANCE'];

    public const STATUSES = [
        'draft',
        'planned',
        'in_progress',
        'completed',
        'notes_locked',
        'cancelled',
    ];

    public const ROLES_SURVEILLANT = [
        'surveillant',
        'surveillant_principal',
        'secretaire',
        'responsable_salle',
    ];

    public const SCOPE_TYPES = ['classe', 'parcours', 'mention', 'domaine'];

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    /**
     * Classe principale (legacy BTS + premier-classe LMD).
     * Pour le multi-classe LMD, utiliser `$examen->classes` (pivot).
     */
    public function classe(): BelongsTo
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    /**
     * Toutes les classes concernées par cet examen (pivot esbtp_examen_classes).
     * Sur BTS legacy, contient juste la classe principale (backfillée).
     * Sur LMD : contient toutes les classes du scope (parcours/mention/domaine).
     * Filtre les pivots soft-deleted ET les classes exclues manuellement.
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(
            ESBTPClasse::class,
            'esbtp_examen_classes',
            'examen_id',
            'classe_id'
        )
            ->withTimestamps()
            ->withPivot(['excluded', 'deleted_at'])
            ->wherePivot('excluded', false)
            ->wherePivotNull('deleted_at');
    }

    /**
     * Toutes les pivots y compris exclues (pour audit / réactivation).
     */
    public function classesAvecExclues(): BelongsToMany
    {
        return $this->belongsToMany(
            ESBTPClasse::class,
            'esbtp_examen_classes',
            'examen_id',
            'classe_id'
        )
            ->withTimestamps()
            ->withPivot(['excluded', 'deleted_at'])
            ->wherePivotNull('deleted_at');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    /**
     * UE (Unité d'Enseignement) auquel rattacher l'ECUE (LMD UEMOA).
     * Dérivable depuis matiere.unite_enseignement_id, dénormalisée pour filtrage rapide.
     */
    public function uniteEnseignement(): BelongsTo
    {
        return $this->belongsTo(ESBTPUniteEnseignement::class, 'unite_enseignement_id');
    }

    public function parcours(): BelongsTo
    {
        return $this->belongsTo(ESBTPLMDParcours::class, 'parcours_id');
    }

    public function surveillants(): HasMany
    {
        return $this->hasMany(ESBTPExamenSurveillant::class, 'examen_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notesLockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notes_locked_by');
    }

    public function scopeForClasse(Builder $query, int $classeId): Builder
    {
        // Filtrage via la pivot pour couvrir multi-classe + classe principale
        return $query->whereHas('classes', fn ($q) => $q->where('esbtp_classes.id', $classeId));
    }

    public function scopeForAnnee(Builder $query, int $anneeId): Builder
    {
        return $query->where('annee_universitaire_id', $anneeId);
    }

    public function scopeForUe(Builder $query, int $ueId): Builder
    {
        return $query->where('unite_enseignement_id', $ueId);
    }

    public function scopeForScope(Builder $query, string $scopeType, ?int $scopeId): Builder
    {
        $query->where('scope_type', $scopeType);
        if ($scopeId !== null) {
            $query->where('scope_id', $scopeId);
        }
        return $query;
    }

    public function scopeBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('date_debut', [$start, $end])
                ->orWhereBetween('date_fin', [$start, $end])
                ->orWhere(function ($inner) use ($start, $end) {
                    $inner->where('date_debut', '<=', $start)
                        ->where('date_fin', '>=', $end);
                });
        });
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('date_debut', '>=', now())
            ->whereNotIn('status', ['cancelled', 'completed']);
    }

    public function scopeNotesLockable(Builder $query): Builder
    {
        return $query->where('notes_locked', false)
            ->where('status', 'completed');
    }

    public function isLocked(): bool
    {
        return (bool) $this->notes_locked;
    }

    public function isFinished(): bool
    {
        return $this->date_fin?->isPast() ?? false;
    }

    /**
     * Système académique dérivé de l'examen — BTS ou LMD.
     *
     * Heuristique (ordre de priorité) :
     *   1. `unite_enseignement_id` non null → LMD certain (ECUE UEMOA dénormalisé)
     *   2. Pivot legacy `matiere.uniteEnseignements()` non vide → LMD (cas pré-migration)
     *   3. Classe principale `systeme_academique = 'LMD'` (case-insensitive) → LMD
     *   4. Sinon → BTS
     *
     * Coût : 0 query supplémentaire grâce eager-load standard (`classe`, `matiere`).
     */
    public function getSystemeAttribute(): string
    {
        if ($this->unite_enseignement_id !== null) {
            return 'LMD';
        }
        // Fallback secondaire : matières LMD pré-migration via pivot `esbtp_ue_matiere`
        if ($this->relationLoaded('matiere') && $this->matiere
            && method_exists($this->matiere, 'uniteEnseignements')
            && $this->matiere->uniteEnseignements()->exists()
        ) {
            return 'LMD';
        }
        $systeme = strtoupper((string) ($this->classe?->systeme_academique ?? ''));
        return $systeme === 'LMD' ? 'LMD' : 'BTS';
    }

    /**
     * Indique si TOUTES les classes du pivot ont le même système académique.
     * False si multi-classes mixte BTS+LMD (configuration invalide UEMOA).
     */
    public function hasConsistentSysteme(): bool
    {
        $classes = $this->relationLoaded('classes') ? $this->classes : $this->classes()->get();
        $systemes = $classes->pluck('systeme_academique')
            ->filter()
            ->map(fn ($s) => strtoupper((string) $s))
            ->unique();
        return $systemes->count() <= 1;
    }
}
