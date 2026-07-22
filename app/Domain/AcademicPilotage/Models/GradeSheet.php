<?php

namespace App\Domain\AcademicPilotage\Models;

use App\Domain\AcademicPilotage\Enums\GradeSheetEntryMode;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPTeacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class GradeSheet extends Model implements AuditableContract
{
    use Auditable, SoftDeletes;

    protected $table = 'esbtp_grade_sheets';

    protected $fillable = [
        'code',
        'obligation_key',
        'evaluation_id',
        'classe_id',
        'matiere_id',
        'annee_universitaire_id',
        'teacher_id',
        'academic_system',
        'semester',
        'evaluation_type',
        'entry_mode',
        'expected_at',
        'source',
        'observations',
        'metadata',
    ];

    protected $casts = [
        'entry_mode' => GradeSheetEntryMode::class,
        'status' => GradeSheetStatus::class,
        'expected_at' => 'datetime',
        'submitted_at' => 'datetime',
        'received_at' => 'datetime',
        'entry_started_at' => 'datetime',
        'entered_at' => 'datetime',
        'controlled_at' => 'datetime',
        'validated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
        'lock_version' => 'integer',
    ];

    protected $auditInclude = [
        'teacher_id',
        'assigned_processor_id',
        'entry_mode',
        'status',
        'expected_at',
        'submitted_at',
        'received_at',
        'entry_started_at',
        'entered_at',
        'controlled_at',
        'validated_at',
        'cancelled_at',
        'submitted_by',
        'received_by',
        'entered_by',
        'controlled_by',
        'validated_by',
        'cancelled_by',
        'observations',
        'lock_version',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(ESBTPEvaluation::class, 'evaluation_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(ESBTPTeacher::class, 'teacher_id');
    }

    public function assignedProcessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_processor_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function controlledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'controlled_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(GradeSheetEntry::class, 'grade_sheet_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(GradeSheetEvent::class, 'grade_sheet_id');
    }

    public function latestEvent(): HasOne
    {
        return $this->hasOne(GradeSheetEvent::class, 'grade_sheet_id')->latestOfMany('occurred_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(GradeSheetDocument::class, 'grade_sheet_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(GradeSheetRevision::class, 'grade_sheet_id');
    }

    public function currentValidatedRevision(): HasOne
    {
        return $this->hasOne(GradeSheetRevision::class, 'grade_sheet_id')->latestOfMany('revision_number');
    }
}
