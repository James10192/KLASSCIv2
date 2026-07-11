<?php

namespace App\Domain\AcademicPilotage\Models;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicMetricSnapshot extends Model
{
    protected $table = 'esbtp_academic_metric_snapshots';

    protected $fillable = [
        'context_hash',
        'scope_type',
        'scope_id',
        'academic_system',
        'annee_universitaire_id',
        'semester',
        'classe_id',
        'etudiant_id',
        'user_id',
        'academic_score',
        'operational_score',
        'coverage_pct',
        'confidence_pct',
        'level',
        'metrics',
        'factors',
        'reasons',
        'evidence_hash',
        'engine_version',
        'is_dirty',
        'stale_at',
        'calculated_at',
    ];

    protected $casts = [
        'academic_score' => 'decimal:2',
        'operational_score' => 'decimal:2',
        'coverage_pct' => 'integer',
        'confidence_pct' => 'integer',
        'metrics' => 'array',
        'factors' => 'array',
        'reasons' => 'array',
        'is_dirty' => 'boolean',
        'stale_at' => 'datetime',
        'calculated_at' => 'datetime',
    ];

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
