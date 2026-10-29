<?php

namespace App\Domain\Analytics\Predictors;

use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\DTOs\PredictionResult;

/**
 * Contrat d'un Predictor analytics. Implémentations branchables via Strategy
 * pattern dans AnalyticsEngine.
 *
 * Sites de mise en cache et de persistance dans analytics_predictions sont
 * gérés par AnalyticsEngine, pas ici. Predictors restent pure compute.
 */
interface PredictorInterface
{
    /**
     * Identifiant stable utilisé en clé cache et colonne `predictor` table.
     * Snake-case canonique : 'cash_flow', 'default_risk', 'anomaly'.
     */
    public function name(): string;

    /**
     * Calcule la prédiction pour un contexte donné.
     * Doit être déterministe pour des données d'entrée identiques.
     */
    public function predict(AnalyticsContext $context): PredictionResult;

    /**
     * Données minimales requises pour une prédiction utile (en mois).
     * Si l'historique disponible est inférieur, predict() doit retourner
     * PredictionResult::unavailable(...).
     */
    public function minimumHistoryMonths(): int;
}
