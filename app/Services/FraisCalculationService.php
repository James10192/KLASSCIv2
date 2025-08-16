<?php

namespace App\Services;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisRule;
use App\Models\ESBTPFraisVariant;
use App\Models\ESBTPInscription;
use App\Services\FraisCalculation\Contracts\FraisCalculationStrategyInterface;
use App\Services\FraisCalculation\Strategies\AcademicFeeStrategy;
use App\Services\FraisCalculation\Strategies\ServiceFeeStrategy;
use App\Services\FraisCalculation\Strategies\AdministrativeFeeStrategy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service centralisé pour le calcul des frais selon les meilleures pratiques DDD
 * Utilise le Strategy Pattern pour gérer différents types de calculs
 */
class FraisCalculationService
{
    private array $strategies = [];
    
    public function __construct()
    {
        $this->initializeStrategies();
    }

    /**
     * Initialise les stratégies de calcul par type de frais
     */
    private function initializeStrategies(): void
    {
        $this->strategies = [
            'academic' => new AcademicFeeStrategy(),
            'service' => new ServiceFeeStrategy(),
            'administrative' => new AdministrativeFeeStrategy(),
        ];
    }

    /**
     * Calcule le montant des frais pour une inscription donnée
     */
    public function calculateFeeForInscription(
        ESBTPInscription $inscription, 
        ESBTPFraisCategory $category, 
        ?ESBTPFraisVariant $variant = null,
        array $additionalData = []
    ): array {
        try {
            // Obtenir la stratégie appropriée
            $strategy = $this->getStrategyForCategory($category);
            
            // Récupérer la configuration applicable
            $configuration = $this->getApplicableConfiguration($inscription, $category);
            
            // Calculer le montant de base
            $baseAmount = $this->calculateBaseAmount($configuration, $category);
            
            // Appliquer la stratégie spécifique
            $calculatedAmount = $strategy->calculate($baseAmount, $inscription, $category, $variant, $additionalData);
            
            // Calculer les frais de retard si applicable
            $lateFee = $this->calculateLateFee($configuration, $calculatedAmount, $inscription);
            
            // Vérifier les échéanciers
            $installmentInfo = $this->getInstallmentInfo($configuration, $calculatedAmount);
            
            return [
                'base_amount' => $baseAmount,
                'calculated_amount' => $calculatedAmount,
                'late_fee' => $lateFee,
                'total_amount' => $calculatedAmount + $lateFee,
                'installment_info' => $installmentInfo,
                'configuration' => $configuration,
                'strategy_used' => get_class($strategy),
                'calculation_date' => Carbon::now(),
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul des frais', [
                'inscription_id' => $inscription->id,
                'category_id' => $category->id,
                'variant_id' => $variant?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \RuntimeException('Erreur lors du calcul des frais: ' . $e->getMessage());
        }
    }

    /**
     * Calcule les frais pour toutes les catégories d'une inscription
     */
    public function calculateAllFeesForInscription(ESBTPInscription $inscription): array
    {
        $cacheKey = "frais_calculation_{$inscription->id}_{$inscription->updated_at->timestamp}";
        
        return Cache::remember($cacheKey, 3600, function () use ($inscription) {
            $results = [];
            $totalMandatory = 0;
            $totalOptional = 0;
            
            $categories = ESBTPFraisCategory::active()->get();
            
            foreach ($categories as $category) {
                try {
                    $calculation = $this->calculateFeeForInscription($inscription, $category);
                    
                    $results[$category->id] = [
                        'category' => $category->toArray(),
                        'calculation' => $calculation,
                        'is_mandatory' => $category->is_mandatory,
                        'is_configured' => $calculation['configuration'] !== null,
                    ];
                    
                    if ($category->is_mandatory && $calculation['configuration']) {
                        $totalMandatory += $calculation['total_amount'];
                    } elseif (!$category->is_mandatory && $calculation['configuration']) {
                        $totalOptional += $calculation['total_amount'];
                    }
                    
                } catch (\Exception $e) {
                    Log::warning('Erreur lors du calcul pour la catégorie', [
                        'category_id' => $category->id,
                        'inscription_id' => $inscription->id,
                        'error' => $e->getMessage()
                    ]);
                    
                    $results[$category->id] = [
                        'category' => $category->toArray(),
                        'calculation' => null,
                        'error' => $e->getMessage(),
                        'is_mandatory' => $category->is_mandatory,
                        'is_configured' => false,
                    ];
                }
            }
            
            return [
                'inscription' => $inscription->toArray(),
                'categories' => $results,
                'totals' => [
                    'mandatory' => $totalMandatory,
                    'optional' => $totalOptional,
                    'grand_total' => $totalMandatory + $totalOptional,
                ],
                'calculated_at' => Carbon::now(),
            ];
        });
    }

    /**
     * Obtient la stratégie de calcul pour une catégorie
     */
    private function getStrategyForCategory(ESBTPFraisCategory $category): FraisCalculationStrategyInterface
    {
        $categoryType = $category->category_type ?? 'academic';
        
        if (!isset($this->strategies[$categoryType])) {
            Log::warning('Stratégie non trouvée pour le type', ['type' => $categoryType]);
            return $this->strategies['academic']; // Fallback
        }
        
        return $this->strategies[$categoryType];
    }

    /**
     * Récupère la configuration applicable pour une inscription et catégorie
     */
    private function getApplicableConfiguration(ESBTPInscription $inscription, ESBTPFraisCategory $category): ?ESBTPFraisRule
    {
        $cacheKey = "frais_config_{$category->id}_{$inscription->filiere_id}_{$inscription->niveau_id}_{$inscription->annee_universitaire_id}";
        
        return Cache::remember($cacheKey, 1800, function () use ($inscription, $category) {
            return ESBTPFraisRule::getApplicableRule(
                $category->id,
                $inscription->filiere_id,
                $inscription->niveau_id,
                $inscription->annee_universitaire_id
            );
        });
    }

    /**
     * Calcule le montant de base selon la configuration
     */
    private function calculateBaseAmount(?ESBTPFraisRule $configuration, ESBTPFraisCategory $category): float
    {
        if ($configuration && $configuration->amount > 0) {
            return (float) $configuration->amount;
        }
        
        return (float) ($category->default_amount ?? 0);
    }

    /**
     * Calcule les frais de retard
     */
    private function calculateLateFee(?ESBTPFraisRule $configuration, float $baseAmount, ESBTPInscription $inscription): float
    {
        if (!$configuration) {
            return 0;
        }
        
        $deadlineDays = $configuration->payment_deadline_days ?? 30;
        $daysLate = Carbon::now()->diffInDays($inscription->created_at->addDays($deadlineDays), false);
        
        if ($daysLate <= 0) {
            return 0;
        }
        
        return $configuration->calculateLateFee($baseAmount, $daysLate);
    }

    /**
     * Obtient les informations sur les échéanciers
     */
    private function getInstallmentInfo(?ESBTPFraisRule $configuration, float $amount): array
    {
        if (!$configuration || !$configuration->allowsInstallments()) {
            return [
                'allowed' => false,
                'max_installments' => 1,
                'min_amount_per_installment' => $amount,
            ];
        }
        
        return [
            'allowed' => true,
            'max_installments' => $configuration->max_installments,
            'min_amount_per_installment' => $configuration->getMinimumInstallmentAmount(),
            'suggested_schedule' => $this->generateInstallmentSchedule($amount, $configuration),
        ];
    }

    /**
     * Génère un échéancier suggéré
     */
    private function generateInstallmentSchedule(float $amount, ESBTPFraisRule $configuration): array
    {
        $maxInstallments = $configuration->max_installments;
        $minAmountPerInstallment = $configuration->getMinimumInstallmentAmount();
        
        $schedule = [];
        $remainingAmount = $amount;
        $installmentAmount = max($minAmountPerInstallment, $amount / $maxInstallments);
        
        for ($i = 1; $i <= $maxInstallments && $remainingAmount > 0; $i++) {
            $currentAmount = min($installmentAmount, $remainingAmount);
            
            // Ajuster le dernier versement pour couvrir le montant restant
            if ($i === $maxInstallments) {
                $currentAmount = $remainingAmount;
            }
            
            $schedule[] = [
                'installment_number' => $i,
                'amount' => $currentAmount,
                'due_date' => Carbon::now()->addMonths($i - 1)->format('Y-m-d'),
            ];
            
            $remainingAmount -= $currentAmount;
        }
        
        return $schedule;
    }

    /**
     * Invalide le cache pour une inscription
     */
    public function invalidateCache(ESBTPInscription $inscription): void
    {
        $pattern = "frais_*_{$inscription->id}_*";
        Cache::flush(); // Simplification - en production, utiliser un cache plus précis
        
        Log::info('Cache invalidé pour l\'inscription', ['inscription_id' => $inscription->id]);
    }

    /**
     * Valide les données de calcul
     */
    public function validateCalculationData(array $data): array
    {
        $errors = [];
        
        if (!isset($data['inscription_id']) || !$data['inscription_id']) {
            $errors[] = 'ID d\'inscription requis';
        }
        
        if (!isset($data['category_id']) || !$data['category_id']) {
            $errors[] = 'ID de catégorie requis';
        }
        
        if (isset($data['variant_id']) && $data['variant_id']) {
            $variant = ESBTPFraisVariant::find($data['variant_id']);
            if (!$variant || $variant->frais_category_id != $data['category_id']) {
                $errors[] = 'Variant invalide pour cette catégorie';
            }
        }
        
        return $errors;
    }

    /**
     * Obtient les statistiques de calcul pour le monitoring
     */
    public function getCalculationStats(): array
    {
        return Cache::remember('frais_calculation_stats', 300, function () {
            return [
                'total_configurations' => ESBTPFraisRule::active()->count(),
                'total_categories' => ESBTPFraisCategory::active()->count(),
                'total_variants' => ESBTPFraisVariant::active()->count(),
                'last_updated' => Carbon::now(),
            ];
        });
    }
}