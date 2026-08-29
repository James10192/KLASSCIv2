<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parcours extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'parcours';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'formation_id', // ID de la formation à laquelle appartient le parcours
        'responsable_id', // ID de l'utilisateur qui est responsable du parcours
        'created_by',
        'updated_by',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];


    /**
     * Obtenir le responsable du parcours.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Obtenir les étudiants inscrits à ce parcours.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Obtenir les unités d'enseignement (UE) associées à ce parcours.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function uniteEnseignements()
    {
        return $this->belongsToMany(UniteEnseignement::class, 'parcours_unite_enseignement', 'parcours_id', 'unite_enseignement_id')
            ->withPivot('semester', 'is_optional')
            ->withTimestamps();
    }

    /**
     * Obtenir l'utilisateur qui a créé le parcours.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtenir l'utilisateur qui a mis à jour le parcours.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtenir le nombre d'étudiants inscrits à ce parcours.
     * 
     * @return int
     */
    public function getStudentCount()
    {
        return $this->students()->count();
    }

    /**
     * Obtenir le nombre d'UE dans ce parcours.
     * 
     * @return int
     */
    public function getUECount()
    {
        return $this->uniteEnseignements()->count();
    }
} 