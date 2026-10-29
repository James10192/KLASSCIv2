<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the teachers for the designation.
     */
    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }

    /**
     * Get the number of teachers with this designation.
     */
    public function getTeacherCount()
    {
        return $this->teachers()->count();
    }

    /**
     * Get the list of teacher names with this designation.
     */
    public function getTeacherNames()
    {
        return $this->teachers()->with('user')->get()->map(function ($teacher) {
            return $teacher->user->name;
        })->implode(', ');
    }
} 