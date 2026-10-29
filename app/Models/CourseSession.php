<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseSession extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'course_sessions';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'element_constitutif_id', // EC concerné
        'course_class_id', // Classe associée à la séance
        'teacher_id', // Enseignant qui donne le cours
        'classroom_id', // Salle de classe
        'date', // Date de la séance
        'start_time', // Heure de début
        'end_time', // Heure de fin
        'type', // Type de séance (CM, TD, TP)
        'title', // Titre de la séance
        'description', // Description du contenu
        'status', // Statut (planifié, en cours, terminé, annulé)
        'cancellation_reason', // Raison de l'annulation si applicable
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtenir l'EC associé à cette séance.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function elementConstitutif()
    {
        return $this->belongsTo(ElementConstitutif::class);
    }

    /**
     * Obtenir l'enseignant qui donne cette séance.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Obtenir la salle de classe où se déroule cette séance.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Obtenir les présences pour cette séance.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Obtenir les documents associés à cette séance.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Obtenir la classe associée à cette séance.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class);
    }

    /**
     * Obtenir l'utilisateur qui a créé la séance.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtenir l'utilisateur qui a mis à jour la séance.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Vérifier si la séance est à venir.
     * 
     * @return bool
     */
    public function isUpcoming()
    {
        return now()->lt($this->start_time);
    }

    /**
     * Vérifier si la séance est passée.
     * 
     * @return bool
     */
    public function isPast()
    {
        return now()->gt($this->end_time);
    }

    /**
     * Vérifier si la séance est en cours.
     * 
     * @return bool
     */
    public function isInProgress()
    {
        $now = now();
        return $now->gte($this->start_time) && $now->lte($this->end_time);
    }

    /**
     * Vérifier si la séance est annulée.
     * 
     * @return bool
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Obtenir la durée de la séance en minutes.
     * 
     * @return int
     */
    public function getDurationInMinutes()
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Obtenir le taux de présence pour cette séance.
     * 
     * @return float|null
     */
    public function getAttendanceRate()
    {
        $totalStudents = $this->elementConstitutif->uniteEnseignement->parcours()
            ->with('students')
            ->get()
            ->pluck('students')
            ->flatten()
            ->unique('id')
            ->count();
        
        if ($totalStudents === 0) {
            return null;
        }
        
        $presentStudents = $this->attendances()->where('status', 'present')->count();
        
        return ($presentStudents / $totalStudents) * 100;
    }
} 