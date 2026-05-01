<?php

namespace App\Domain\Analytics\Predictors;

use App\Domain\Analytics\Algorithms\ExponentialSmoothing;
use App\Domain\Analytics\Algorithms\LinearRegression;
use App\Domain\Analytics\Algorithms\Statistics;
use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\DTOs\ConfidenceInterval;
use App\Domain\Analytics\DTOs\PredictionResult;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
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

        if (count($history) < self::MIN_HISTORY_MONTHS) {
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
        $forecast = ExponentialSmoothing::forecastSeasonal($seasonalSeries, $targetMonth);

        $tendance = LinearRegression::fit($values)['slope'];
        $stdDev = Statistics::standardDeviation($values);

        $ci = new ConfidenceInterval(
            lower: max(0.0, $forecast - self::Z_SCORE_95 * $stdDev),
            upper: $forecast + self::Z_SCORE_95 * $stdDev,
            percentile: 95,
        );

        return new PredictionResult(
            predictor: self::NAME,
            value: $forecast,
            label: 'forecast',
            confidenceInterval: $ci,
            confidenceLabel: $ci->labelForValue($forecast),
            explanation: $this->buildExplanation($history, $tendance, $targetMonth),
            targetDate: $nextMonth->startOfMonth(),
        );
    }

    /**
     * @return array<int, string>
     */
    private function buildExplanation(array $history, float $tendance, int $targetMonth): array
    {
        $reasons = [];
        $monthName = Carbon::create(null, $targetMonth, 1)->translatedFormat('F');

        $reasons[] = sprintf('Basé sur %d mois d\'historique de paiements validés', count($history));

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
