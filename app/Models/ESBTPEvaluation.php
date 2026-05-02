<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPEvaluation extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    /**
     * Colonnes auditées (whitelist — éviter explosion volume).
     *
     * @var array
     */
    protected $auditInclude = [
        'titre',
        'matiere_id',
        'classe_id',
        'type',
        'date_evaluation',
        'coefficient',
        'bareme',
        'duree_minutes',
        'periode',
        'annee_universitaire_id',
        'status',
        'is_published',
        'notes_published',
        'enseignant_id',
        'enseignant_externe_nom',
    ];

    /**
     * Événements à auditer.
     *
     * @var array
     */
    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_evaluations';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'titre',
        'description',
        'matiere_id',
        'classe_id',
        'type',
        'date_evaluation',
        'coefficient',
        'bareme',
        'duree_minutes',
        'periode',
        'annee_universitaire_id',
        'status',
        'is_published',
        'notes_published',
        'created_by',
        'updated_by',
        'enseignant_id',
        'enseignant_externe_nom',
        'token_saisie_externe',
        'token_expire_at'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'date_evaluation' => 'datetime',
        'token_expire_at' => 'datetime'
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const TYPE_DEVOIR = 'devoir';
    const TYPE_EXAMEN = 'examen';
    const TYPE_RATTRAPAGE = 'rattrapage';

    /**
     * Relation avec la matière associée à cette évaluation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    /**
     * Relation avec la classe associée à cette évaluation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classe()
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    /**
     * Relation avec l'année universitaire associée à cette évaluation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    /**
     * Relation avec les notes des étudiants pour cette évaluation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes()
    {
        return $this->hasMany(ESBTPNote::class, 'evaluation_id');
    }

    /**
     * Relation avec l'utilisateur qui a créé l'évaluation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour l'évaluation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relation avec l'enseignant assigné à l'évaluation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function enseignant()
    {
        return $this->belongsTo(User::class, 'enseignant_id');
    }

    /**
     * Scope pour filtrer les évaluations pour un étudiant donné.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $studentId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->whereHas('classe.etudiants', function ($q) use ($studentId) {
            $q->where('esbtp_etudiants.id', $studentId);
        });
    }

    /**
     * Types d'évaluation disponibles
     *
     * @return array
     */
    public static function getTypes()
    {
        return [
            'examen' => 'Examen',
            'devoir' => 'Devoir',
            'tp' => 'Travaux Pratiques',
            'projet' => 'Projet',
            'oral' => 'Évaluation Orale'
        ];
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeUnpublished($query)
    {
        return $query->where('is_published', false);
    }

    public function scopeNotesPublished($query)
    {
        return $query->where('notes_published', true);
    }

    public function scopeNotesUnpublished($query)
    {
        return $query->where('notes_published', false);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date_evaluation', '>', now())
                    ->where('status', '!=', self::STATUS_CANCELLED);
    }

    public function scopePast($query)
    {
        return $query->where('date_evaluation', '<', now());
    }

    public function isEditable()
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED]);
    }

    public function canPublishNotes()
    {
        if ($this->status !== self::STATUS_COMPLETED || $this->notes_published) {
            return false;
        }

        $notesCount = $this->notes_count ?? null;
        $hasNotes = $notesCount !== null ? $notesCount > 0 : $this->notes()->exists();

        return $hasNotes;
    }

    public function isDeletable()
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_CANCELLED]);
    }

    /**
     * Retourne le libellé utilisateur du statut courant.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Retourne la classe CSS à utiliser pour afficher un badge de statut.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'badge bg-secondary',
            self::STATUS_SCHEDULED => 'badge bg-info text-dark',
            self::STATUS_IN_PROGRESS => 'badge bg-warning text-dark',
            self::STATUS_COMPLETED => 'badge bg-success',
            self::STATUS_CANCELLED => 'badge bg-danger',
            default => 'badge bg-secondary',
        };
    }

    /**
     * Détermine le statut automatique selon la date, l'heure et l'état de publication.
     */
    public function determineAutomaticStatus(?Carbon $now = null, bool $respectCancellation = true): string
    {
        $now = $now ?: now();

        if ($respectCancellation && $this->status === self::STATUS_CANCELLED) {
            return self::STATUS_CANCELLED;
        }

        $notesCount = $this->notes_count ?? null;
        $hasNotes = $notesCount !== null ? $notesCount > 0 : $this->notes()->exists();

        if ($hasNotes && $this->date_evaluation instanceof Carbon && $this->date_evaluation->isPast()) {
            return self::STATUS_COMPLETED;
        }

        if (!$this->is_published) {
            return self::STATUS_DRAFT;
        }

        if (!$this->date_evaluation instanceof Carbon) {
            return self::STATUS_SCHEDULED;
        }

        $startAt = $this->date_evaluation->copy();
        $durationMinutes = (int) ($this->duree_minutes ?? 0);
        $endAt = $durationMinutes > 0 ? $startAt->copy()->addMinutes($durationMinutes) : $startAt->copy()->endOfDay();

        if ($startAt->isFuture()) {
            return self::STATUS_SCHEDULED;
        }

        if ($now->between($startAt, $endAt)) {
            return self::STATUS_IN_PROGRESS;
        }

        return self::STATUS_COMPLETED;
    }

    /**
     * Synchronise et persiste le statut automatique si nécessaire.
     *
     * @return bool true si le statut a été modifié
     */
    public function syncAutomaticStatus(bool $persist = true, ?Carbon $now = null, bool $respectCancellation = true): bool
    {
        $newStatus = $this->determineAutomaticStatus($now, $respectCancellation);

        if ($newStatus === $this->status) {
            return false;
        }

        $this->status = $newStatus;

        if ($persist) {
            $this->save();
        }

        return true;
    }

    /**
     * Retourne la liste des libellés de statut.
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_SCHEDULED => 'Planifiée',
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_COMPLETED => 'Terminée',
            self::STATUS_CANCELLED => 'Annulée',
        ];
    }
}
