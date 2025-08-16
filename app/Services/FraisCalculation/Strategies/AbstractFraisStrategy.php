<?php

namespace App\Services\FraisCalculation\Strategies;

use App\Services\FraisCalculation\Contracts\FraisCalculationStrategyInterface;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisVariant;
use App\Models\ESBTPInscription;
use Illuminate\Support\Facades\Log;

/**
 * Classe abstraite pour les stratégies de calcul de frais
 * Fournit des méthodes communes et une structure de base
 */
abstract class AbstractFraisStrategy implements FraisCalculationStrategyInterface
{
    protected array $metadata = [];
    protected string $strategyName = 'Unknown';

    /**
     * Méthode template pour le calcul des frais
     */
    public function calculate(
        float $baseAmount,
        ESBTPInscription $inscription,
        ESBTPFraisCategory $category,
        ?ESBTPFraisVariant $variant = null,
        array $additionalData = []
    ): float {
        $this->initializeMetadata();
        
        // Log du début du calcul
        $this->logCalculationStart($baseAmount, $inscription, $category, $variant);
        
        // Validation des prérequis
        $this->validateCalculationPrerequisites($baseAmount, $inscription, $category);
        
        // Calcul spécifique à la stratégie
        $calculatedAmount = $this->performCalculation($baseAmount, $inscription, $category, $variant, $additionalData);
        
        // Application des modificateurs de variant
        if ($variant) {
            $calculatedAmount = $this->applyVariantModifications($calculatedAmount, $variant, $additionalData);
        }
        
        // Application des règles métier spécifiques
        $finalAmount = $this->applyBusinessRules($calculatedAmount, $inscription, $category, $additionalData);
        
        // Log du résultat
        $this->logCalculationResult($baseAmount, $finalAmount, $inscription, $category);
        
        return $finalAmount;
    }

    /**
     * Calcul spécifique à implémenter par chaque stratégie
     */
    abstract protected function performCalculation(
        float $baseAmount,
        ESBTPInscription $inscription,
        ESBTPFraisCategory $category,
        ?ESBTPFraisVariant $variant,
        array $additionalData
    ): float;

    /**
     * Applique les modifications de variant
     */
    protected function applyVariantModifications(float $amount, ESBTPFraisVariant $variant, array $additionalData): float
    {
        if (!$variant->is_active) {
            $this->addMetadata('variant_status', 'inactive');
            return $amount;
        }

        $modifiedAmount = $variant->calculatePrice($amount);
        
        $this->addMetadata('variant_applied', [
            'variant_id' => $variant->id,
            'variant_name' => $variant->name,
            'original_amount' => $amount,
            'modified_amount' => $modifiedAmount,
            'modification_type' => $variant->getAdditionalData('price_type', 'fixed'),
        ]);

        return $modifiedAmount;
    }

    /**
     * Applique les règles métier communes
     */
    protected function applyBusinessRules(float $amount, ESBTPInscription $inscription, ESBTPFraisCategory $category, array $additionalData): float
    {
        // Règle 1: Montant minimum
        $minAmount = $this->getMinimumAmount($category);
        if ($amount < $minAmount) {
            $this->addMetadata('minimum_amount_applied', [
                'calculated' => $amount,
                'minimum' => $minAmount,
            ]);
            $amount = $minAmount;
        }

        // Règle 2: Remises pour frères/sœurs
        $siblingDiscount = $this->calculateSiblingDiscount($inscription, $category);
        if ($siblingDiscount > 0) {
            $amount = $amount * (1 - $siblingDiscount);
            $this->addMetadata('sibling_discount', [
                'discount_percentage' => $siblingDiscount * 100,
                'amount_after_discount' => $amount,
            ]);
        }

        // Règle 3: Bourses et réductions
        $scholarshipAmount = $this->calculateScholarshipReduction($inscription, $category, $amount);
        if ($scholarshipAmount > 0) {
            $amount -= $scholarshipAmount;
            $this->addMetadata('scholarship_applied', [
                'scholarship_amount' => $scholarshipAmount,
                'amount_after_scholarship' => $amount,
            ]);
        }

        return max(0, $amount); // Ne jamais retourner un montant négatif
    }

