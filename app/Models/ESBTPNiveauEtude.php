<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPNiveauEtude extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_niveau_etudes';

    /**
     * Les annees qu'un niveau LMD peut porter, par cycle.
     *
     * L'annee est comptee EN CONTINU d'un cycle a l'autre : Master 1 est l'annee
     * 4, pas l'annee 1. C'est la convention sur laquelle repose la correspondance
     * niveau → semestres (`ESBTPClasse::getSemestresLMD()`, annee 4 → S7-S8) et
     * tout ce qui la lit.
     *
     * Un Master saisi en annee 1 ne leve aucune erreur : il est range en S1-S2,
     * c'est-a-dire traite comme une Licence 1, et recoit ses unites. C'est
     * exactement ce qui est arrive a une ecole avant que cette liste existe.
     */
    public const ANNEES_PAR_CYCLE_LMD = [
        'Licence' => [1, 2, 3],
        'Master' => [4, 5],
        'Doctorat' => [6, 7, 8],
    ];

    /**
     * Vrai si l'annee est de celles de son cycle, faux sinon, et null pour un
     * niveau qui n'est pas un cycle LMD (BTS, Ingenieur...) : la question ne se
     * pose pas pour lui.
     */
    public function anneeCoherenteAvecSonCycle(): ?bool
    {
        if (! $this->estUnCycleLmd()) {
            return null;
        }

        return in_array((int) $this->year, self::ANNEES_PAR_CYCLE_LMD[$this->type], true);
    }

    public function estUnCycleLmd(): bool
    {
        return array_key_exists((string) $this->type, self::ANNEES_PAR_CYCLE_LMD);
    }

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'libelle',
        'code',
        'type',
        'year',
        'description',
        'is_active',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'year' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtenir les étudiants inscrits à ce niveau d'études.
     *
     * Un niveau d'études peut avoir plusieurs étudiants.
     * Par exemple, "Première année BTS" peut avoir plusieurs étudiants inscrits.
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'niveau_etude_id');
    }

    /**
     * Relation avec les classes associées à ce niveau d'études.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function classes()
    {
        return $this->hasMany(ESBTPClasse::class, 'niveau_etude_id');
    }

    /**
     * Accesseur pour le niveau (basé sur year).
     *
     * @return int
     */
    public function getNiveauAttribute()
    {
        return $this->year;
    }

    /**
     * Mutateur pour le niveau (stocké dans year).
     *
     * @param int $value
     * @return void
     */
    public function setNiveauAttribute($value)
    {
        $this->attributes['year'] = $value;
    }

    /**
     * Scope pour récupérer uniquement les niveaux d'études actifs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour récupérer uniquement les niveaux d'études inactifs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Obtenir le nom complet du niveau d'études.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->type . ' - ' . $this->name;
    }


    /**
     * Relation avec les matières associées à ce niveau d'études.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function matieres()
    {
        return $this->belongsToMany(ESBTPMatiere::class, 'esbtp_matiere_niveau', 'niveau_etude_id', 'matiere_id')
                    ->withPivot('coefficient', 'heures_cours', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Relation avec les inscriptions associées à ce niveau d'études.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inscriptions()
    {
        return $this->hasMany(ESBTPInscription::class, 'niveau_id');
    }

    /**
     * Relation avec les filières associées à ce niveau d'études.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function filieres()
    {
        return $this->belongsToMany(ESBTPFiliere::class, 'esbtp_filiere_niveau', 'niveau_etude_id', 'filiere_id')
                    ->withTimestamps();
    }
}
