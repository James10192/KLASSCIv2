<?php

namespace App\Models;

use App\Services\AppreciationScaleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPNote extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    public const SUBMISSION_DRAFT = 'draft';
    public const SUBMISSION_SUBMITTED = 'submitted';

    /**
     * Colonnes auditées (whitelist — éviter explosion volume).
     *
     * @var array
     */
    protected $auditInclude = [
        'evaluation_id',
        'etudiant_id',
        'matiere_id',
        'classe_id',
        'semestre',
        'note',
        'valeur',
        'is_absent',
        'commentaire',
        'type_evaluation',
        'submission_status',
        'submitted_at',
        'submitted_by',
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

    protected static function booted(): void
    {
        static::addGlobalScope('not_archived', function (Builder $builder) {
            $builder->whereNull($builder->getModel()->getTable() . '.archived_at');
        });

        static::saving(function ($note) {
            if ($note->evaluation) {
                $note->semestre = (int) str_replace('semestre', '', $note->evaluation->periode);
            }
        });
    }

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_notes';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'evaluation_id',
        'etudiant_id',
        'matiere_id',
        'classe_id',
        'semestre',
        'annee_universitaire',
        'note',
        'type_evaluation',
        'valeur',
        'created_by',
        'updated_by',
        'is_absent',
        'commentaire',
        'submission_status',
        'submitted_at',
        'submitted_by',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'valeur' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    /**
     * Relation avec l'évaluation associée à cette note.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function evaluation()
    {
        return $this->belongsTo(ESBTPEvaluation::class, 'evaluation_id');
    }

    /**
     * Relation avec l'étudiant associé à cette note.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    /**
     * Relation avec l'utilisateur qui a créé la note.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour la note.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function isSubmitted(): bool
    {
        return ($this->submission_status ?? self::SUBMISSION_SUBMITTED) === self::SUBMISSION_SUBMITTED;
    }

    /**
     * Relation avec la matière associée à cette note.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    /**
     * Relation avec la classe associée à cette note.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classe()
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    /**
     * Obtenir la note sur 20.
     *
     * @return float|null
     */
    public function getNoteVingtAttribute()
    {
        if ($this->is_absent) {
            return 0;
        }

        $rawNote = $this->note ?? $this->valeur;

        if ($this->evaluation && $this->evaluation->bareme > 0) {
            return round(($rawNote / $this->evaluation->bareme) * 20, 2);
        }

        return $rawNote;
    }

    /**
     * Obtenir la note pondérée (note sur 20 * coefficient de l'évaluation).
     *
     * @return float|null
     */
    public function getNotePondereeAttribute()
    {
        if ($this->evaluation) {
            return round($this->note_vingt * $this->evaluation->coefficient, 2);
        }

        return $this->valeur;
    }

    /**
     * Obtenir la mention associée à la note.
     *
     * @return string
     */
    public function getMentionAttribute()
    {
        $note = $this->note_vingt;

        if ($this->is_absent) {
            return 'Absent';
        }

        return app(AppreciationScaleService::class)->labelFor($note === null ? null : (float) $note, 'bts');
    }

    /**
     * Scope to only include notes with valid evaluations
     */
    public function scopeWithValidEvaluation($query)
    {
        return $query->whereHas('evaluation');
    }

    /**
     * Check if this note has a valid evaluation
     */
    public function hasValidEvaluation()
    {
        return $this->evaluation()->exists();
    }

    /**
     * Get the formatted note with barème
     */
    public function getFormattedNoteAttribute()
    {
        if ($this->is_absent) {
            return 'Absent';
        }

        if (!$this->hasValidEvaluation()) {
            return "{$this->note}/N/A";
        }

        return "{$this->note}/{$this->evaluation->bareme}";
    }

    /**
     * Scope pour filtrer les notes par période (semestre)
     */
    public function scopeByPeriode($query, $periode)
    {
        if ($periode === 'annuel') {
            return $query;
        }

        return $query->where(function($q) use ($periode) {
            $q->where('semestre', $periode)
                ->orWhereHas('evaluation', function($eval) use ($periode) {
                    $eval->where('periode', $periode);
                });
        });
    }

    /**
     * Scope pour filtrer les notes par année universitaire
     */
    public function scopeByAnneeUniversitaire($query, $anneeId)
    {
        return $query->whereHas('evaluation', function($q) use ($anneeId) {
            $q->where('annee_universitaire_id', $anneeId);
        });
    }

    /**
     * Scope pour filtrer les notes par année universitaire, incluant aussi l'année précédente
     * Utile pour gérer la transition entre les années académiques
     */
    public function scopeByAnneeUniversitaireWithPrevious($query, $anneeId)
    {
        return $query->whereHas('evaluation', function($q) use ($anneeId) {
            $q->where('annee_universitaire_id', $anneeId)
              ->orWhere('annee_universitaire_id', $anneeId - 1);
        });
    }

    /**
     * Scope pour filtrer les notes par classe
     * Filtre les notes en fonction de la classe associée à leur évaluation
     */
    public function scopeByClasse($query, $classeId)
    {
        return $query->whereHas('evaluation', function($q) use ($classeId) {
            $q->where('classe_id', $classeId);
        });
    }

    /**
     * Les notes rattachees a une classe — LES DEUX rattachements, reunis.
     *
     * `esbtp_notes.classe_id` est une colonne denormalisee, recopiee depuis
     * l'evaluation au moment ou la note est enregistree. Le crochet qui la
     * synchronise ne se declenche qu'a l'enregistrement de LA NOTE : changer
     * la classe d'une evaluation laisse derriere elle des notes qui portent
     * encore l'ancienne. C'est le piege documente dans
     * `.claude/rules/klassci-debugging-discipline.md` (#7), et la commande
     * `evaluations:sync-notes` existe justement pour le rattraper apres coup.
     *
     * L'archivage s'appuyait sur la seule colonne denormalisee. Une note dont
     * elle avait derive restait donc VIVANTE quand l'eleve quittait la classe,
     * et continuait a peser sur ses moyennes ailleurs. La reunion des deux
     * rattachements ne peut qu'en prendre davantage, jamais moins — c'est le
     * bon sens d'erreur pour un archivage.
     *
     * ⚠️ Le retrait et la restauration doivent employer le MEME predicat.
     * Archiver large et restaurer etroit perdrait definitivement des notes
     * au retour d'un eleve dans sa classe.
     *
     * Volontairement SANS filtre d'annee : `esbtp_notes` ne porte l'annee que
     * sous forme de libelle (« 2025-2026 », parfois « N/A »), et s'en servir
     * ferait sauter en silence les notes mal renseignees. Une classe KLASSCI
     * n'est de toute facon pas liee a une annee (voir la rule
     * `classes-universelles-pas-annee.md`).
     *
     * `esbtp_resultats` et `esbtp_bulletins` gardent, eux, le seul
     * `classe_id`, et c'est voulu : leur colonne est ecrite a la generation
     * avec la classe du bulletin, elle ne derive pas d'une evaluation dont on
     * changerait la classe apres coup.
     */
    public function scopeRattacheesALaClasse($query, $classeId)
    {
        return $query->where(function ($scope) use ($classeId) {
            $scope->where('classe_id', $classeId)
                ->orWhereHas('evaluation', function ($evaluation) use ($classeId) {
                    // Colonne qualifiee : `classe_id` existe des DEUX cotes de
                    // la jointure, et laisser MySQL trancher rend la requete
                    // juste mais illisible.
                    $evaluation->where('esbtp_evaluations.classe_id', $classeId);
                });
        });
    }

    /**
     * Realigne `esbtp_notes.semestre` sur la periode d'une evaluation, pour
     * toutes ses notes, et rend le nombre de lignes touchees.
     *
     * **L'encodage de cette colonne vit ici, avec le hook qui le decide.** Elle
     * est un `varchar`, mais tout le depot y ecrit l'ENTIER : `booted()::saving`
     * fait `(int) str_replace('semestre', '', …)` et a le dernier mot sur chaque
     * chemin Eloquent — y compris `synchronizerPeriode()` juste en dessous, qui
     * pose pourtant la chaine avant d'appeler `save()`.
     *
     * Seul un `update()` de query builder contourne le hook. Les deux endpoints
     * de deplacement de periode en faisaient un, et y ecrivaient `'semestre1'`.
     * Ce n'etait pas une nuance de format : en MySQL `'semestre1' = 1` vaut
     * **0**, la chaine etant castee en zero. La categorie 2 d'
     * `evaluations:sync-notes --clean-resultats` joint sur
     * `esbtp_notes.semestre = 1`, ne retrouvait donc pas la note, et
     * **supprimait** l'agregat — la commande meme qu'on recommande pour le
     * menage des agregats orphelins.
     */
    public static function realignerLeSemestre(int $evaluationId, string $periodeCible): int
    {
        return static::where('evaluation_id', $evaluationId)
            ->update(['semestre' => (int) str_replace('semestre', '', $periodeCible)]);
    }

    /**
     * Synchroniser le semestre de la note avec la période de l'évaluation
     *
     * @return bool
     */
    public function synchronizerPeriode()
    {
        if (!$this->evaluation) {
            return false;
        }

        if ($this->semestre !== $this->evaluation->periode) {
            $this->semestre = $this->evaluation->periode;
            return $this->save();
        }

        return true;
    }

    /**
     * Accesseur pour l'attribut valeur (alias de note).
     *
     * @return mixed
     */
    public function getValeurAttribute()
    {
        return $this->note;
    }

    /**
     * Mutateur pour l'attribut valeur (alias de note).
     *
     * @param mixed $value
     * @return void
     */
    public function setValeurAttribute($value)
    {
        $this->attributes['note'] = $value;
    }

    /**
     * Synchroniser toutes les notes avec les périodes de leurs évaluations
     *
     * @return array
     */
    public static function synchronizerToutesPeriodes()
    {
        $notes = self::with('evaluation')->get();
        $total = $notes->count();
        $updated = 0;
        $missingEval = 0;

        foreach ($notes as $note) {
            if (!$note->evaluation) {
                $missingEval++;
                continue;
            }

            if ($note->semestre !== $note->evaluation->periode) {
                $note->semestre = $note->evaluation->periode;
                if ($note->save()) {
                    $updated++;
                }
            }
        }

        return [
            'total' => $total,
            'updated' => $updated,
            'missing_evaluations' => $missingEval
        ];
    }

}