    /**
     * Validation des prérequis de calcul
     */
    protected function validateCalculationPrerequisites(float $baseAmount, ESBTPInscription $inscription, ESBTPFraisCategory $category): void
    {
        if ($baseAmount < 0) {
            throw new \InvalidArgumentException('Le montant de base ne peut pas être négatif');
        }

        if (!$inscription->exists) {
            throw new \InvalidArgumentException('Inscription invalide');
        }

        if (!$category->is_active) {
            throw new \InvalidArgumentException('Catégorie de frais inactive');
        }
    }

    /**
     * Obtient le montant minimum pour une catégorie
     */
    protected function getMinimumAmount(ESBTPFraisCategory $category): float
    {
        return (float) ($category->getAdditionalData('minimum_amount') ?? 0);
    }

    /**
     * Calcule la remise fratrie
     */
    protected function calculateSiblingDiscount(ESBTPInscription $inscription, ESBTPFraisCategory $category): float
    {
        // Compter les frères/sœurs dans la même école
        $siblingsCount = ESBTPInscription::where('annee_universitaire_id', $inscription->annee_universitaire_id)
            ->where('status', 'active')
            ->whereHas('etudiant', function ($query) use ($inscription) {
                $query->where('parent_id', $inscription->etudiant->parent_id)
                      ->where('id', '!=', $inscription->etudiant_id);
            })
            ->count();

        if ($siblingsCount === 0) {
            return 0;
        }

        // Remise progressive selon le nombre de frères/sœurs
        $discountRates = [
            1 => 0.05, // 5% pour 1 frère/sœur
            2 => 0.10, // 10% pour 2 frères/sœurs
            3 => 0.15, // 15% pour 3+ frères/sœurs
        ];

        $siblingKey = min($siblingsCount, 3);
        return $discountRates[$siblingKey] ?? 0;
    }

    /**
     * Calcule la réduction de bourse
     */
    protected function calculateScholarshipReduction(ESBTPInscription $inscription, ESBTPFraisCategory $category, float $amount): float
    {
        // Récupérer les bourses actives pour cet étudiant
        $scholarships = collect(); // Placeholder - à implémenter selon le modèle de bourses
        
        return $scholarships->sum(function ($scholarship) use ($amount, $category) {
            if ($scholarship->applies_to_category($category->id)) {
                return min($amount, $scholarship->getReductionAmount($amount));
            }
            return 0;
        });
    }

    /**
     * Initialise les métadonnées de calcul
     */
    protected function initializeMetadata(): void
    {
        $this->metadata = [
            'strategy' => $this->strategyName,
            'calculation_time' => now()->toISOString(),
            'rules_applied' => [],
        ];
    }

    /**
     * Ajoute des métadonnées
     */
    protected function addMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
    }

    /**
     * Log du début de calcul
     */
    protected function logCalculationStart(float $baseAmount, ESBTPInscription $inscription, ESBTPFraisCategory $category, ?ESBTPFraisVariant $variant): void
    {
        Log::info("Début calcul frais - {$this->strategyName}", [
            'base_amount' => $baseAmount,
            'inscription_id' => $inscription->id,
            'category_id' => $category->id,
            'variant_id' => $variant?->id,
        ]);
    }

    /**
     * Log du résultat de calcul
     */
    protected function logCalculationResult(float $baseAmount, float $finalAmount, ESBTPInscription $inscription, ESBTPFraisCategory $category): void
    {
        Log::info("Fin calcul frais - {$this->strategyName}", [
            'base_amount' => $baseAmount,
            'final_amount' => $finalAmount,
            'inscription_id' => $inscription->id,
            'category_id' => $category->id,
            'metadata' => $this->metadata,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getCalculationMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->strategyName;
    }

    /**
     * {@inheritDoc}
     */
    public function canHandle(ESBTPFraisCategory $category): bool
    {
        return $category->is_active;
    }
}