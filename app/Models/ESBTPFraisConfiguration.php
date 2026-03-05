<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle unifié pour la configuration des frais par classe/année
 * Remplace la complexité Rules + Variants par une approche simplifiée
 */
class ESBTPFraisConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'esbtp_frais_configurations';

    protected $fillable = [
        'frais_category_id',
        'filiere_id',
        'niveau_id', 
        'annee_universitaire_id',
        'amount',
        'amount_affecte',    // Nouveau: montant pour étudiants affectés
        'amount_reaffecte',  // Nouveau: montant pour étudiants réaffectés
        'amount_non_affecte', // Nouveau: montant pour étudiants non affectés
        'payment_deadline_days',
        'installments_allowed',
        'max_installments',
        'min_installment_amount',
        'late_fee_percentage',
        'late_fee_amount',
        'early_payment_discount',
        'sibling_discount_enabled',
        'bulk_discount_tiers',
        'seasonal_adjustments',
        'special_conditions',
        'is_active',
        'effective_date',
        'expiry_date',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_affecte' => 'decimal:2',
        'amount_reaffecte' => 'decimal:2',
        'amount_non_affecte' => 'decimal:2',
        'payment_deadline_days' => 'integer',
        'installments_allowed' => 'boolean',
        'max_installments' => 'integer',
        'min_installment_amount' => 'decimal:2',
        'late_fee_percentage' => 'decimal:2',
        'late_fee_amount' => 'decimal:2',
        'early_payment_discount' => 'decimal:2',
        'sibling_discount_enabled' => 'boolean',
        'bulk_discount_tiers' => 'array',
        'seasonal_adjustments' => 'array',
        'special_conditions' => 'array',
        'is_active' => 'boolean',
        'effective_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->effective_date)) {
                $model->effective_date = now()->toDateString();
            }
            if (empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });
    }

    /**
     * Relations
     */
    public function fraisCategory()
    {
        return $this->belongsTo(ESBTPFraisCategory::class, 'frais_category_id');
    }

    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    public function niveau()
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_id');
    }

    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function options()
    {
        return $this->hasMany(ESBTPFraisOption::class, 'configuration_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->where('effective_date', '<=', $now)
              ->where(function ($q2) use ($now) {
                  $q2->whereNull('expiry_date')
                     ->orWhere('expiry_date', '>=', $now);
              });
        });
    }

    public function scopeForFiliere($query, $filiereId)
    {
        return $query->where('filiere_id', $filiereId);
    }

    public function scopeForNiveau($query, $niveauId)
    {
        return $query->where('niveau_id', $niveauId);
    }

    public function scopeForAnnee($query, $anneeId)
    {
        return $query->where('annee_universitaire_id', $anneeId);
    }

    public function scopeGeneral($query)
    {
        return $query->whereNull('annee_universitaire_id');
    }

    /**
     * Méthodes métier
     */

    /**
     * Obtient la configuration applicable pour un contexte donné
     */
    public static function getApplicableConfiguration($categoryId, $filiereId, $niveauId, $anneeId = null)
    {
        $query = static::active()
            ->valid()
            ->where('frais_category_id', $categoryId)
            ->where('filiere_id', $filiereId)
            ->where('niveau_id', $niveauId);

        if ($anneeId) {
            // Priorité à la configuration spécifique à l'année
            $config = (clone $query)->where('annee_universitaire_id', $anneeId)->first();
            if ($config) return $config;
        }

        // Fallback: configuration générale
        return $query->whereNull('annee_universitaire_id')->first();
    }

    /**
     * Calcule les frais de retard
     */
    public function calculateLateFee($baseAmount, $daysLate = 0)
    {
        if ($daysLate <= 0) return 0;

        $lateFee = 0;

        if ($this->late_fee_percentage > 0) {
            $lateFee = $baseAmount * ($this->late_fee_percentage / 100);
        }

        if ($this->late_fee_amount > 0) {
            $lateFee += $this->late_fee_amount;
        }

        return $lateFee;
    }

    /**
     * Vérifie si les paiements échelonnés sont autorisés
     */
    public function allowsInstallments()
    {
        return $this->installments_allowed && $this->max_installments > 1;
    }

    /**
     * Calcule le montant minimum d'un échéancier
     */
    public function getMinimumInstallmentAmount()
    {
        if (!$this->allowsInstallments()) return $this->amount;

        $minByRule = $this->min_installment_amount ?: 0;
        $minByDivision = $this->amount / $this->max_installments;

        return max($minByRule, $minByDivision);
    }

    /**
     * Applique la remise de paiement anticipé
     */
    public function applyEarlyPaymentDiscount($amount)
    {
        if ($this->early_payment_discount > 0) {
            return $amount * (1 - $this->early_payment_discount / 100);
        }
        return $amount;
    }

    /**
     * Vérifie si la remise fratrie est activée
     */
    public function hasSiblingDiscount()
    {
        return $this->sibling_discount_enabled;
    }

    /**
     * Obtient les remises en volume
     */
    public function getBulkDiscountForQuantity($quantity)
    {
        if (!$this->bulk_discount_tiers || $quantity <= 1) {
            return 0;
        }

        $tiers = collect($this->bulk_discount_tiers);
        
        // Trier par quantité décroissante et prendre le premier applicable
        $applicableTier = $tiers
            ->sortByDesc('min_quantity')
            ->first(function ($tier) use ($quantity) {
                return $quantity >= $tier['min_quantity'];
            });

        return $applicableTier ? $applicableTier['discount_percentage'] : 0;
    }

    /**
     * Obtient l'ajustement saisonnier actuel
     */
    public function getCurrentSeasonalAdjustment()
    {
        if (!$this->seasonal_adjustments) {
            return 1.0;
        }

        $currentMonth = now()->month;
        $adjustments = collect($this->seasonal_adjustments);

        $applicable = $adjustments->first(function ($adjustment) use ($currentMonth) {
            return $currentMonth >= $adjustment['start_month'] && 
                   $currentMonth <= $adjustment['end_month'];
        });

        return $applicable ? $applicable['multiplier'] : 1.0;
    }

    /**
     * Vérifie si une condition spéciale s'applique
     */
    public function hasSpecialCondition($conditionType)
    {
        if (!$this->special_conditions) {
            return false;
        }

        return collect($this->special_conditions)->contains('type', $conditionType);
    }

    /**
     * Obtient le résumé de la configuration
     */
    public function getSummary()
    {
        return [
            'category' => $this->fraisCategory->name,
            'filiere' => $this->filiere->name,
            'niveau' => $this->niveau->name,
            'annee' => $this->anneeUniversitaire ? $this->anneeUniversitaire->name : 'Générale',
            'amount' => $this->amount,
            'payment_deadline' => $this->payment_deadline_days . ' jours',
            'installments' => $this->allowsInstallments() ? 'Oui (' . $this->max_installments . ' max)' : 'Non',
            'early_discount' => $this->early_payment_discount > 0 ? $this->early_payment_discount . '%' : 'Non',
            'sibling_discount' => $this->sibling_discount_enabled ? 'Oui' : 'Non',
            'seasonal_adjustments' => !empty($this->seasonal_adjustments) ? 'Oui' : 'Non',
        ];
    }

    /**
     * Récupère le montant selon le statut d'affectation.
     *
     * @param string $affectationStatus
     * @return float
     */
    public function getMontantByStatus($affectationStatus)
    {
        return match($affectationStatus) {
            'affecté' => $this->amount_affecte ?? $this->amount,
            'réaffecté' => $this->amount_reaffecte ?? $this->amount,
            'non_affecté' => $this->amount_non_affecte ?? $this->amount,
            default => $this->amount
        };
    }

    /**
     * Vérifie si des montants différenciés sont configurés.
     *
     * @return bool
     */
    public function hasDifferentiatedAmounts()
    {
        return $this->amount_affecte !== null || 
               $this->amount_reaffecte !== null || 
               $this->amount_non_affecte !== null;
    }

    /**
     * Retourne tous les montants configurés par statut.
     *
     * @return array
     */
    public function getAllAmounts()
    {
        return [
            'affecté' => $this->amount_affecte ?? $this->amount,
            'réaffecté' => $this->amount_reaffecte ?? $this->amount,
            'non_affecté' => $this->amount_non_affecte ?? $this->amount,
        ];
    }

    /**
     * Clone la configuration pour une nouvelle année
     */
    public function cloneForNewYear($newAnneeId, $userId)
    {
        $newConfig = $this->replicate(['id', 'created_at', 'updated_at']);
        $newConfig->annee_universitaire_id = $newAnneeId;
        $newConfig->created_by = $userId;
        $newConfig->effective_date = now();
        $newConfig->notes = 'Clonée depuis la configuration ID: ' . $this->id;
        
        $newConfig->save();
        
        // Cloner aussi les options
        foreach ($this->options as $option) {
            $newOption = $option->replicate(['id', 'created_at', 'updated_at']);
            $newOption->configuration_id = $newConfig->id;
            $newOption->save();
        }
        
        return $newConfig;
    }
}