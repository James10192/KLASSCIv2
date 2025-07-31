<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ESBTPTeacherAttendance extends Model
{
    use HasFactory;

    protected $table = 'esbtp_teacher_attendances';

    protected $fillable = [
        'teacher_id',
        'course_id',
        'daily_code_id',
        'date',
        'status',
        'attempts',
        'validated_at',
    ];

    protected $casts = [
        'date' => 'date',
        'validated_at' => 'datetime',
        'attempts' => 'integer',
        'device_info' => 'array',
        'geolocation_data' => 'array',
    ];

    /**
     * Relation avec l'enseignant
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Relation avec le cours
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(ESBTPCourse::class, 'course_id');
    }

    /**
     * Relation avec le code d'émargement
     */
    public function dailyCode(): BelongsTo
    {
        return $this->belongsTo(ESBTPDailyCode::class, 'daily_code_id');
    }

    /**
     * Vérifie si la présence est bloquée après 3 tentatives
     */
    public function isBlocked(): bool
    {
        return $this->attempts >= 3 || $this->status === 'bloqué';
    }

    /**
     * Incrémente le nombre de tentatives
     */
    public function incrementAttempts(): void
    {
        $this->attempts++;
        if ($this->attempts >= 3) {
            $this->status = 'bloqué';
        }
        $this->save();
    }

    /**
     * Marque la présence comme validée
     */
    public function markAsValidated(): void
    {
        $this->status = 'fait';
        $this->validated_at = now();
        $this->save();

        // Déclencher l'événement pour mettre à jour les heures de planification
        try {
            // Chercher la séance de cours correspondante
            $seance = \App\Models\ESBTPSeanceCours::where('enseignant_id', $this->teacher_id)
                ->whereDate('date', $this->date)
                ->first();

            if ($seance) {
                event(new \App\Events\TeacherAttendanceValidated($this, $seance));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur lors du déclenchement de l\'événement TeacherAttendanceValidated', [
                'attendance_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Vérifie si l'émargement est dans la fenêtre horaire autorisée
     */
    public function isWithinTimeWindow()
    {
        $settings = config('esbtp.attendance');
        $courseStart = $this->course->start_time;
        $courseEnd = $this->course->end_time;

        $earlyWindow = $courseStart->copy()->subMinutes($settings['allowed_early_minutes'] ?? 30);
        $lateWindow = $courseEnd->copy()->addMinutes($settings['allowed_late_minutes'] ?? 15);

        return $this->date->between($earlyWindow, $lateWindow);
    }

    /**
     * Vérifie si la géolocalisation est valide
     */
    public function hasValidGeolocation()
    {
        // Désactivé temporairement : la géolocalisation n'est plus requise
        return true;
    }

    /**
     * Calcule la distance en mètres entre deux points géographiques
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Rayon de la Terre en mètres

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $latDelta = $lat2 - $lat1;
        $lonDelta = $lon2 - $lon1;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($lat1) * cos($lat2) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    public function isWithinAllowedTimeframe(): bool
    {
        $settings = app(ESBTPAttendanceSettings::class);
        $course = $this->course;

        $earliestTime = $course->start_time->subMinutes($settings->get('allowed_early_minutes', 30));
        $latestTime = $course->end_time->addMinutes($settings->get('allowed_late_minutes', 15));

        return now()->between($earliestTime, $latestTime);
    }

    public function isWithinAllowedDistance(): bool
    {
        if (!$this->geolocation_data) {
            return false;
        }

        $settings = app(ESBTPAttendanceSettings::class);
        $maxDistance = $settings->get('max_distance_meters', 100);
        $schoolLat = $settings->get('school_latitude', 0);
        $schoolLon = $settings->get('school_longitude', 0);

        $distance = $this->calculateDistance(
            $this->geolocation_data['latitude'],
            $this->geolocation_data['longitude'],
            $schoolLat,
            $schoolLon
        );

        return $distance <= $maxDistance;
    }

    public function determineStatus(): string
    {
        $course = $this->course;
        return now()->gt($course->start_time) ? 'late' : 'present';
    }

    public function validate(User $validator, string $notes = null): void
    {
        $this->validation_status = 'validated';
        $this->validation_notes = $notes;
        $this->validated_by = $validator->id;
        $this->validated_at = now();
        $this->save();
    }

    public function reject(User $validator, string $notes): void
    {
        $this->validation_status = 'rejected';
        $this->validation_notes = $notes;
        $this->validated_by = $validator->id;
        $this->validated_at = now();
        $this->save();
    }

    public static function getAttendanceStatistics(ESBTPEnseignant $enseignant, $startDate = null, $endDate = null): array
    {
        $query = self::where('teacher_id', $enseignant->id)
            ->where('validation_status', 'validated');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        $attendances = $query->get();

        return [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'presence_rate' => $attendances->count() > 0
                ? round(($attendances->count() / $enseignant->emploisDuTemps()->count()) * 100, 2)
                : 0
        ];
    }

    public function hasValidDeviceInfo(): bool
    {
        if (!$this->device_info || !isset($this->device_info['fingerprint'])) {
            return false;
        }

        $recentAttendance = self::where('teacher_id', $this->teacher_id)
            ->where('id', '!=', $this->id)
            ->latest()
            ->first();

        if (!$recentAttendance) {
            return true;
        }

        $oldDeviceInfo = $recentAttendance->device_info;
        if (!$oldDeviceInfo || !isset($oldDeviceInfo['fingerprint'])) {
            return true;
        }

        return $oldDeviceInfo['fingerprint'] === $this->device_info['fingerprint'];
    }

    public function hasValidIpAddress(): bool
    {
        if (!$this->ip_address) {
            return false;
        }

        $recentAttendance = self::where('teacher_id', $this->teacher_id)
            ->where('id', '!=', $this->id)
            ->latest()
            ->first();

        if (!$recentAttendance) {
            return true;
        }

        return $recentAttendance->ip_address === $this->ip_address;
    }

    /**
     * Accessor for signed_at to provide backward compatibility
     * Returns validated_at value
     */
    public function getSignedAtAttribute()
    {
        return $this->validated_at;
    }
}
