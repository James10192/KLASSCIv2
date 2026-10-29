<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'relation',
        'phone',
        'alternate_phone',
        'email',
        'occupation',
        'address',
        'city',
        'state',
        'country',
        'pincode',
    ];

    /**
     * Get the user that owns the guardian.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the students for the guardian.
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Get the number of students for the guardian.
     */
    public function getStudentCount()
    {
        return $this->students()->count();
    }

    /**
     * Get the list of student names for the guardian.
     */
    public function getStudentNames()
    {
        return $this->students()->with('user')->get()->map(function ($student) {
            return $student->user->name;
        })->implode(', ');
    }

    /**
     * Get the list of student classes for the guardian.
     */
    public function getStudentClasses()
    {
        return $this->students()->with('class')->get()->map(function ($student) {
            return $student->class->name;
        })->implode(', ');
    }
} 