<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evaluation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'evaluations';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'element_constitutif_id', // EC évalué
        'type', // Type d'évaluation (examen, contrôle continu, projet, etc.)
        'date', // Date de l'évaluation
        'start_time', // Heure de début
        'end_time', // Heure de fin
        'location', // Lieu de l'évaluation
        'coefficient', // Coefficient dans la note finale de l'EC
        'max_score', // Score maximum possible
        'is_published', // Si les résultats sont publiés
        'created_by',
        'updated_by',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'coefficient' => 'float',
        'max_score' => 'float',
        'is_published' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtenir l'EC auquel appartient cette évaluation.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function elementConstitutif()
    {
        return $this->belongsTo(ElementConstitutif::class);
    }

    /**
     * Obtenir les notes des étudiants pour cette évaluation.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Obtenir les surveillants assignés à cette évaluation.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function supervisors()
    {
        return $this->belongsToMany(User::class, 'evaluation_supervisor', 'evaluation_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Obtenir l'utilisateur qui a créé l'évaluation.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtenir l'utilisateur qui a mis à jour l'évaluation.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtenir le nombre d'étudiants ayant une note pour cette évaluation.
     * 
     * @return int
     */
    public function getGradeCount()
    {
        return $this->grades()->count();
    }

    /**
     * Obtenir la note moyenne pour cette évaluation.
     * 
     * @return float|null
     */
    public function getAverageGrade()
    {
        $grades = $this->grades()->pluck('score');
        
        if ($grades->isEmpty()) {
            return null;
        }
        
        return $grades->avg();
    }

    /**
     * Obtenir la note la plus haute pour cette évaluation.
     * 
     * @return float|null
     */
    public function getHighestGrade()
    {
        $grades = $this->grades()->pluck('score');
        
        if ($grades->isEmpty()) {
            return null;
        }
        
        return $grades->max();
    }

    /**
     * Obtenir la note la plus basse pour cette évaluation.
     * 
     * @return float|null
     */
    public function getLowestGrade()
    {
        $grades = $this->grades()->pluck('score');
        
        if ($grades->isEmpty()) {
            return null;
        }
        
        return $grades->min();
    }

    /**
     * Vérifier si l'évaluation est à venir.
     * 
     * @return bool
     */
    public function isUpcoming()
    {
        return now()->lt($this->date);
    }

    /**
     * Vérifier si l'évaluation est passée.
     * 
     * @return bool
     */
    public function isPast()
    {
        return now()->gt($this->date);
    }

    /**
     * Vérifier si l'évaluation est en cours.
     * 
     * @return bool
     */
    public function isInProgress()
    {
        $now = now();
        return $now->gte($this->start_time) && $now->lte($this->end_time);
    }
} 