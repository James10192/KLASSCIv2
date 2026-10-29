<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UEEnrollment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     * 
     * @var string
     */
    protected $table = 'ue_enrollments';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'unite_enseignement_id',
        'academic_year',
        'semester',
        'status',
        'final_grade',
        'is_retaking',
        'comments',
        'created_by',
        'updated_by',
    ];

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'final_grade' => 'float',
        'is_retaking' => 'boolean',
    ];

    /**
     * Relation avec l'étudiant inscrit à l'UE.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relation avec l'UE à laquelle l'étudiant est inscrit.
     */
    public function ue()
    {
        return $this->belongsTo(UniteEnseignement::class, 'unite_enseignement_id');
    }

    /**
     * Relation avec les inscriptions aux ECs liées à cette inscription UE.
     */
    public function ecEnrollments()
    {
        return $this->hasMany(ECEnrollment::class, 'ue_enrollment_id');
    }

    /**
     * Relation avec les résultats d'évaluation liés à cette UE.
     */
    public function evaluationResults()
    {
        return $this->hasManyThrough(
            EvaluationResult::class,
            Evaluation::class,
            'unite_enseignement_id', // Clé étrangère sur Evaluation
            'evaluation_id', // Clé étrangère sur EvaluationResult
            'unite_enseignement_id', // Clé locale sur UEEnrollment
            'id' // Clé locale sur Evaluation
        )->where('evaluation_results.student_id', $this->student_id);
    }

    /**
     * Relation avec l'utilisateur qui a créé cette inscription.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour cette inscription.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Vérifier si l'UE est validée.
     * 
     * @return bool
     */
    public function isValidated()
    {
        return $this->status === 'validé' || ($this->final_grade !== null && $this->final_grade >= 10);
    }

    /**
     * Calculer la moyenne de l'UE à partir des notes des ECs.
     * 
     * @return float|null
     */
    public function calculateGradeFromECs()
    {
        $ecEnrollments = $this->ecEnrollments()->with('ec')->get();
        
        if ($ecEnrollments->isEmpty()) {
            return null;
        }
        
        $totalCoefficient = 0;
        $weightedSum = 0;
        
        foreach ($ecEnrollments as $enrollment) {
            if ($enrollment->final_grade !== null && $enrollment->ec->coefficient > 0) {
                $weightedSum += $enrollment->final_grade * $enrollment->ec->coefficient;
                $totalCoefficient += $enrollment->ec->coefficient;
            }
        }
        
        if ($totalCoefficient === 0) {
            return null;
        }
        
        return round($weightedSum / $totalCoefficient, 2);
    }

    /**
     * Mettre à jour le statut en fonction de la note finale.
     */
    public function updateStatus()
    {
        if ($this->final_grade === null) {
            return;
        }
        
        if ($this->final_grade >= 10) {
            $this->status = 'validé';
        } else {
            $this->status = 'ajourné';
        }
        
        $this->save();
    }

    /**
     * Scope pour filtrer les inscriptions par année académique.
     */
    public function scopeInAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    /**
     * Scope pour filtrer les inscriptions par semestre.
     */
    public function scopeInSemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }

    /**
     * Scope pour filtrer les inscriptions par statut.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pour filtrer les inscriptions validées.
     */
    public function scopeValidated($query)
    {
        return $query->where('status', 'validé')
                     ->orWhere(function($q) {
                         $q->whereNotNull('final_grade')
                           ->where('final_grade', '>=', 10);
                     });
    }

    /**
     * Scope pour filtrer les inscriptions non validées.
     */
    public function scopeNotValidated($query)
    {
        return $query->where('status', '!=', 'validé')
                     ->where(function($q) {
                         $q->whereNull('final_grade')
                           ->orWhere('final_grade', '<', 10);
                     });
    }

    /**
     * Scope pour filtrer les réinscriptions.
     */
    public function scopeRetaking($query)
    {
        return $query->where('is_retaking', true);
    }
} 