<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPNote extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

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
        'observation',
        'type_evaluation',
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
        'observation',
        'created_by',
        'updated_by',
        'is_absent',
        'commentaire'
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

        if ($note >= 16) {
            return 'Très Bien';
        } elseif ($note >= 14) {
            return 'Bien';
        } elseif ($note >= 12) {
            return 'Assez Bien';
        } elseif ($note >= 10) {
            return 'Passable';
        } else {
            return 'Insuffisant';
        }
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
