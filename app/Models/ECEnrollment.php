<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ECEnrollment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     * 
     * @var string
     */
    protected $table = 'ec_enrollments';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'element_constitutif_id',
        'ue_enrollment_id',
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
     * Relation avec l'étudiant inscrit à l'EC.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relation avec l'EC auquel l'étudiant est inscrit.
     */
    public function ec()
    {
        return $this->belongsTo(ElementConstitutif::class, 'element_constitutif_id');
    }

    /**
     * Relation avec l'inscription à l'UE parente.
     */
    public function ueEnrollment()
    {
        return $this->belongsTo(UEEnrollment::class, 'ue_enrollment_id');
    }

    /**
     * Relation avec les résultats d'évaluation liés à cet EC.
     */
    public function evaluationResults()
    {
        return $this->hasManyThrough(
            EvaluationResult::class,
            Evaluation::class,
            'element_constitutif_id', // Clé étrangère sur Evaluation
            'evaluation_id', // Clé étrangère sur EvaluationResult
            'element_constitutif_id', // Clé locale sur ECEnrollment
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
     * Vérifier si l'EC est validé.
     * 
     * @return bool
     */
    public function isValidated()
    {
        return $this->status === 'validé' || ($this->final_grade !== null && $this->final_grade >= 10);
    }

    /**
     * Calculer la note finale à partir des résultats d'évaluation.
     * 
     * @return float|null
     */
    public function calculateGradeFromEvaluations()
    {
        $evaluationResults = $this->evaluationResults()
            ->join('evaluations', 'evaluations.id', '=', 'evaluation_results.evaluation_id')
            ->select('evaluation_results.*', 'evaluations.weight')
            ->get();
        
        if ($evaluationResults->isEmpty()) {
            return null;
        }
        
        $totalWeight = 0;
        $weightedSum = 0;
        
        foreach ($evaluationResults as $result) {
            if ($result->grade !== null && $result->weight > 0) {
                $weightedSum += $result->grade * $result->weight;
                $totalWeight += $result->weight;
            }
        }
        
        if ($totalWeight === 0) {
            return null;
        }
        
        return round($weightedSum / $totalWeight, 2);
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
        
        // Si cette inscription EC fait partie d'une inscription UE, mettre à jour l'UE
        if ($this->ue_enrollment_id) {
            $this->ueEnrollment->final_grade = $this->ueEnrollment->calculateGradeFromECs();
            $this->ueEnrollment->updateStatus();
        }
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