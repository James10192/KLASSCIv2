<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\DTO;

use InvalidArgumentException;

final class AcademicMetricEvidenceSchema
{
    private const SCHEMAS = [
        'academic_performance' => [
            'sample_count' => 'non_negative_integer',
            'state' => ['enum', [
                'no_data', 'semester_complete', 'semester_complete_no_coefficients',
                'annual_complete', 'annual_complete_no_coefficients', 'annual_incomplete',
            ]],
            'notes_count' => 'non_negative_integer',
            'subjects_count' => 'non_negative_integer',
            'coefficients_missing' => 'boolean',
            'attendance_adjustment' => 'finite_number',
            'configured_credits' => 'non_negative_integer',
            'expected_credits' => 'non_negative_integer',
            'observed_credits' => 'non_negative_integer',
            'published' => 'boolean',
        ],
        'assessment_completion' => [
            'sample_count' => 'non_negative_integer',
            'configured_sheets' => 'non_negative_integer',
            'applicable_entries' => 'non_negative_integer',
            'resolved_entries' => 'non_negative_integer',
            'missing_entries' => 'non_negative_integer',
            'evidence_hash' => 'sha256',
        ],
        'attendance' => [
            'sample_count' => 'non_negative_integer',
            'source' => ['enum', ['manual_subject', 'manual_global', 'final_attendance']],
            'records_count' => 'non_negative_integer',
            'scheduled_hours' => 'non_negative_number',
            'observed_hours' => 'non_negative_number',
        ],
        'progression' => [
            'sample_count' => 'non_negative_integer',
            'previous_period_available' => 'boolean',
            'average_delta' => 'finite_number',
        ],
        'open_alerts' => [
            'sample_count' => 'non_negative_integer',
            'engine_ready' => 'boolean',
        ],
    ];

    public static function validate(string $metricKey, array $evidence): void
    {
        self::redact($metricKey, $evidence);
    }

    public static function redact(string $metricKey, array $evidence): array
    {
        return self::redactInternal($metricKey, $evidence, true);
    }

    public static function redactLegacy(string $metricKey, array $evidence): array
    {
        return self::redactInternal($metricKey, $evidence, false);
    }

    private static function redactInternal(string $metricKey, array $evidence, bool $strict): array
    {
        $schema = self::SCHEMAS[$metricKey] ?? [];
        $redacted = [];

        foreach ($evidence as $key => $value) {
            if (! is_string($key) || ! array_key_exists($key, $schema)) {
                if (! $strict) {
                    continue;
                }

                throw new InvalidArgumentException(
                    "La preuve {$key} n'est pas autorisee pour la metrique {$metricKey}."
                );
            }

            if (! self::matches($value, $schema[$key])) {
                if (! $strict) {
                    continue;
                }

                throw new InvalidArgumentException("La preuve {$key} ne respecte pas le format autorise.");
            }

            $redacted[$key] = $value;
        }

        return $redacted;
    }

    private static function matches(mixed $value, string|array $shape): bool
    {
        return match (is_array($shape) ? $shape[0] : $shape) {
            'boolean' => is_bool($value),
            'enum' => is_string($value) && in_array($value, $shape[1], true),
            'finite_number' => self::isFiniteNumber($value),
            'non_negative_integer' => is_int($value) && $value >= 0,
            'non_negative_number' => self::isFiniteNumber($value) && $value >= 0,
            'sha256' => is_string($value) && preg_match('/^[a-f0-9]{64}$/', $value) === 1,
            default => false,
        };
    }

    private static function isFiniteNumber(mixed $value): bool
    {
        return (is_int($value) || is_float($value)) && is_finite((float) $value);
    }
}
