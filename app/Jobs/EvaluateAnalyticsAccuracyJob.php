<?php

namespace App\Jobs;

use App\Domain\Analytics\AccuracyEvaluator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job mensuel (1er du mois, 5h Africa/Abidjan) qui évalue rétrospectivement
 * la précision des prédictions cash flow du mois écoulé. Met à jour
 * actual_value et accuracy_score dans analytics_predictions.
 *
 * Le widget Précision sur /analytics agrège ensuite ces scores en label
 * Excellente/Bonne/À surveiller.
 */
class EvaluateAnalyticsAccuracyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public array $backoff = [60, 180, 300];

    public function __construct()
    {
        $this->onQueue('low');
    }

    public function handle(AccuracyEvaluator $evaluator): void
    {
        try {
            $result = $evaluator->evaluatePendingCashFlow();
            Log::info('Analytics accuracy evaluation completed', $result);
        } catch (\Throwable $e) {
            Log::error('Analytics accuracy evaluation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EvaluateAnalyticsAccuracyJob failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
