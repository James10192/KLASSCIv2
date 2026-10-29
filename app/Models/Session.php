<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_current',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    /**
     * Get the classes for the session.
     */
    public function classes()
    {
        return $this->hasMany(ClassModel::class);
    }

    /**
     * Get the students for the session.
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Get the exams for the session.
     */
    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Get the timetables for the session.
     */
    public function timetables()
    {
        return $this->hasMany(Timetable::class);
    }
} 