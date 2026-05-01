<?php

namespace App\Domain\Analytics;

use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\DTOs\PredictionResult;
use App\Models\AnalyticsPrediction;

/**
 * Persiste les PredictionResult dans la table analytics_predictions pour
 * audit trail et calcul d'accuracy a posteriori (actual_value vs predicted).
 */
class PredictionPersister
{
    public function persist(PredictionResult $result, AnalyticsContext $context): AnalyticsPrediction
    {
        $record = AnalyticsPrediction::fromResult($result, $context);
        $record->save();
        return $record;
    }

    /**
     * Récupère la dernière prédiction pour un (predictor, context). Retourne
     * null si jamais persistée.
     */
    public function latest(string $predictor, AnalyticsContext $context): ?AnalyticsPrediction
    {
        return AnalyticsPrediction::query()
            ->where('predictor', $predictor)
            ->where('context_hash', $context->hash())
            ->orderByDesc('computed_at')
            ->first();
    }
}
