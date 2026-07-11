<?php

namespace App\Domain\AcademicPilotage\Models;

use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class GradeSheetEvent extends Model
{
    public $timestamps = false;

    protected $table = 'esbtp_grade_sheet_events';

    protected $fillable = [
        'grade_sheet_id',
        'event_type',
        'reason',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'from_status' => GradeSheetStatus::class,
        'to_status' => GradeSheetStatus::class,
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Grade sheet events are append-only.');
        });
        static::deleting(function (): void {
            throw new LogicException('Grade sheet events are append-only.');
        });
    }

    public function gradeSheet(): BelongsTo
    {
        return $this->belongsTo(GradeSheet::class, 'grade_sheet_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
