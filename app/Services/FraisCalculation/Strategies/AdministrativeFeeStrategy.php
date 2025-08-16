<?php

namespace App\Services\FraisCalculation\Strategies;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisVariant;
use App\Models\ESBTPInscription;

/**
 * Stratégie de calcul pour les frais administratifs (documentation, examens, certificats)
 * Gère les règles spécifiques aux services administratifs ponctuels
 */
class AdministrativeFeeStrategy extends AbstractFraisStrategy
{
    protected string $strategyName = 'Administrative Fee Strategy';

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
        $calculatedAmount = $baseAmount;

        // Règle 1: Ajustement selon le type de document/service administratif
        $documentMultiplier = $this->getDocumentTypeMultiplier($category, $variant);
        if ($documentMultiplier !== 1.0) {
            $calculatedAmount *= $documentMultiplier;
            $this->addMetadata('document_type_adjustment', [
                'category_code' => $category->code,
                'variant' => $variant?->name,
                'multiplier' => $documentMultiplier,
                'adjusted_amount' => $calculatedAmount,
            ]);
        }

        // Règle 2: Frais d'urgence si demandé
        if ($this->isUrgentRequest($additionalData)) {
            $urgencyFee = $this->calculateUrgencyFee($calculatedAmount);
            $calculatedAmount += $urgencyFee;
            $this->addMetadata('urgency_fee', [
                'base_amount' => $calculatedAmount - $urgencyFee,
                'urgency_fee' => $urgencyFee,
                'total_amount' => $calculatedAmount,
            ]);
        }

        // Règle 3: Remise pour demandes groupées
        $bulkDiscount = $this->calculateBulkDiscount($additionalData);
        if ($bulkDiscount > 0) {
            $calculatedAmount *= (1 - $bulkDiscount);
            $this->addMetadata('bulk_discount', [
                'quantity' => $additionalData['quantity'] ?? 1,
                'discount_percentage' => $bulkDiscount * 100,
                'amount_after_discount' => $calculatedAmount,
            ]);
        }

        // Règle 4: Frais de traitement électronique vs papier
        $processingMultiplier = $this->getProcessingTypeMultiplier($additionalData);
        if ($processingMultiplier !== 1.0) {
            $calculatedAmount *= $processingMultiplier;
            $this->addMetadata('processing_type_adjustment', [
                'processing_type' => $additionalData['processing_type'] ?? 'standard',
                'multiplier' => $processingMultiplier,
                'adjusted_amount' => $calculatedAmount,
            ]);
        }

        // Règle 5: Frais de livraison si applicable
        $deliveryFee = $this->calculateDeliveryFee($additionalData);
        if ($deliveryFee > 0) {
            $calculatedAmount += $deliveryFee;
            $this->addMetadata('delivery_fee', [
                'delivery_type' => $additionalData['delivery_type'] ?? 'pickup',
                'delivery_fee' => $deliveryFee,
                'total_with_delivery' => $calculatedAmount,
            ]);
        }

        return $calculatedAmount;
    }

    /**
     * Obtient le multiplicateur selon le type de document
     */
    private function getDocumentTypeMultiplier(ESBTPFraisCategory $category, ?ESBTPFraisVariant $variant): float
    {
        // Multiplicateurs selon le code de la catégorie
        $categoryMultipliers = [
            'DOCUMENTATION' => 1.0,    // Prix standard pour documentation
            'EXAMEN' => 1.2,          // 20% de plus pour les examens
            'CERTIFICAT' => 1.5,      // 50% de plus pour les certificats
            'DIPLOME' => 2.0,         // Double prix pour les diplômes
            'APOSTILLE' => 3.0,       // Triple prix pour apostille
        ];

        $baseMultiplier = $categoryMultipliers[$category->code] ?? 1.0;

        // Multiplicateur supplémentaire selon le variant
        if ($variant) {
            $variantMultipliers = [
                'Standard' => 1.0,
                'Premium' => 1.3,      // Version premium (papier spécial, reliure)
                'International' => 1.8, // Version internationale (traductions)
                'Officiel' => 2.0,     // Version officielle (cachets, signatures)
            ];
            
            $variantMultiplier = $variantMultipliers[$variant->name] ?? 1.0;
            return $baseMultiplier * $variantMultiplier;
        }

        return $baseMultiplier;
    }

    /**
     * Vérifie si c'est une demande urgente
     */
    private function isUrgentRequest(array $additionalData): bool
    {
        return isset($additionalData['urgent']) && $additionalData['urgent'] === true;
    }

    /**
     * Calcule les frais d'urgence
     */
    private function calculateUrgencyFee(float $amount): float
    {
        // 50% de frais supplémentaires pour traitement urgent (dans les 24h)
        return $amount * 0.50;
    }

    /**
     * Calcule la remise pour demandes groupées
     */
    private function calculateBulkDiscount(array $additionalData): float
    {
        $quantity = $additionalData['quantity'] ?? 1;
        
        // Remises par volume
        $discountTiers = [
            5 => 0.05,   // 5% pour 5+ documents
            10 => 0.10,  // 10% pour 10+ documents
            20 => 0.15,  // 15% pour 20+ documents
        ];

        foreach (array_reverse($discountTiers, true) as $minQuantity => $discount) {
            if ($quantity >= $minQuantity) {
                return $discount;
            }
        }

        return 0;
    }

    /**
     * Obtient le multiplicateur selon le type de traitement
     */
    private function getProcessingTypeMultiplier(array $additionalData): float
    {
        $processingType = $additionalData['processing_type'] ?? 'standard';
        
        $multipliers = [
            'electronic' => 0.8,    // 20% de remise pour version électronique
            'standard' => 1.0,      // Prix standard pour version papier
            'premium' => 1.3,       // 30% de plus pour papier premium
            'certified' => 1.6,     // 60% de plus pour version certifiée
        ];
        
        return $multipliers[$processingType] ?? 1.0;
    }

    /**
     * Calcule les frais de livraison
     */
    private function calculateDeliveryFee(array $additionalData): float
    {
        $deliveryType = $additionalData['delivery_type'] ?? 'pickup';
        
        $deliveryFees = [
            'pickup' => 0,          // Retrait sur place: gratuit
            'local' => 2000,        // Livraison locale: 2000 FCFA
            'national' => 5000,     // Livraison nationale: 5000 FCFA
            'international' => 15000, // Livraison internationale: 15000 FCFA
            'express' => 10000,     // Livraison express: 10000 FCFA
        ];
        
        return $deliveryFees[$deliveryType] ?? 0;
    }

    /**
     * Calcule les frais spécifiques aux examens
     */
    private function calculateExaminationFees(float $baseAmount, array $additionalData): float
    {
        $examType = $additionalData['exam_type'] ?? 'regular';
        
        $examMultipliers = [
            'regular' => 1.0,       // Examen normal
            'makeup' => 1.5,        // Examen de rattrapage
            'special' => 2.0,       // Session spéciale
            'international' => 3.0,  // Certification internationale
        ];
        
        return $baseAmount * ($examMultipliers[$examType] ?? 1.0);
    }

    /**
     * Vérifie si l'étudiant a droit à des réductions spéciales
     */
    private function hasSpecialReductions(ESBTPInscription $inscription): bool
    {
        // Vérifier si l'étudiant a des statuts particuliers (boursier, handicap, etc.)
        return $inscription->etudiant->has_special_status ?? false;
    }

    /**
     * {@inheritDoc}
     */
    public function canHandle(ESBTPFraisCategory $category): bool
    {
        return parent::canHandle($category) && $category->category_type === 'administrative';
    }
}