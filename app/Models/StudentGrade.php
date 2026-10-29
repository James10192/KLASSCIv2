<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentGrade extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'student_grades';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'student_id',
        'evaluation_id',
        'grade',
        'status',
        'comment',
        'created_by',
        'updated_by',
    ];

    /**
     * Les attributs à convertir en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'grade' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'present',
    ];

    /**
     * Relation avec l'étudiant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Relation avec l'évaluation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }

    /**
     * Relation avec l'utilisateur qui a créé la note.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour la note.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope pour filtrer les notes par statut.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pour filtrer les notes par étudiant.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $studentId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope pour filtrer les notes par évaluation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $evaluationId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForEvaluation($query, $evaluationId)
    {
        return $query->where('evaluation_id', $evaluationId);
    }

    /**
     * Determine si l'étudiant a réussi l'évaluation.
     *
     * @return bool
     */
    public function hasPassed()
    {
        if ($this->status !== 'present' || $this->grade === null) {
            return false;
        }

        $passingGrade = $this->evaluation->passing_grade;
        if ($passingGrade === null) {
            // Default to 50% of total points if no passing grade is set
            $passingGrade = $this->evaluation->total_points * 0.5;
        }

        return $this->grade >= $passingGrade;
    }

    /**
     * Calcule le pourcentage de réussite.
     *
     * @return float|null
     */
    public function getPercentage()
    {
        if ($this->status !== 'present' || $this->grade === null || $this->evaluation->total_points == 0) {
            return 0;
        }

        return round(($this->grade / $this->evaluation->total_points) * 100, 2);
    }

    /**
     * Convertit la note sur une autre échelle.
     *
     * @param  int  $scale The maximum value of the target scale
     * @return float|null
     */
    public function convertToScale($scale)
    {
        if ($this->status !== 'present' || $this->grade === null || $this->evaluation->total_points == 0) {
            return null;
        }

        $ratio = $this->grade / $this->evaluation->total_points;
        return round($ratio * $scale, 2);
    }
} 