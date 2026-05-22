<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'parcours_id',
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

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
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
        return $query->where('classe_id', $classeId);
    }

    public function scopeForAnnee(Builder $query, int $anneeId): Builder
    {
        return $query->where('annee_universitaire_id', $anneeId);
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
}
