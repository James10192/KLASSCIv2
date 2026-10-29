<?php

namespace App\Domain\AcademicPilotage\Models;

use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeSheetEntry extends Model
{
    protected $table = 'esbtp_grade_sheet_entries';

    protected $fillable = [
        'grade_sheet_id',
        'etudiant_id',
        'source',
        'metadata',
    ];

    protected $casts = [
        'status' => GradeSheetEntryStatus::class,
        'resolved_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function gradeSheet(): BelongsTo
    {
        return $this->belongsTo(GradeSheet::class, 'grade_sheet_id');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(ESBTPNote::class, 'note_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
