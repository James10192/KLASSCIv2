<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'session_id',
        'semester_id',
        'start_date',
        'end_date',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the session that owns the exam.
     */
    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the semester that owns the exam.
     */
    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * Get the grades for the exam.
     */
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Check if the exam is past.
     */
    public function isPast()
    {
        return $this->end_date->isPast();
    }

    /**
     * Check if the exam is future.
     */
    public function isFuture()
    {
        return $this->start_date->isFuture();
    }

    /**
     * Check if the exam is ongoing.
     */
    public function isOngoing()
    {
        $now = now();
        return $this->start_date->lte($now) && $this->end_date->gte($now);
    }

    /**
     * Get the average grade for a specific student.
     */
    public function getAverageGradeForStudent($studentId)
    {
        $grades = $this->grades()->where('student_id', $studentId)->get();
        
        if ($grades->isEmpty()) {
            return 0;
        }

        $sum = $grades->sum('grade_value');
        return $sum / $grades->count();
    }

    /**
     * Get the average grade for a specific subject.
     */
    public function getAverageGradeForSubject($subjectId)
    {
        $grades = $this->grades()->where('subject_id', $subjectId)->get();
        
        if ($grades->isEmpty()) {
            return 0;
        }

        $sum = $grades->sum('grade_value');
        return $sum / $grades->count();
    }
} 