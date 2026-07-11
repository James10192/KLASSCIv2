<?php

namespace App\Domain\AcademicPilotage\Models;

use App\Domain\AcademicPilotage\Enums\AcademicAlertSeverity;
use App\Domain\AcademicPilotage\Enums\AcademicAlertStatus;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPTeacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AcademicAlert extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'esbtp_academic_alerts';

    protected $fillable = [
        'fingerprint',
        'type',
        'severity',
        'annee_universitaire_id',
        'semester',
        'classe_id',
        'etudiant_id',
        'matiere_id',
        'teacher_id',
        'entity_type',
        'entity_id',
        'message',
        'recommended_action',
        'metadata',
        'source_version',
        'detected_at',
        'last_seen_at',
    ];

    protected $casts = [
        'severity' => AcademicAlertSeverity::class,
        'status' => AcademicAlertStatus::class,
        'metadata' => 'array',
        'detected_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    protected $auditInclude = [
        'severity',
        'status',
        'assignee_id',
        'message',
        'recommended_action',
        'last_seen_at',
        'acknowledged_at',
        'acknowledged_by',
        'resolved_at',
        'resolved_by',
        'dismissed_at',
        'dismissed_by',
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

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(ESBTPTeacher::class, 'teacher_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function dismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AcademicAlertEvent::class, 'academic_alert_id');
    }
}
