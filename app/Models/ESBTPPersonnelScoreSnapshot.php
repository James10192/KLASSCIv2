<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPPersonnelScoreSnapshot extends Model
{
    use HasFactory;

    protected $table = 'esbtp_personnel_score_snapshots';

    protected $fillable = [
        'user_id',
        'teacher_id',
        'role_name',
        'period_type',
        'period_start',
        'period_end',
        'total_score',
        'level',
        'applicable_dimensions_count',
        'excluded_dimensions_count',
        'coverage',
        'confidence',
        'engine_version',
        'evidence_hash',
        'permissions',
        'metrics',
        'breakdown',
        'calculated_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_score' => 'integer',
        'applicable_dimensions_count' => 'integer',
        'excluded_dimensions_count' => 'integer',
        'coverage' => 'float',
        'confidence' => 'float',
        'permissions' => 'array',
        'metrics' => 'array',
        'breakdown' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function teacher()
    {
        return $this->belongsTo(ESBTPTeacher::class, 'teacher_id');
    }
}
