<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ElementConstitutif extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'element_constitutifs';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'unite_enseignement_id', // UE à laquelle appartient cet EC
        'credits', // Nombre de crédits ECTS
        'coefficient', // Coefficient dans l'UE
        'hours', // Nombre d'heures total
        'cm_hours', // Heures de cours magistraux
        'td_hours', // Heures de travaux dirigés
        'tp_hours', // Heures de travaux pratiques
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
        'coefficient' => 'float',
        'hours' => 'integer',
        'cm_hours' => 'integer',
        'td_hours' => 'integer',
        'tp_hours' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtenir l'UE à laquelle appartient cet EC.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uniteEnseignement()
    {
        return $this->belongsTo(UniteEnseignement::class);
    }

    /**
     * Obtenir l'enseignant responsable de cet EC.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Obtenir les enseignants qui interviennent dans cet EC.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_element_constitutif', 'element_constitutif_id', 'teacher_id')
            ->withPivot('role', 'hours', 'type') // type peut être CM, TD, TP
            ->withTimestamps();
    }

    /**
     * Obtenir les évaluations associées à cet EC.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * Obtenir les séances de cours programmées pour cet EC.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function courseSessions()
    {
        return $this->hasMany(CourseSession::class);
    }

    /**
     * Obtenir l'utilisateur qui a créé l'EC.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtenir l'utilisateur qui a mis à jour l'EC.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtenir le nombre d'enseignants intervenant dans cet EC.
     * 
     * @return int
     */
    public function getTeacherCount()
    {
        return $this->teachers()->count();
    }

    /**
     * Obtenir le nombre d'évaluations pour cet EC.
     * 
     * @return int
     */
    public function getEvaluationCount()
    {
        return $this->evaluations()->count();
    }

    /**
     * Obtenir le nombre de séances programmées pour cet EC.
     * 
     * @return int
     */
    public function getSessionCount()
    {
        return $this->courseSessions()->count();
    }
} 