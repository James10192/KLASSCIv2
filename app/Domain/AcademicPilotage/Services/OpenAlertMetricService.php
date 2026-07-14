<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\AcademicMetricValue;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Domain\AcademicPilotage\Enums\AcademicAlertStatus;
use App\Domain\AcademicPilotage\Models\AcademicAlert;
use Illuminate\Support\Facades\Schema;

final class OpenAlertMetricService
{
    private const SCORE_PENALTY_PER_ALERT = 25;

    private ?bool $available = null;

    private array $countsByScope = [];

    public function flush(): void
    {
        $this->countsByScope = [];
    }

    public function forStudent(StudentMetricContext $context): AcademicMetricValue
    {
        if (! $this->isAvailable()) {
            return AcademicMetricValue::unavailable(
                'open_alerts',
                "Le moteur d'alertes académiques n'est pas encore disponible.",
                ['engine_ready' => false],
            );
        }

        $counts = $this->countsForScope($context);
        $count = (int) ($counts[$context->studentId] ?? 0);

        return new AcademicMetricValue(
            key: 'open_alerts',
            value: (float) max(0, 100 - ($count * self::SCORE_PENALTY_PER_ALERT)),
            confidencePct: 100,
            evidence: ['sample_count' => $count, 'engine_ready' => true],
        );
    }

    private function isAvailable(): bool
    {
        return $this->available ??= Schema::hasTable('esbtp_academic_alerts');
    }

    private function countsForScope(StudentMetricContext $context): array
    {
        $key = implode('|', [$context->academicYearId, $context->classId, $context->period]);
        if (array_key_exists($key, $this->countsByScope)) {
            return $this->countsByScope[$key];
        }

        return $this->countsByScope[$key] = AcademicAlert::query()
            ->selectRaw('etudiant_id, COUNT(*) as alert_count')
            ->whereNotNull('etudiant_id')
            ->where('annee_universitaire_id', $context->academicYearId)
            ->where('classe_id', $context->classId)
            ->where('semester', $context->period)
            ->whereIn('status', AcademicAlertStatus::activeValues())
            ->groupBy('etudiant_id')
            ->pluck('alert_count', 'etudiant_id')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }
}
