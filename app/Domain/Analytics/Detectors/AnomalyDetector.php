<?php

namespace App\Domain\Analytics\Detectors;

use App\Domain\Analytics\Algorithms\Statistics;
use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\DTOs\AnomalyAlert;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPPaiement;
use Carbon\Carbon;

/**
 * Détecte des anomalies dans les flux financiers via Z-score sur séries
 * historiques. Trois types : revenue_drop (mois sous-performant),
 * revenue_spike (mois exceptionnel), payment_outlier (paiement individuel
 * démesuré). Seuils Z configurables via Settings (B4).
 */
class AnomalyDetector
{
    private const NAME = 'anomaly';
    private const MIN_HISTORY_MONTHS = 6;
    private const HISTORY_LOOKBACK = 24;
    private const PAYMENT_OUTLIER_LOOKBACK_DAYS = 30;
    private const PAYMENT_OUTLIER_MIN_SAMPLE = 20;

    public const DEFAULT_Z_WARNING = 2.0;
    public const DEFAULT_Z_CRITICAL = 3.0;
    public const DEFAULT_PAYMENT_OUTLIER_MULTIPLIER = 3.0;

    public function __construct(
        private readonly AnalyticsRepository $repository,
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    /**
     * @return array<int, AnomalyAlert>
     */
    public function detect(AnalyticsContext $context): array
    {
        $alerts = [];
        $alerts = array_merge($alerts, $this->detectMonthlyRevenueAnomalies($context));
        $alerts = array_merge($alerts, $this->detectPaymentOutliers($context));

        usort($alerts, fn (AnomalyAlert $a, AnomalyAlert $b) => $this->severityRank($b->severity) <=> $this->severityRank($a->severity));

        return $alerts;
    }

    /**
     * @return array<int, AnomalyAlert>
     */
    private function detectMonthlyRevenueAnomalies(AnalyticsContext $context): array
    {
        $history = $this->repository->monthlyRevenue($context, self::HISTORY_LOOKBACK);
        if (count($history) < self::MIN_HISTORY_MONTHS) {
            return [];
        }

        $values = array_column($history, 'value');
        $zWarning = $this->configFloat('z_warning', self::DEFAULT_Z_WARNING);
        $zCritical = $this->configFloat('z_critical', self::DEFAULT_Z_CRITICAL);

        $alerts = [];
        $now = Carbon::now();

        foreach ($history as $point) {
            $monthDate = Carbon::create($point['year'], $point['month'], 1);
            if ($monthDate->diffInMonths($now) > 6) {
                continue;
            }

            $z = Statistics::zScore((float) $point['value'], $values);
            $absZ = abs($z);

            if ($absZ < $zWarning) {
                continue;
            }

            $severity = $absZ >= $zCritical ? AnomalyAlert::SEVERITY_CRITICAL : AnomalyAlert::SEVERITY_WARNING;
            $type = $z > 0 ? 'revenue_spike' : 'revenue_drop';

            $alerts[] = new AnomalyAlert(
                type: $type,
                severity: $severity,
                entityType: 'period',
                entityId: $point['year'] * 100 + $point['month'],
                score: $absZ,
                message: $this->buildRevenueMessage($type, $monthDate, $point['value'], Statistics::mean($values), $absZ),
                context: [
                    'year' => $point['year'],
                    'month' => $point['month'],
                    'value' => $point['value'],
                    'z_score' => $z,
                ],
            );
        }

        return $alerts;
    }

    /**
     * @return array<int, AnomalyAlert>
     */
    private function detectPaymentOutliers(AnalyticsContext $context): array
    {
        $multiplier = $this->configFloat('payment_outlier_multiplier', self::DEFAULT_PAYMENT_OUTLIER_MULTIPLIER);

        $recentPayments = ESBTPPaiement::query()
            ->where('status', 'validé')
            ->whereNull('deleted_at')
            ->where('date_paiement', '>=', Carbon::now()->subDays(self::PAYMENT_OUTLIER_LOOKBACK_DAYS))
            ->when($context->anneeId, fn ($q) => $q->where('annee_universitaire_id', $context->anneeId))
            ->when($context->classeId, fn ($q) => $q->whereHas('inscription', fn ($q2) => $q2->where('classe_id', $context->classeId)))
            ->when($context->filiereId, fn ($q) => $q->whereHas('inscription.classe', fn ($q2) => $q2->where('filiere_id', $context->filiereId)))
            ->select('id', 'inscription_id', 'montant', 'date_paiement', 'numero_recu')
            ->get();

        if ($recentPayments->count() < self::PAYMENT_OUTLIER_MIN_SAMPLE) {
            return [];
        }

        $amounts = $recentPayments->pluck('montant')->map(fn ($v) => (float) $v)->all();
        $mean = Statistics::mean($amounts);
        if ($mean <= 0.0) {
            return [];
        }

        $threshold = $mean * $multiplier;
        $alerts = [];

        foreach ($recentPayments as $payment) {
            if ((float) $payment->montant < $threshold) {
                continue;
            }

            $ratio = (float) $payment->montant / $mean;
            $severity = $ratio >= ($multiplier + 2.0)
                ? AnomalyAlert::SEVERITY_CRITICAL
                : AnomalyAlert::SEVERITY_WARNING;

            $alerts[] = new AnomalyAlert(
                type: 'payment_outlier',
                severity: $severity,
                entityType: 'paiement',
                entityId: (int) $payment->id,
                score: $ratio,
                message: sprintf(
                    'Paiement #%s exceptionnellement élevé : %s FCFA (%.1f× la moyenne mensuelle de %s FCFA)',
                    $payment->numero_recu ?: $payment->id,
                    number_format((float) $payment->montant, 0, ',', ' '),
                    $ratio,
                    number_format($mean, 0, ',', ' '),
                ),
                context: [
                    'paiement_id' => $payment->id,
                    'inscription_id' => $payment->inscription_id,
                    'montant' => (float) $payment->montant,
                    'date_paiement' => $payment->date_paiement?->toDateString(),
                    'mean' => $mean,
                    'ratio' => $ratio,
                ],
            );
        }

        return $alerts;
    }

    private function buildRevenueMessage(string $type, Carbon $monthDate, float $value, float $mean, float $absZ): string
    {
        $monthLabel = ucfirst($monthDate->translatedFormat('F Y'));
        $direction = $type === 'revenue_spike' ? 'au-dessus' : 'en dessous';
        $delta = abs($value - $mean);

        return sprintf(
            '%s : %s FCFA, soit %s FCFA %s de la moyenne (Z=%.1f)',
            $monthLabel,
            number_format($value, 0, ',', ' '),
            number_format($delta, 0, ',', ' '),
            $direction,
            $absZ,
        );
    }

    private function severityRank(string $severity): int
    {
        return match ($severity) {
            AnomalyAlert::SEVERITY_CRITICAL => 3,
            AnomalyAlert::SEVERITY_WARNING => 2,
            AnomalyAlert::SEVERITY_INFO => 1,
            default => 0,
        };
    }

    private function configFloat(string $key, float $default): float
    {
        return (float) SettingsHelper::get("analytics.anomaly.{$key}", $default);
    }
}
