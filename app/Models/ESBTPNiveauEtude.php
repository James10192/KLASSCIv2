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
     * Obtenir le nom complet du niveau d'études.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->type . ' - ' . $this->name;
    }

    /**
     * Relation avec les formations associées à ce niveau d'études.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function formations()
    {
        return $this->belongsToMany(ESBTPFormation::class, 'esbtp_formation_niveau', 'niveau_id', 'formation_id')
                    ->withTimestamps();
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
