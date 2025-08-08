<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPEvaluation extends Model
{
    use HasFactory, SoftDeletes;

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
        'is_published',
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
        'date_evaluation' => 'date',
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
        return $this->status === self::STATUS_COMPLETED && !$this->notes_published;
    }

    public function isDeletable()
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_CANCELLED]);
    }
}
