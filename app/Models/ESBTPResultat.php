<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPResultat extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    /**
     * Colonnes auditées (whitelist — résultats académiques officiels).
     *
     * Note : ce modèle stocke un résultat par matière (la décision
     * finale annuelle est calculée à partir des résultats matière par
     * matière). Le champ `appreciation` couvre la mention.
     *
     * @var array
     */
    protected $auditInclude = [
        'etudiant_id',
        'classe_id',
        'matiere_id',
        'periode',
        'annee_universitaire_id',
        'moyenne',
        'coefficient',
        'rang',
        'appreciation',
        'enseignant_id',
        'type',
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
    }

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_resultats';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'etudiant_id',
        'classe_id',
        'matiere_id',
        'periode',
        'annee_universitaire_id',
        'moyenne',
        'coefficient',
        'rang',
        'appreciation',
        'enseignant_id',
        'type',
        'created_by',
        'updated_by'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'moyenne' => 'decimal:2',
        'coefficient' => 'float',
        'rang' => 'integer'
    ];

    /**
     * Les attributs avec des valeurs par défaut.
     *
     * @var array
     */
    protected $attributes = [
        'rang' => null,
        'appreciation' => null
    ];

    /**
     * Relation avec l'étudiant associé à ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    /**
     * Relation avec la classe associée à ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classe()
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    /**
     * Relation avec la matière associée à ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    /**
     * Récupère toutes les matières associées à cette classe et période.
     * Cette méthode est utilisée pour obtenir une collection des matières liées à ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function matieres()
    {
        // Récupérer toutes les matières associées aux résultats de cet étudiant, classe, période et année
        $matieresIds = ESBTPResultat::where('etudiant_id', $this->etudiant_id)
            ->where('classe_id', $this->classe_id)
            ->where('periode', $this->periode)
            ->where('annee_universitaire_id', $this->annee_universitaire_id)
            ->pluck('matiere_id')
            ->unique()
            ->toArray();

        // Récupérer les matières correspondantes
        if (!empty($matieresIds)) {
            return ESBTPMatiere::whereIn('id', $matieresIds)->get();
        }

        return collect();
    }

    /**
     * Relation avec l'année universitaire associée à ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    /**
     * Relation avec le créateur de ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec le dernier utilisateur ayant mis à jour ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relation avec l'enseignant assigné à ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function enseignant()
    {
        return $this->belongsTo(\App\Models\User::class, 'enseignant_id');
    }

    /**
     * Obtenir la moyenne pondérée (moyenne * coefficient).
     *
     * @return float
     */
    public function getMoyennePondereeAttribute()
    {
        return round($this->moyenne * $this->coefficient, 2);
    }

    /**
     * Obtenir la mention associée à la moyenne.
     *
     * @return string
     */
    public function getMentionAttribute()
    {
        $moyenne = $this->moyenne;

        if ($moyenne >= 16) {
            return 'Très Bien';
        } elseif ($moyenne >= 14) {
            return 'Bien';
        } elseif ($moyenne >= 12) {
            return 'Assez Bien';
        } elseif ($moyenne >= 10) {
            return 'Passable';
        } else {
            return 'Insuffisant';
        }
    }

    /**
     * Déterminer l'appréciation associée à la moyenne.
     *
     * @return string
     */
    public function determinerAppreciation()
    {
        $moyenne = $this->moyenne;

        if ($moyenne >= 16) {
            return 'Excellent';
        } elseif ($moyenne >= 14) {
            return 'Très Bien';
        } elseif ($moyenne >= 12) {
            return 'Bien';
        } elseif ($moyenne >= 10) {
            return 'Passable';
        } else {
            return 'Insuffisant';
        }
    }
}
