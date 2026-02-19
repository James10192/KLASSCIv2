<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPMatiere extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_matieres';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'nom',
        'description',
        'coefficient',
        'heures_cm',
        'heures_td',
        'heures_tp',
        'heures_stage',
        'heures_perso',
        'niveau_etude_id',
        'filiere_id',
        'type_formation',
        'couleur',
        'is_active',
        'created_by',
        'updated_by',
        'coefficient_default',
        'total_heures_default'
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'coefficient' => 'float',
        'heures_cm' => 'integer',
        'heures_td' => 'integer',
        'heures_tp' => 'integer',
        'heures_stage' => 'integer',
        'heures_perso' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relation avec le niveau d'étude.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function niveauEtude()
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_etude_id');
    }

    /**
     * Relation avec la filière.
     *
     * @deprecated Use filieres() instead for a many-to-many relationship
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    /**
     * Relation avec les filières.
     * Une matière peut être associée à plusieurs filières (relation many-to-many).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function filieres()
    {
        return $this->belongsToMany(ESBTPFiliere::class, 'esbtp_matiere_filiere', 'matiere_id', 'filiere_id')
                    ->withPivot('is_active')
                    ->withTimestamps();
    }

    /**
     * Relation avec les évaluations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function evaluations()
    {
        return $this->hasMany(ESBTPEvaluation::class, 'matiere_id');
    }

    /**
     * Relation avec les classes qui utilisent cette matière.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function classes()
    {
        return $this->belongsToMany(ESBTPClasse::class, 'esbtp_classe_matiere', 'matiere_id', 'classe_id')
                    ->withPivot('coefficient', 'total_heures', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Relation avec les enseignants qui enseignent cette matière.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function enseignants()
    {
        return $this->belongsToMany(User::class, 'esbtp_enseignant_matiere', 'matiere_id', 'enseignant_id')
                    ->withPivot('annee_universitaire_id', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Obtenir le coefficient pour une classe spécifique.
     *
     * @param int $classeId
     * @return float
     */
    public function getCoefficientForClasse($classeId)
    {
        $pivot = $this->classes()->where('esbtp_classe.id', $classeId)->first()->pivot ?? null;
        return $pivot ? $pivot->coefficient : $this->coefficient;
    }

    /**
     * Obtenir le total d'heures pour une classe spécifique.
     *
     * @param int $classeId
     * @return int
     */
    public function getTotalHeuresForClasse($classeId)
    {
        $pivot = $this->classes()->where('esbtp_classe.id', $classeId)->first()->pivot ?? null;
        return $pivot ? $pivot->total_heures : $this->heures_cm + $this->heures_td + $this->heures_tp + $this->heures_stage + $this->heures_perso;
    }

    /**
     * Utilisateur qui a créé l'entrée.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Utilisateur qui a mis à jour l'entrée.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Récupère l'unité d'enseignement associée à la matière.
     * TEMPORAIREMENT COMMENTÉ - Table esbtp_unites_enseignement n'existe pas
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    /*
    public function uniteEnseignement()
    {
        return $this->belongsTo(ESBTPUniteEnseignement::class, 'unite_enseignement_id');
    }
    */

    /**
     * Relation avec les niveaux d'études associés à cette matière.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function niveaux()
    {
        return $this->belongsToMany(ESBTPNiveauEtude::class, 'esbtp_matiere_niveau', 'matiere_id', 'niveau_etude_id')
                    ->withPivot('coefficient', 'heures_cours', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Alias de la relation niveaux pour compatibilité.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function niveauxEtudes()
    {
        return $this->niveaux();
    }

    /**
     * Liaisons précises filière+niveau (une ligne par combinaison).
     * Utilisé par la nouvelle modal de configuration des liaisons.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function liaisonsFilieresNiveaux()
    {
        return $this->hasMany(\App\Models\ESBTPMatiereFilierNiveau::class, 'matiere_id');
    }

    /**
     * Relation avec les séances de cours.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function seancesCours()
    {
        return $this->hasMany(ESBTPCours::class, 'matiere_id');
    }

    /**
     * Accesseur pour obtenir le coefficient par défaut.
     *
     * @return float
     */
    public function getCoefficientDefaultAttribute()
    {
        return $this->coefficient ?? $this->niveaux()->pluck('pivot.coefficient')->avg() ?? 1.0;
    }

    /**
     * Accesseur pour obtenir le total d'heures par défaut.
     *
     * @return int
     */
    public function getTotalHeuresDefaultAttribute()
    {
        return $this->heures_cm + $this->heures_td + $this->heures_tp + $this->heures_stage + $this->heures_perso ??
               $this->niveaux()->pluck('pivot.heures_cours')->sum() ?? 0;
    }
}
