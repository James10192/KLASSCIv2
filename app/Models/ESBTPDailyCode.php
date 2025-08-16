<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class ESBTPDailyCode extends Model
{
    protected $table = 'esbtp_daily_codes';

    protected $fillable = [
        'code',
        'valid_from',
        'valid_until',
        'is_active',
        'status',
        'total_attempts',
        'successful_attempts',
        'failed_attempts',
        'last_attempt_at',
        'created_by',
        'description',
        'type',
        'seance_id'
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'last_attempt_at' => 'datetime'
    ];

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ESBTPTeacherAttendance::class, 'daily_code_id');
    }

    public function seance(): BelongsTo
    {
        return $this->belongsTo(ESBTPSeanceCours::class, 'seance_id');
    }

    public function isValid(): bool
    {
        return $this->is_active && $this->valid_until->isFuture();
    }

    public static function generateCode(): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';

        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $code;
    }

    public static function createDailyCode(string $description = null, int $durationMinutes = null, int $seanceId = null): self
    {
        // Ne pas invalider automatiquement - c'est géré par le contrôleur maintenant
        // pour l'invalidation intelligente

        // Déterminer la durée de validité
        if ($durationMinutes) {
            $validityMinutes = $durationMinutes;
        } else {
            try {
                $settings = app(ESBTPAttendanceSettings::class);
                $validityHours = $settings->get('code_validity_hours', 24);
                $validityMinutes = $validityHours * 60;
            } catch (\Exception $e) {
                // Fallback si les settings ne sont pas disponibles
                $validityMinutes = 24 * 60; // 24 heures par défaut
            }
        }

        return self::create([
            'code' => self::generateCode(),
            'valid_from' => now(),
            'valid_until' => now()->addMinutes($validityMinutes),
            'is_active' => true,
            'status' => 'active',
            'created_by' => auth()->id(),
            'description' => $description,
            'type' => $seanceId ? 'session' : 'journee',
            'seance_id' => $seanceId
        ]);
    }

    public function recordAttempt(bool $success): void
    {
        $this->total_attempts++;

        if ($success) {
            $this->successful_attempts++;
        } else {
            $this->failed_attempts++;
        }

        $this->last_attempt_at = now();
        $this->save();
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->is_active = false;
        $this->save();
    }

    public function expire(): void
    {
        $this->status = 'expired';
        $this->is_active = false;
        $this->save();
    }

    public function getAttemptsStatistics(): array
    {
        return [
            'total' => $this->total_attempts,
            'successful' => $this->successful_attempts,
            'failed' => $this->failed_attempts,
            'success_rate' => $this->total_attempts > 0
                ? round(($this->successful_attempts / $this->total_attempts) * 100, 2)
                : 0
        ];
    }

    public function getRemainingValidityInMinutes(): int
    {
        if (!$this->isValid()) {
            return 0;
        }

        return max(0, now()->diffInMinutes($this->valid_until));
    }

    public function shouldRefresh(): bool
    {
        if (!$this->isValid()) {
            return true;
        }

        $settings = app(ESBTPAttendanceSettings::class);
        $displayDuration = $settings->get('display_code_duration', 60);

        return $this->created_at->addMinutes($displayDuration)->isPast();
    }
}
