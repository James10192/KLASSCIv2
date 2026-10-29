<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'ufr_id',
        'level',
        'year',
        'academic_year',
        'capacity',
        'description',
        'is_active'
    ];

    /**
     * Get the UFR that owns the class.
     */
    public function ufr()
    {
        return $this->belongsTo(UFR::class);
    }

    /**
     * Get the students in this class.
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

}
