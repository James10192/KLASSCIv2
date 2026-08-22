<?php

namespace App\Models;

use Illuminate\Validation\ValidationException;
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
/**
     * Coherence entre le systeme academique de la classe et la nature de la
     * matiere.
     *
     * La table esbtp_evaluations est partagee par le BTS et le LMD. Une
     * evaluation est coherente quand une classe BTS porte une matiere sans
     * unite d'enseignement, et une classe LMD une ECUE. L'inverse trahit un
     * selecteur qui a propose la mauvaise liste, et les notes saisies
     * atterrissent alors sur une matiere etrangere au cursus.
     *
     * Le controle se declenche a la creation, et a la modification seulement
     * si la classe ou la matiere change : une evaluation historiquement
     * incoherente reste modifiable sur son titre ou sa date, sinon on ne
     * pourrait meme plus la corriger.
     */
    protected static function booted(): void
    {
        static::saving(function (self $evaluation): void {
            $doitControler = ! $evaluation->exists
                || $evaluation->isDirty(['matiere_id', 'classe_id']);

            if (! $doitControler || ! $evaluation->matiere_id || ! $evaluation->classe_id) {
                return;
            }

            $classe = ESBTPClasse::find($evaluation->classe_id);
            $matiere = ESBTPMatiere::find($evaluation->matiere_id);

            if (! $classe || ! $matiere) {
                return;
            }

            $classeEstLmd = ($classe->systeme_academique ?? '') === 'LMD';
            $matiereEstEcue = $matiere->unite_enseignement_id !== null;

            if ($classeEstLmd === $matiereEstEcue) {
                return;
            }

            throw ValidationException::withMessages([
                'matiere_id' => $classeEstLmd
                    ? "La classe « {$classe->name} » est en LMD : elle attend une ECUE, or « {$matiere->name} » est une matière BTS."
                    : "La classe « {$classe->name} » est en BTS : elle attend une matière BTS, or « {$matiere->name} » est une ECUE du LMD.",
            ]);
        });

        static::saving(function (self $evaluation): void {
            $evaluation->assertPeriodeCoherenteAvecLaClasse();
        });
    }

    /**
     * Une classe de spécialité issue du tronc commun ne s'ouvre qu'au semestre
     * porté par `semestre_activation`. Y créer une évaluation sur un semestre
     * antérieur produit une note que l'étudiant n'aurait pas dû avoir, et qui
     * remonte ensuite sur son bulletin de tronc commun.
     *
     * Cas constaté à l'ESBTP Yamoussoukro : une évaluation de « Sécurité »
     * créée en semestre 1 sur une classe de spécialité s'affichait sur les
     * bulletins de tronc commun des étudiants concernés.
     *
     * Le contrôle tient en une requête indexée : il ne charge pas les
     * inscriptions, contrairement au compteur de cohortes.
     */
    protected function assertPeriodeCoherenteAvecLaClasse(): void
    {
        $doitControler = ! $this->exists || $this->isDirty(['periode', 'classe_id']);

        if (! $doitControler || ! $this->classe_id || ! $this->periode) {
            return;
        }

        $semestre = self::numeroDeSemestre((string) $this->periode);
        if ($semestre === null) {
            return;
        }

        $activation = \Illuminate\Support\Facades\DB::table('esbtp_classe_orientation_targets')
            ->where('target_classe_id', $this->classe_id)
            ->where('is_active', true)
            ->min('semestre_activation');

        if ($activation === null || $semestre >= (int) $activation) {
            return;
        }

        $classe = ESBTPClasse::find($this->classe_id);
        $nomClasse = $classe->name ?? "#{$this->classe_id}";

        throw ValidationException::withMessages([
            'periode' => "La classe « {$nomClasse} » est une classe de spécialité : "
                ."les étudiants n'y arrivent qu'au semestre {$activation}, après leur "
                .'orientation depuis le tronc commun. Une évaluation de semestre '
                ."{$semestre} y créerait une note qui remonterait à tort sur leur "
                .'bulletin de tronc commun.',
        ]);
    }

    /**
     * Le champ periode a connu des valeurs héritées ('1', '2') avant les
     * libellés actuels. Les deux coexistent en base.
     */
    public static function numeroDeSemestre(string $periode): ?int
    {
        return match ($periode) {
            'semestre1', '1' => 1,
            'semestre2', '2' => 2,
            default => null,
        };
    }
}
