<?php

namespace App\Domain\Analytics\Predictors;

use App\Domain\Analytics\Algorithms\ExponentialSmoothing;
use App\Domain\Analytics\Algorithms\LinearRegression;
use App\Domain\Analytics\Algorithms\Statistics;
use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\DTOs\ConfidenceInterval;
use App\Domain\Analytics\DTOs\PredictionResult;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Services\Analytics\CashFlowProjectionService;
use Carbon\Carbon;

/**
 * Prédit l'encaissement du prochain mois via saisonnalité scolaire +
 * régression linéaire. Confidence interval 95%. Top 3 raisons textuelles
 * compréhensibles par un comptable lambda.
 */
class CashFlowPredictor implements PredictorInterface
{
    private const NAME = 'cash_flow';
    private const MIN_HISTORY_MONTHS = 6;
    private const HISTORY_LOOKBACK = 24;
    private const Z_SCORE_95 = 1.96;
    private const TENDANCE_THRESHOLD = 1000.0;

    public function __construct(
        private readonly AnalyticsRepository $repository,
        private readonly CashFlowProjectionService $projectionService,
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function minimumHistoryMonths(): int
    {
        return self::MIN_HISTORY_MONTHS;
    }

    public function predict(AnalyticsContext $context): PredictionResult
    {
        $history = $this->repository->monthlyRevenue($context, self::HISTORY_LOOKBACK);
        $scheduledNextMonth = $this->projectionService->nextMonthRevenue($context);

        if (count($history) < self::MIN_HISTORY_MONTHS && $scheduledNextMonth <= 0) {
            return PredictionResult::unavailable(
                self::NAME,
                sprintf(
                    'Historique insuffisant pour une prévision fiable (%d mois disponibles, %d requis minimum)',
                    count($history),
                    self::MIN_HISTORY_MONTHS,
                ),
            );
        }

        $values = array_column($history, 'value');
        $nextMonth = Carbon::now()->addMonth();
        $targetMonth = (int) $nextMonth->month;

        $seasonalSeries = array_map(fn ($p) => ['month' => $p['month'], 'value' => $p['value']], $history);
        $historicalForecast = count($history) >= self::MIN_HISTORY_MONTHS
            ? ExponentialSmoothing::forecastSeasonal($seasonalSeries, $targetMonth)
            : 0.0;

        $forecast = $scheduledNextMonth > 0
            ? ($historicalForecast > 0 ? round(($scheduledNextMonth * 0.8) + ($historicalForecast * 0.2), 2) : $scheduledNextMonth)
            : $historicalForecast;

        $tendance = count($values) >= 2 ? LinearRegression::fit($values)['slope'] : 0.0;
        $stdDev = count($values) >= 2 ? Statistics::standardDeviation($values) : 0.0;

        $ci = count($history) >= self::MIN_HISTORY_MONTHS
            ? new ConfidenceInterval(
                lower: max(0.0, $forecast - self::Z_SCORE_95 * $stdDev),
                upper: $forecast + self::Z_SCORE_95 * $stdDev,
                percentile: 95,
            )
            : null;

        return new PredictionResult(
            predictor: self::NAME,
            value: $forecast,
            label: 'forecast',
            confidenceInterval: $ci,
            confidenceLabel: $ci?->labelForValue($forecast) ?? 'indicatif',
            explanation: $this->buildExplanation($history, $tendance, $targetMonth, $scheduledNextMonth),
            targetDate: $nextMonth->startOfMonth(),
            metadata: [
                'scheduled_revenue_next_month' => $scheduledNextMonth,
                'history_months' => count($history),
            ],
        );
    }

    /**
     * @return array<int, string>
     */
    private function buildExplanation(array $history, float $tendance, int $targetMonth, float $scheduledRevenue): array
    {
        $reasons = [];
        $monthName = Carbon::create(null, $targetMonth, 1)->translatedFormat('F');

        $reasons[] = sprintf('Basé sur %d mois d\'historique de paiements validés', count($history));

        if ($scheduledRevenue > 0) {
            $reasons[] = sprintf(
                'Échéanciers actifs pris en compte pour le mois cible : %s FCFA',
                number_format($scheduledRevenue, 0, ',', ' ')
            );
        }

        $sameMonthValues = array_column(
            array_filter($history, fn ($p) => $p['month'] === $targetMonth),
            'value'
        );
        if (!empty($sameMonthValues)) {
            $reasons[] = sprintf(
                '%s rapporte historiquement %s FCFA en moyenne (sur %d années)',
                ucfirst($monthName),
                number_format(Statistics::mean($sameMonthValues), 0, ',', ' '),
                count($sameMonthValues),
            );
        }

        if ($tendance > self::TENDANCE_THRESHOLD) {
            $reasons[] = 'Tendance globale en hausse — encaissements en croissance';
        } elseif ($tendance < -self::TENDANCE_THRESHOLD) {
            $reasons[] = 'Tendance globale en baisse — vigilance recommandée';
        } else {
            $reasons[] = 'Tendance globale stable sur la période';
        }

        return $reasons;
    }
}
