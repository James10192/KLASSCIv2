<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UniteEnseignement extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'unite_enseignements';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'credits', // Nombre de crédits ECTS
        'hours', // Nombre d'heures total
        'cm_hours', // Heures de cours magistraux
        'td_hours', // Heures de travaux dirigés
        'tp_hours', // Heures de travaux pratiques
        'department_id', // Département responsable
        'responsable_id', // Enseignant responsable
        'created_by',
        'updated_by',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'credits' => 'integer',
        'hours' => 'integer',
        'cm_hours' => 'integer',
        'td_hours' => 'integer',
        'tp_hours' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtenir le département responsable de cette UE.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Obtenir l'enseignant responsable de cette UE.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Obtenir les parcours auxquels cette UE est associée.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function parcours()
    {
        return $this->belongsToMany(Parcours::class, 'parcours_unite_enseignement', 'unite_enseignement_id', 'parcours_id')
            ->withPivot('semester', 'is_optional')
            ->withTimestamps();
    }

    /**
     * Obtenir les éléments constitutifs (EC) de cette UE.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function elementConstitutifs()
    {
        return $this->hasMany(ElementConstitutif::class);
    }

    /**
     * Obtenir les enseignants qui interviennent dans cette UE.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_unite_enseignement', 'unite_enseignement_id', 'teacher_id')
            ->withPivot('role', 'hours')
            ->withTimestamps();
    }

    /**
     * Obtenir l'utilisateur qui a créé l'UE.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtenir l'utilisateur qui a mis à jour l'UE.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtenir le nombre d'EC dans cette UE.
     * 
     * @return int
     */
    public function getECCount()
    {
        return $this->elementConstitutifs()->count();
    }

    /**
     * Obtenir le nombre d'enseignants intervenant dans cette UE.
     * 
     * @return int
     */
    public function getTeacherCount()
    {
        return $this->teachers()->count();
    }
} 