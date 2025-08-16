<?php

namespace App\Services\FraisCalculation\Contracts;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisVariant;
use App\Models\ESBTPInscription;

/**
 * Interface pour les stratégies de calcul de frais
 * Suit le Strategy Pattern pour permettre différents algorithmes de calcul
 */
interface FraisCalculationStrategyInterface
{
    /**
     * Calcule le montant des frais selon la stratégie spécifique
     *
     * @param float $baseAmount Montant de base configuré
     * @param ESBTPInscription $inscription Inscription de l'étudiant
     * @param ESBTPFraisCategory $category Catégorie de frais
     * @param ESBTPFraisVariant|null $variant Variant sélectionné (optionnel)
     * @param array $additionalData Données supplémentaires pour le calcul
     * @return float Montant calculé
     */
    public function calculate(
        float $baseAmount,
        ESBTPInscription $inscription,
        ESBTPFraisCategory $category,
        ?ESBTPFraisVariant $variant = null,
        array $additionalData = []
    ): float;

    /**
     * Valide si cette stratégie peut traiter la catégorie donnée
     *
     * @param ESBTPFraisCategory $category
     * @return bool
     */
    public function canHandle(ESBTPFraisCategory $category): bool;

    /**
     * Obtient les métadonnées de calcul pour la transparence
     *
     * @return array
     */
    public function getCalculationMetadata(): array;

    /**
     * Obtient le nom de la stratégie
     *
     * @return string
     */
    public function getName(): string;
}