<?php

namespace App\Domain\AcademicPilotage\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class GradeSheetRevision extends Model
{
    public $timestamps = false;
    protected $table = 'esbtp_grade_sheet_revisions';
    protected $guarded = [];
    protected $casts = ['snapshot' => 'array', 'validated_at' => 'datetime', 'created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Grade sheet revisions are append-only.'));
        static::deleting(fn () => throw new LogicException('Grade sheet revisions are append-only.'));
    }

    public function gradeSheet(): BelongsTo { return $this->belongsTo(GradeSheet::class, 'grade_sheet_id'); }
    public function parentRevision(): BelongsTo { return $this->belongsTo(self::class, 'parent_revision_id'); }
    public function validatedBy(): BelongsTo { return $this->belongsTo(User::class, 'validated_by'); }
}
