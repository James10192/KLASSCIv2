<?php

namespace App\Domain\AcademicPilotage\Models;

use App\Domain\AcademicPilotage\Enums\AcademicAlertStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AcademicAlertEvent extends Model
{
    public $timestamps = false;

    protected $table = 'esbtp_academic_alert_events';

    protected $fillable = [
        'academic_alert_id',
        'event_type',
        'reason',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'from_status' => AcademicAlertStatus::class,
        'to_status' => AcademicAlertStatus::class,
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Academic alert events are append-only.');
        });
        static::deleting(function (): void {
            throw new LogicException('Academic alert events are append-only.');
        });
    }

    public function academicAlert(): BelongsTo
    {
        return $this->belongsTo(AcademicAlert::class, 'academic_alert_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
