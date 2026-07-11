<?php

namespace App\Domain\AcademicPilotage\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class GradeSheetDocument extends Model
{
    public $timestamps = false;

    protected $table = 'esbtp_grade_sheet_documents';

    protected $fillable = [
        'grade_sheet_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'checksum_sha256',
        'uploaded_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'uploaded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Grade sheet documents are immutable after creation.');
        });
        static::deleting(function (): void {
            throw new LogicException('Grade sheet documents are immutable after creation.');
        });
    }

    public function gradeSheet(): BelongsTo
    {
        return $this->belongsTo(GradeSheet::class, 'grade_sheet_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
