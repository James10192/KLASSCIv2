<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPContinuingEducation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'esbtp_continuing_education';

    protected $fillable = [
        'name',
        'code',
        'department_id',
        'cycle_id',
        'coordinator_name',
        'description',
        'duration',
        'duration_unit',
        'price',
        'start_date',
        'end_date',
        'prerequisites',
        'objectives',
        'target_audience',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'duration' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function department()
    {
        return $this->belongsTo(ESBTPDepartment::class, 'department_id');
    }

    public function cycle()
    {
        return $this->belongsTo(ESBTPCycle::class, 'cycle_id');
    }

    public function enrollments()
    {
        return $this->hasMany(ESBTPContinuingEducationEnrollment::class, 'program_id');
    }

    public function getDurationTextAttribute()
    {
        $unit = match($this->duration_unit) {
            'days' => $this->duration > 1 ? 'jours' : 'jour',
            'weeks' => $this->duration > 1 ? 'semaines' : 'semaine',
            'months' => $this->duration > 1 ? 'mois' : 'mois',
            default => $this->duration_unit
        };

        return "{$this->duration} {$unit}";
    }
}
