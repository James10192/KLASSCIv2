<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPSpecialty extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'esbtp_specialties';

    protected $fillable = [
        'name',
        'code',
        'department_id',
        'cycle_id',
        'coordinator_name',
        'description',
        'career_opportunities',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(ESBTPDepartment::class, 'department_id');
    }

    public function cycle()
    {
        return $this->belongsTo(ESBTPCycle::class, 'cycle_id');
    }

    public function studyYears()
    {
        return $this->hasMany(ESBTPStudyYear::class, 'specialty_id');
    }

    public function students()
    {
        return $this->hasManyThrough(ESBTPEtudiant::class, ESBTPStudyYear::class);
    }
}
