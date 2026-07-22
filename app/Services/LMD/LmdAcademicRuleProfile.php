<?php

declare(strict_types=1);

namespace App\Services\LMD;

use App\Helpers\SettingsHelper;
use Closure;

final class LmdAcademicRuleProfile
{
    private Closure $resolver;

    /** @param null|Closure(string, mixed): mixed $resolver */
    public function __construct(?Closure $resolver = null)
    {
        $this->resolver = $resolver ?? static fn (string $key, mixed $default = null): mixed => SettingsHelper::get($key, $default);
    }

    public function validationThreshold(): float
    {
        return (float) $this->first(['lmd_validation_threshold', 'lmd_seuil_validation_ecue'], 10);
    }

    public function eliminatoryGrade(): float
    {
        return (float) $this->first(['lmd_note_eliminatoire'], 0);
    }

    public function interUeCompensationEnabled(): bool
    {
        return $this->toBool($this->first(['lmd_compensation_inter_ue', 'lmd_compensation_enabled'], true));
    }

    /** @return array{passable: float, assez_bien: float, bien: float, tres_bien: float, excellent: float} */
    public function mentionThresholds(): array
    {
        return [
            'passable' => (float) $this->first(['lmd_mention_p_threshold'], 10),
            'assez_bien' => (float) $this->first(['lmd_mention_ab_threshold'], 12),
            'bien' => (float) $this->first(['lmd_mention_b_threshold'], 14),
            'tres_bien' => (float) $this->first(['lmd_mention_tb_threshold'], 16),
            'excellent' => (float) $this->first(['lmd_mention_excellent_threshold'], 18),
        ];
    }

    public function mentionFor(?float $average): ?string
    {
        if ($average === null) {
            return null;
        }

        $thresholds = $this->mentionThresholds();
        foreach (['excellent', 'tres_bien', 'bien', 'assez_bien', 'passable'] as $mention) {
            if ($average >= $thresholds[$mention]) {
                return $mention;
            }
        }

        return null;
    }

    public function expectedCreditsPerSemester(): int
    {
        return (int) config('academic_pilotage.lmd.expected_credits_per_semester', 30);
    }

    private function first(array $keys, mixed $default): mixed
    {
        foreach ($keys as $key) {
            $value = ($this->resolver)($key, null);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
    }
}

