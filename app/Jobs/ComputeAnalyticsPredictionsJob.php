<?php

namespace App\Jobs;

use App\Domain\Analytics\Cache\CachedPredictor;
use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\PredictionPersister;
use App\Domain\Analytics\Predictors\CashFlowPredictor;
use App\Domain\Analytics\Predictors\DefaultRiskPredictor;
use App\Domain\Analytics\Predictors\PredictorInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job quotidien (4h Africa/Abidjan via scheduler) qui calcule toutes les
 * prédictions analytics du tenant courant pour le contexte global, persiste
 * dans analytics_predictions et préchauffe le cache.
 *
 * Idempotent : peut être rerun sans casser. Chaque run produit un nouveau
 * record (audit trail). Cache préchauffé écrase l'ancien.
 */
class ComputeAnalyticsPredictionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;
    public array $backoff = [60, 300, 600];

    public function __construct()
    {
        $this->onQueue('low');
    }

    public function handle(
        CashFlowPredictor $cashFlow,
        DefaultRiskPredictor $defaultRisk,
        PredictionPersister $persister,
    ): void {
        $context = AnalyticsContext::empty();

        $predictors = [
            new CachedPredictor($cashFlow, ttlSeconds: 3600),
            new CachedPredictor($defaultRisk, ttlSeconds: 3600),
        ];

        foreach ($predictors as $predictor) {
            $this->runOne($predictor, $context, $persister);
        }
    }

    private function runOne(PredictorInterface $predictor, AnalyticsContext $context, PredictionPersister $persister): void
    {
        $cached = $predictor instanceof CachedPredictor;
        if ($cached) {
            $predictor->forget($context);
        }

        try {
            $result = $predictor->predict($context);
        } catch (\Throwable $e) {
            Log::error('Analytics predict() failed', [
                'predictor' => $predictor->name(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return;
        }

        if (!$result->isAvailable()) {
            Log::info('Analytics prediction unavailable, skipping persistence', [
                'predictor' => $predictor->name(),
                'reason' => $result->explanation[0] ?? 'unknown',
            ]);
            return;
        }

        try {
            $persister->persist($result, $context);
        } catch (\Throwable $e) {
            Log::error('Analytics persist failed — invalidating cache to keep DB and cache consistent', [
                'predictor' => $predictor->name(),
                'error' => $e->getMessage(),
            ]);
            if ($cached) {
                $predictor->forget($context);
            }
            return;
        }

        Log::info('Analytics prediction computed', [
            'predictor' => $predictor->name(),
            'value' => $result->value,
            'confidence' => $result->confidenceLabel,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ComputeAnalyticsPredictionsJob failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
