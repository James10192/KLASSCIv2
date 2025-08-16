<?php

namespace App\Services\FraisCalculation\Strategies;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisVariant;
use App\Models\ESBTPInscription;

/**
 * Stratégie de calcul pour les frais de services (cantine, transport, etc.)
 * Gère les règles spécifiques aux services optionnels avec variants
 */
class ServiceFeeStrategy extends AbstractFraisStrategy
{
    protected string $strategyName = 'Service Fee Strategy';

    /**
     * {@inheritDoc}
     */
    protected function performCalculation(
        float $baseAmount,
        ESBTPInscription $inscription,
        ESBTPFraisCategory $category,
        ?ESBTPFraisVariant $variant,
        array $additionalData
    ): float {
        // Pour les services, le calcul dépend largement du variant choisi
        if (!$variant) {
            // Pas de variant = pas de service souscrit = montant 0
            $this->addMetadata('no_variant_selected', 'Service not subscribed');
            return 0;
        }

        $calculatedAmount = $this->calculateServiceSpecificAmount($baseAmount, $variant, $additionalData);

        // Règle 1: Calcul selon la fréquence d'utilisation
        $frequencyMultiplier = $this->getFrequencyMultiplier($variant, $additionalData);
        if ($frequencyMultiplier !== 1.0) {
            $calculatedAmount *= $frequencyMultiplier;
            $this->addMetadata('frequency_adjustment', [
                'frequency' => $additionalData['frequency'] ?? 'monthly',
                'multiplier' => $frequencyMultiplier,
                'adjusted_amount' => $calculatedAmount,
            ]);
        }

        // Règle 2: Remise pour souscription multiple
        $multiServiceDiscount = $this->calculateMultiServiceDiscount($inscription, $category);
        if ($multiServiceDiscount > 0) {
            $calculatedAmount *= (1 - $multiServiceDiscount);
            $this->addMetadata('multi_service_discount', [
                'discount_percentage' => $multiServiceDiscount * 100,
                'amount_after_discount' => $calculatedAmount,
            ]);
        }

        // Règle 3: Ajustement saisonnier
        $seasonalAdjustment = $this->getSeasonalAdjustment($category);
        if ($seasonalAdjustment !== 1.0) {
            $calculatedAmount *= $seasonalAdjustment;
            $this->addMetadata('seasonal_adjustment', [
                'season' => $this->getCurrentSeason(),
                'multiplier' => $seasonalAdjustment,
                'adjusted_amount' => $calculatedAmount,
            ]);
        }

        return $calculatedAmount;
    }

    /**
     * Calcule le montant spécifique au service selon le variant
     */
    private function calculateServiceSpecificAmount(float $baseAmount, ESBTPFraisVariant $variant, array $additionalData): float
    {
        // Le variant définit le prix spécifique du service
        $serviceAmount = $variant->amount;

        // Pour certains services comme le transport, le prix peut dépendre de la distance
        if ($variant->fraisCategory->code === 'TRANSPORT') {
            $serviceAmount = $this->calculateTransportAmount($variant, $additionalData);
        }
        
        // Pour la cantine, le prix peut dépendre du forfait choisi
        elseif ($variant->fraisCategory->code === 'CANTINE') {
            $serviceAmount = $this->calculateCantineAmount($variant, $additionalData);
        }

        $this->addMetadata('service_specific_calculation', [
            'service_type' => $variant->fraisCategory->code,
            'variant_name' => $variant->name,
            'base_variant_amount' => $variant->amount,
            'calculated_service_amount' => $serviceAmount,
        ]);

        return $serviceAmount;
    }

    /**
     * Calcule le montant du transport selon la distance/zone
     */
    private function calculateTransportAmount(ESBTPFraisVariant $variant, array $additionalData): float
    {
        // Le variant peut contenir des données sur la zone ou l'arrêt
        $zoneMultiplier = 1.0;
        
        if ($variant->hasAdditionalData('zone_multiplier')) {
            $zoneMultiplier = $variant->getAdditionalData('zone_multiplier', 1.0);
        }
        
        // Distance supplémentaire si spécifiée
        $extraDistance = $additionalData['extra_distance'] ?? 0;
        $extraCost = $extraDistance * 0.5; // 500 FCFA par km supplémentaire
        
        return ($variant->amount * $zoneMultiplier) + $extraCost;
    }

    /**
     * Calcule le montant de la cantine selon le forfait
     */
    private function calculateCantineAmount(ESBTPFraisVariant $variant, array $additionalData): float
    {
        $baseAmount = $variant->amount;
        
        // Forfait spécial (ex: végétarien, sans gluten)
        if (isset($additionalData['special_diet']) && $additionalData['special_diet']) {
            $baseAmount *= 1.15; // 15% de supplément pour régime spécial
        }
        
        // Nombre de repas par semaine
        $mealsPerWeek = $additionalData['meals_per_week'] ?? 5;
        $mealMultiplier = $mealsPerWeek / 5; // Base: 5 repas par semaine
        
        return $baseAmount * $mealMultiplier;
    }

    /**
     * Obtient le multiplicateur selon la fréquence
     */
    private function getFrequencyMultiplier(ESBTPFraisVariant $variant, array $additionalData): float
    {
        $frequency = $additionalData['frequency'] ?? 'monthly';
        
        $multipliers = [
            'daily' => 30,      // Paiement quotidien
            'weekly' => 4.33,   // Paiement hebdomadaire (approximativement 4.33 semaines par mois)
            'monthly' => 1,     // Paiement mensuel (base)
            'quarterly' => 0.95, // Remise 5% pour paiement trimestriel
            'annual' => 0.85,   // Remise 15% pour paiement annuel
        ];
        
        return $multipliers[$frequency] ?? 1.0;
    }

    /**
     * Calcule la remise pour souscription à plusieurs services
     */
    private function calculateMultiServiceDiscount(ESBTPInscription $inscription, ESBTPFraisCategory $currentCategory): float
    {
        // Compter le nombre de services auxquels l'étudiant est souscrit
        $subscribedServices = collect(); // Placeholder - à implémenter selon le modèle de souscriptions
        
        $serviceCount = $subscribedServices->count();
        
        // Remise progressive
        $discountRates = [
            2 => 0.05, // 5% pour 2 services
            3 => 0.10, // 10% pour 3 services ou plus
        ];
        
        foreach ($discountRates as $minServices => $discount) {
            if ($serviceCount >= $minServices) {
                return $discount;
            }
        }
        
        return 0;
    }

    /**
     * Obtient l'ajustement saisonnier
     */
    private function getSeasonalAdjustment(ESBTPFraisCategory $category): float
    {
        // Certains services peuvent avoir des variations saisonnières
        if ($category->code === 'TRANSPORT') {
            $season = $this->getCurrentSeason();
            
            // Prix réduit pendant les vacances d'été
            if ($season === 'summer') {
                return 0.7; // 30% de réduction en été
            }
        }
        
        return 1.0;
    }

    /**
     * Obtient la saison actuelle
     */
    private function getCurrentSeason(): string
    {
        $month = now()->month;
        
        if (in_array($month, [6, 7, 8, 9])) {
            return 'summer'; // Juin à septembre (vacances d'été)
        } elseif (in_array($month, [12, 1, 2])) {
            return 'winter'; // Décembre à février (vacances d'hiver)
        }
        
        return 'regular'; // Période scolaire normale
    }

    /**
     * {@inheritDoc}
     */
    public function canHandle(ESBTPFraisCategory $category): bool
    {
        return parent::canHandle($category) && $category->category_type === 'service';
    }
}