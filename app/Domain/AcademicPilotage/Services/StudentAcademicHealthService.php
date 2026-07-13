<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Contracts\AcademicSystemMetricsProvider;
use App\Domain\AcademicPilotage\DTO\AcademicHealthResult;
use App\Domain\AcademicPilotage\DTO\AcademicMetricSet;
use App\Domain\AcademicPilotage\DTO\AcademicMetricValue;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use InvalidArgumentException;

final class StudentAcademicHealthService
{
    /** @var array<string, int|float> */
    private array $weights;

    public function __construct(
        private readonly ?AcademicSystemMetricsProvider $provider = null,
        ?array $weights = null,
        private readonly ?int $minimumCoveragePct = null,
    ) {
        $this->weights = $weights ?? (array) config('academic_pilotage.student_health.weights', []);
        $this->validateConfiguration();
    }

    public function evaluate(StudentMetricContext $context): AcademicHealthResult
    {
        if ($this->provider === null) {
            throw new InvalidArgumentException('Aucun fournisseur de métriques académiques n’est configuré.');
        }

        return $this->calculate($this->provider->metricsFor($context));
    }

    public function calculate(AcademicMetricSet $metrics): AcademicHealthResult
    {
        $totalWeight = array_sum($this->weights);
        $availableWeight = 0.0;
        $weightedScore = 0.0;
        $weightedConfidence = 0.0;
        $factors = [];

        foreach ($this->weights as $key => $weight) {
            $metric = $metrics->get($key);
            $available = $metric?->isAvailable() ?? false;

            if ($available) {
                $effectiveWeight = $weight * ($metric->effectiveCoveragePct() / 100);
                $availableWeight += $effectiveWeight;
                $weightedScore += $metric->value * $effectiveWeight;
                $weightedConfidence += $metric->confidencePct * $effectiveWeight;
            }

            $factors[$key] = $this->factorPayload($metric, (float) $weight, $available);
        }

        $coverage = (int) round(($availableWeight / $totalWeight) * 100);
        $confidence = (int) round($weightedConfidence / $totalWeight);
        $minimumCoverage = $this->minimumCoveragePct
            ?? (int) config('academic_pilotage.student_health.minimum_coverage_pct', 60);

        if ($coverage < $minimumCoverage) {
            return new AcademicHealthResult(
                score: null,
                coveragePct: $coverage,
                confidencePct: $confidence,
                level: 'insufficient_data',
                metrics: $metrics,
                factors: $factors,
                explanations: [
                    "Données insuffisantes pour calculer un score fiable : couverture de {$coverage} %, minimum requis {$minimumCoverage} %.",
                ],
            );
        }

        $score = round($weightedScore / $availableWeight, 2);
        $level = $this->resolveLevel($score);

        return new AcademicHealthResult(
            score: $score,
            coveragePct: $coverage,
            confidencePct: $confidence,
            level: $level,
            metrics: $metrics,
            factors: $factors,
            explanations: $this->explanationsFor($level, $coverage, $confidence),
        );
    }

    private function factorPayload(?AcademicMetricValue $metric, float $weight, bool $available): array
    {
        return [
            'weight' => $weight,
            'available' => $available,
            'value' => $metric?->value,
            'coverage_pct' => $metric?->effectiveCoveragePct() ?? 0,
            'confidence_pct' => $metric?->confidencePct ?? 0,
        ];
    }

    private function resolveLevel(float $score): string
    {
        $levels = (array) config('academic_pilotage.student_health.levels', []);

        if ($score >= (float) ($levels['healthy'] ?? 80)) {
            return 'healthy';
        }

        if ($score >= (float) ($levels['watch'] ?? 65)) {
            return 'watch';
        }

        if ($score >= (float) ($levels['at_risk'] ?? 50)) {
            return 'at_risk';
        }

        return 'critical';
    }

    private function explanationsFor(string $level, int $coverage, int $confidence): array
    {
        $summary = match ($level) {
            'healthy' => 'La situation académique est satisfaisante.',
            'watch' => 'La situation académique nécessite une surveillance régulière.',
            'at_risk' => 'La situation académique est fragile et justifie un accompagnement.',
            default => 'La situation académique est préoccupante et nécessite une intervention.',
        };

        return [
            $summary,
            "Le calcul repose sur {$coverage} % des facteurs attendus, avec une confiance globale de {$confidence} %.",
        ];
    }

    private function validateConfiguration(): void
    {
        if ($this->weights === []) {
            throw new InvalidArgumentException('Les poids des facteurs académiques sont obligatoires.');
        }

        foreach ($this->weights as $key => $weight) {
            if (! is_string($key) || ! is_numeric($weight) || ! is_finite((float) $weight) || $weight <= 0) {
                throw new InvalidArgumentException('Chaque facteur académique doit avoir un poids strictement positif.');
            }
        }

        if (abs(array_sum($this->weights) - 100) > 0.00001) {
            throw new InvalidArgumentException('La somme des poids des facteurs académiques doit être égale à 100.');
        }

        $minimumCoverage = $this->minimumCoveragePct
            ?? (int) config('academic_pilotage.student_health.minimum_coverage_pct', 60);

        if ($minimumCoverage < 0 || $minimumCoverage > 100) {
            throw new InvalidArgumentException('La couverture minimale doit être comprise entre 0 et 100.');
        }
    }
}
