<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ESBTPAttendanceSettings extends Model
{
    protected $table = 'esbtp_attendance_settings';

    protected $fillable = [
        'settings'
    ];

    protected $casts = [
        'settings' => 'array'
    ];

    private const CACHE_PREFIX = 'esbtp_attendance_settings:';
    private const CACHE_TTL = 3600; // 1 hour

    public static function getSettings()
    {
        return static::first()?->settings ?? [
            'code_expiration_hours' => 24,
            'max_attempts' => 3,
            'early_marking_minutes' => 15,
            'late_marking_minutes' => 30,
            'notify_admin_on_failure' => true,
            'notify_teacher_reminder' => true,
            'enforce_ip_validation' => true,
            'enforce_device_validation' => true,
        ];
    }

    public static function get(string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, $value, string $description = null): void
    {
        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description
            ]
        );

        Cache::forget(self::CACHE_PREFIX . $key);
    }

    public static function getAll(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'all', self::CACHE_TTL, function () {
            return self::all()->pluck('value', 'key')->toArray();
        });
    }

    public static function getGeolocationSettings(): array
    {
        return [
            'required' => (bool) self::get('geolocation_required', false),
            'max_distance' => (int) self::get('max_distance_meters', 100),
            'school_latitude' => (float) self::get('school_latitude', 0),
            'school_longitude' => (float) self::get('school_longitude', 0)
        ];
    }

    public static function getTimeSettings(): array
    {
        return [
            'code_validity_hours' => (int) self::get('code_validity_hours', 24),
            'allowed_early_minutes' => (int) self::get('allowed_early_minutes', 30),
            'allowed_late_minutes' => (int) self::get('allowed_late_minutes', 15),
            'display_code_duration' => (int) self::get('display_code_duration', 60)
        ];
    }

    public static function getSecuritySettings(): array
    {
        return [
            'max_attempts' => (int) self::get('max_attempts', 3),
            'block_duration_minutes' => (int) self::get('block_duration_minutes', 60)
        ];
    }

    public static function validateGeolocation(float $latitude, float $longitude): bool
    {
        if (!(bool) self::get('geolocation_required', false)) {
            return true;
        }

        $maxDistance = (int) self::get('max_distance_meters', 100);
        $schoolLat = (float) self::get('school_latitude', 0);
        $schoolLon = (float) self::get('school_longitude', 0);

        $distance = self::calculateDistance($latitude, $longitude, $schoolLat, $schoolLon);

        return $distance <= $maxDistance;
    }

    public static function validateTimeframe(\DateTime $courseTime): bool
    {
        $earlyMinutes = (int) self::get('allowed_early_minutes', 30);
        $lateMinutes = (int) self::get('allowed_late_minutes', 15);

        $earliestTime = (clone $courseTime)->modify("-{$earlyMinutes} minutes");
        $latestTime = (clone $courseTime)->modify("+{$lateMinutes} minutes");

        return now()->between($earliestTime, $latestTime);
    }

    private static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Rayon de la Terre en mètres

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $latDelta = $lat2 - $lat1;
        $lonDelta = $lon2 - $lon1;

        $a = sin($latDelta/2) * sin($latDelta/2) +
             cos($lat1) * cos($lat2) *
             sin($lonDelta/2) * sin($lonDelta/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    public static function clearCache(): void
    {
        $keys = Cache::get(self::CACHE_PREFIX . 'keys', []);
        foreach ($keys as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
        Cache::forget(self::CACHE_PREFIX . 'all');
        Cache::forget(self::CACHE_PREFIX . 'keys');
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            self::clearCache();
        });

        static::deleted(function ($model) {
            self::clearCache();
        });
    }
}
