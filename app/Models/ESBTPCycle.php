<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPCycle extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_cycles';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'duration_years',
        'diploma_awarded',
        'description',
        'is_active'
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'duration_years' => 'integer',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime'
    ];

    /**
     * Relation avec les spécialités.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function specialties()
    {
        return $this->hasMany(ESBTPSpecialty::class, 'cycle_id');
    }

    /**
     * Obtenir le nombre total d'étudiants dans ce cycle.
     *
     * @return int
     */
    public function getTotalStudentsAttribute()
    {
        return $this->specialties()
            ->withCount('students')
            ->get()
            ->sum('students_count');
    }

    /**
     * Vérifie si le cycle peut être supprimé.
     *
     * @return bool
     */
    public function canBeDeleted()
    {
        return $this->specialties()->count() === 0;
    }

    /**
     * Get the students associated with the cycle.
     */
    public function students()
    {
        return $this->hasManyThrough(ESBTPEtudiant::class, ESBTPSpecialty::class);
    }

    /**
     * Get the classes associated with the cycle.
     */
    public function classes()
    {
        return $this->hasManyThrough(ESBTPClass::class, ESBTPSpecialty::class);
    }

    /**
     * Get the teachers associated with the cycle.
     */
    public function teachers()
    {
        return $this->belongsToMany(ESBTPTeacher::class, 'esbtp_teacher_cycle', 'cycle_id', 'teacher_id')
            ->withTimestamps();
    }
}
