<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
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
        'start_date',
        'end_date',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the session that owns the semester.
     */
    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the exams for the semester.
     */
    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Get the grades for the semester.
     */
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Check if the semester is current.
     */
    public function isCurrent()
    {
        return $this->is_active;
    }

    /**
     * Check if the semester is past.
     */
    public function isPast()
    {
        return $this->end_date->isPast();
    }

    /**
     * Check if the semester is future.
     */
    public function isFuture()
    {
        return $this->start_date->isFuture();
    }

    /**
     * Check if the semester is ongoing.
     */
    public function isOngoing()
    {
        $now = now();
        return $this->start_date->lte($now) && $this->end_date->gte($now);
    }
} 