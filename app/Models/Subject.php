<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'credit_hours',
    ];

    /**
     * Get the classes for the subject.
     */
    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'class_subject', 'subject_id', 'class_id');
    }

    /**
     * Get the teachers for the subject.
     */
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subject', 'subject_id', 'teacher_id');
    }

    /**
     * Get the grades for the subject.
     */
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Get the timetable entries for the subject.
     */
    public function timetableEntries()
    {
        return $this->hasMany(Timetable::class);
    }
} 