<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class ESBTPTeacher extends Model
{
    use HasFactory;

    protected $table = 'esbtp_teachers';

    protected $fillable = [
        'user_id',
        'matricule',
        'title',
        'specialization',
        'status',
        'teaching_hours_due',
        'phone',
        'email',
        'address',
        'city',
        'country',
        'postal_code',
        'bio',
        'research_interests',
        'website',
        'created_by',
        'updated_by',
        'is_active',
        'department_id',
        'laboratory_id',
        'grade',
        'office_location',
        'employee_id'
    ];

    protected $casts = [
        'research_interests' => 'array',
        'is_active' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($teacher) {
            if (empty($teacher->employee_id)) {
                $teacher->employee_id = self::generateEmployeeId();
            }

            // Set default department_id if not provided
            if (empty($teacher->department_id)) {
                // Get the first department or set a default value
                $defaultDepartment = \App\Models\Department::first();
                $teacher->department_id = $defaultDepartment ? $defaultDepartment->id : 1;
            }
        });
    }

    protected static function generateEmployeeId()
    {
        $year = Carbon::now()->format('Y');
        $lastTeacher = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastTeacher ? (int)substr($lastTeacher->employee_id, -4) + 1 : 1;
        return sprintf('EMP-%s-%04d', $year, $sequence);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    public function laboratory()
    {
        return $this->belongsTo(\App\Models\Laboratory::class);
    }

    public function seancesCours()
    {
        return $this->hasMany(ESBTPSeanceCours::class, 'teacher_id');
    }

    public function attendances()
    {
        return $this->hasMany(ESBTPTeacherAttendance::class, 'teacher_id');
    }

    // Accesseur pour obtenir le nom complet
    public function getFullNameAttribute()
    {
        return $this->user ? $this->user->firstname . ' ' . $this->user->lastname : 'N/A';
    }

    // Accesseur pour obtenir le nom (utilise name en fallback)
    public function getNameAttribute()
    {
        if (!$this->user) {
            return 'N/A';
        }
        
        // Si firstname et lastname existent, les utiliser
        if ($this->user->firstname && $this->user->lastname) {
            return $this->user->firstname . ' ' . $this->user->lastname;
        }
        
        // Sinon utiliser le champ name
        return $this->user->name ?: 'N/A';
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    
    public function availabilities()
    {
        return $this->hasMany(ESBTPTeacherAvailability::class, 'teacher_id');
    }
}
