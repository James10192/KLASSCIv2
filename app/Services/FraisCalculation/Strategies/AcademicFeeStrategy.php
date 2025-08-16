<?php

namespace App\Services\FraisCalculation\Strategies;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisVariant;
use App\Models\ESBTPInscription;

/**
 * Stratégie de calcul pour les frais académiques (inscription, scolarité)
 * Gère les règles spécifiques aux frais obligatoires de formation
 */
class AcademicFeeStrategy extends AbstractFraisStrategy
{
    protected string $strategyName = 'Academic Fee Strategy';

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
        // Calcul de base pour les frais académiques
        $calculatedAmount = $baseAmount;

        // Règle 1: Ajustement selon le niveau d'étude
        $levelMultiplier = $this->getLevelMultiplier($inscription);
        if ($levelMultiplier !== 1.0) {
            $calculatedAmount *= $levelMultiplier;
            $this->addMetadata('level_adjustment', [
                'level' => $inscription->niveauEtude->name ?? 'Unknown',
                'multiplier' => $levelMultiplier,
                'adjusted_amount' => $calculatedAmount,
            ]);
        }

        // Règle 2: Ajustement selon la filière
        $fieldMultiplier = $this->getFieldMultiplier($inscription);
        if ($fieldMultiplier !== 1.0) {
            $calculatedAmount *= $fieldMultiplier;
            $this->addMetadata('field_adjustment', [
                'field' => $inscription->filiere->name ?? 'Unknown',
                'multiplier' => $fieldMultiplier,
                'adjusted_amount' => $calculatedAmount,
            ]);
        }

        // Règle 3: Frais d'inscription tardive
        if ($this->isLateRegistration($inscription)) {
            $lateFee = $this->calculateLateRegistrationFee($calculatedAmount);
            $calculatedAmount += $lateFee;
            $this->addMetadata('late_registration_fee', [
                'base_amount' => $calculatedAmount - $lateFee,
                'late_fee' => $lateFee,
                'total_amount' => $calculatedAmount,
            ]);
        }

        // Règle 4: Réduction pour paiement anticipé
        if ($this->isEarlyPayment($additionalData)) {
            $earlyDiscount = $this->calculateEarlyPaymentDiscount($calculatedAmount);
            $calculatedAmount -= $earlyDiscount;
            $this->addMetadata('early_payment_discount', [
                'original_amount' => $calculatedAmount + $earlyDiscount,
                'discount' => $earlyDiscount,
                'final_amount' => $calculatedAmount,
            ]);
        }

        return $calculatedAmount;
    }

    /**
     * Obtient le multiplicateur selon le niveau d'étude
     */
    private function getLevelMultiplier(ESBTPInscription $inscription): float
    {
        $levelMultipliers = [
            'L1' => 1.0,
            'L2' => 1.1,
            'L3' => 1.2,
            'M1' => 1.4,
            'M2' => 1.5,
        ];

        $levelCode = $inscription->niveauEtude->code ?? 'L1';
        return $levelMultipliers[$levelCode] ?? 1.0;
    }

    /**
     * Obtient le multiplicateur selon la filière
     */
    private function getFieldMultiplier(ESBTPInscription $inscription): float
    {
        // Certaines filières ont des coûts différents (ex: ingénierie vs littéraire)
        $fieldMultipliers = [
            'BTP' => 1.2,      // Bâtiment et Travaux Publics - matériaux coûteux
            'INFO' => 1.1,     // Informatique - équipements technologiques
            'GESTION' => 1.0,  // Gestion - coût standard
            'COMMERCE' => 1.0, // Commerce - coût standard
        ];

        $fieldCode = $inscription->filiere->code ?? 'GESTION';
        return $fieldMultipliers[$fieldCode] ?? 1.0;
    }

    /**
     * Vérifie si c'est une inscription tardive
     */
    private function isLateRegistration(ESBTPInscription $inscription): bool
    {
        // Considérer comme tardive si l'inscription est faite après le 30 septembre
        $lateRegistrationDate = $inscription->anneeUniversitaire->date_debut->addDays(30);
        return $inscription->created_at->gt($lateRegistrationDate);
    }

    /**
     * Calcule les frais d'inscription tardive
     */
    private function calculateLateRegistrationFee(float $amount): float
    {
        // 10% de frais supplémentaires pour inscription tardive
        return $amount * 0.10;
    }

    /**
     * Vérifie si c'est un paiement anticipé
     */
    private function isEarlyPayment(array $additionalData): bool
    {
        return isset($additionalData['early_payment']) && $additionalData['early_payment'] === true;
    }

    /**
     * Calcule la remise pour paiement anticipé
     */
    private function calculateEarlyPaymentDiscount(float $amount): float
    {
        // 5% de remise pour paiement anticipé (avant le 15 août)
        return $amount * 0.05;
    }

    /**
     * {@inheritDoc}
     */
    public function canHandle(ESBTPFraisCategory $category): bool
    {
        return parent::canHandle($category) && $category->category_type === 'academic';
    }
}