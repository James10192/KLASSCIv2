<?php

namespace App\Models;

use App\Services\FraisScopeResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPFraisConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'esbtp_frais_configurations';

    protected $fillable = [
        'frais_category_id',
        'systeme_academique',
        'filiere_id',
        'parcours_id',
        'niveau_id',
        'annee_universitaire_id',
        'amount',
        'amount_affecte',
        'amount_reaffecte',
        'amount_non_affecte',
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
            if (empty($model->systeme_academique)) {
                $model->systeme_academique = $model->parcours_id
                    ? FraisScopeResolver::SYSTEME_LMD
                    : FraisScopeResolver::SYSTEME_BTS;
            }
        });
    }

    public function fraisCategory()
    {
        return $this->belongsTo(ESBTPFraisCategory::class, 'frais_category_id');
    }

    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    public function parcours()
    {
        return $this->belongsTo(ESBTPLMDParcours::class, 'parcours_id');
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

    public function echeancierRules()
    {
        return $this->hasMany(ESBTPEcheancierRule::class, 'scope_id')
            ->where('scope_type', ESBTPEcheancierRule::SCOPE_CONFIGURATION);
    }

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

    public function scopeForBtsScope($query, $filiereId, $niveauId, $anneeId = null)
    {
        $query->where(function ($q) {
            $q->where('systeme_academique', FraisScopeResolver::SYSTEME_BTS)
                ->orWhereNull('systeme_academique');
        })->where('filiere_id', $filiereId)
            ->where('niveau_id', $niveauId);

        if ($anneeId) {
            $query->where('annee_universitaire_id', $anneeId);
        }

        return $query;
    }

    public function scopeForLmdScope($query, $parcoursId, $niveauId, $anneeId = null)
    {
        $query->where('systeme_academique', FraisScopeResolver::SYSTEME_LMD)
            ->where('parcours_id', $parcoursId)
            ->where('niveau_id', $niveauId);

        if ($anneeId) {
            $query->where('annee_universitaire_id', $anneeId);
        }

        return $query;
    }

    public static function getApplicableConfiguration($categoryId, $filiereId, $niveauId, $anneeId = null)
    {
        return static::getApplicableForScope($categoryId, [
            'systeme' => FraisScopeResolver::SYSTEME_BTS,
            'filiere_id' => $filiereId,
            'niveau_id' => $niveauId,
            'annee_universitaire_id' => $anneeId,
        ]);
    }

    public static function getApplicableForClass(ESBTPClasse $classe)
    {
        $scope = app(FraisScopeResolver::class)->resolveForClasse($classe);

        return static::getConfigurationsForScope($scope, $scope['annee_universitaire_id'] ?? null, 'effective', true);
    }

    public static function getApplicableForInscription(ESBTPInscription $inscription)
    {
        $scope = app(FraisScopeResolver::class)->resolveForInscription($inscription);

        return static::getConfigurationsForScope($scope, $scope['annee_universitaire_id'] ?? null, 'effective', true);
    }

    public static function getGlobalForScope($categoryId, array $scope): ?self
    {
        return static::queryForScope($scope)
            ->active()
            ->valid()
            ->where('frais_category_id', $categoryId)
            ->whereNull('annee_universitaire_id')
            ->first();
    }

    public static function getAnnualOverrideForScope($categoryId, array $scope): ?self
    {
        $anneeId = $scope['annee_universitaire_id'] ?? null;
        if (! $anneeId) {
            return null;
        }

        return static::queryForScope($scope)
            ->active()
            ->valid()
            ->where('frais_category_id', $categoryId)
            ->where('annee_universitaire_id', $anneeId)
            ->first();
    }

    public static function getEffectiveForScope($categoryId, array $scope): ?self
    {
        $annual = static::getAnnualOverrideForScope($categoryId, $scope);
        if ($annual) {
            return $annual;
        }

        return static::getGlobalForScope($categoryId, $scope);
    }

    public static function getApplicableForScope($categoryId, array $scope): ?self
    {
        return static::getEffectiveForScope($categoryId, $scope);
    }

    public static function getConfigurationsForScope(array $scope, ?int $anneeId = null, string $mode = 'effective', bool $withRelations = false)
    {
        $buildQuery = function () use ($scope, $withRelations) {
            $query = static::queryForScope($scope)
                ->active()
                ->valid();

            if ($withRelations) {
                $query->with(['fraisCategory', 'options' => fn ($optionsQuery) => $optionsQuery->active()->ordered()]);
            }

            return $query;
        };

        if ($mode === 'annual') {
            return $anneeId
                ? $buildQuery()->where('annee_universitaire_id', $anneeId)->get()
                : collect();
        }

        $global = $buildQuery()->whereNull('annee_universitaire_id')->get()->keyBy('frais_category_id');
        if ($mode === 'global' || ! $anneeId) {
            return $global->values();
        }

        $annual = $buildQuery()->where('annee_universitaire_id', $anneeId)->get()->keyBy('frais_category_id');

        return $global->merge($annual)->values();
    }

    public static function queryForScope(array $scope)
    {
        $query = static::query();
        $systeme = strtoupper((string) ($scope['systeme'] ?? FraisScopeResolver::SYSTEME_BTS));

        if ($systeme === FraisScopeResolver::SYSTEME_LMD) {
            return $query
                ->where('systeme_academique', FraisScopeResolver::SYSTEME_LMD)
                ->where('parcours_id', $scope['parcours_id'] ?? null)
                ->where('niveau_id', $scope['niveau_id'] ?? null);
        }

        return $query
            ->where(function ($q) {
                $q->where('systeme_academique', FraisScopeResolver::SYSTEME_BTS)
                    ->orWhereNull('systeme_academique');
            })
            ->where('filiere_id', $scope['filiere_id'] ?? null)
            ->where('niveau_id', $scope['niveau_id'] ?? null);
    }

    public function calculateLateFee($baseAmount, $daysLate = 0)
    {
        if ($daysLate <= 0) {
            return 0;
        }

        $lateFee = 0;

        if ($this->late_fee_percentage > 0) {
            $lateFee = $baseAmount * ($this->late_fee_percentage / 100);
        }

        if ($this->late_fee_amount > 0) {
            $lateFee += $this->late_fee_amount;
        }

        return $lateFee;
    }

    public function allowsInstallments()
    {
        return $this->installments_allowed && $this->max_installments > 1;
    }

    public function getMinimumInstallmentAmount()
    {
        if (! $this->allowsInstallments()) {
            return $this->amount;
        }

        $minByRule = $this->min_installment_amount ?: 0;
        $minByDivision = $this->amount / $this->max_installments;

        return max($minByRule, $minByDivision);
    }

    public function applyEarlyPaymentDiscount($amount)
    {
        if ($this->early_payment_discount > 0) {
            return $amount * (1 - $this->early_payment_discount / 100);
        }

        return $amount;
    }

    public function hasSiblingDiscount()
    {
        return $this->sibling_discount_enabled;
    }

    public function getBulkDiscountForQuantity($quantity)
    {
        if (! $this->bulk_discount_tiers || $quantity <= 1) {
            return 0;
        }

        $tiers = collect($this->bulk_discount_tiers);
        $applicableTier = $tiers
            ->sortByDesc('min_quantity')
            ->first(function ($tier) use ($quantity) {
                return $quantity >= $tier['min_quantity'];
            });

        return $applicableTier ? $applicableTier['discount_percentage'] : 0;
    }

    public function getCurrentSeasonalAdjustment()
    {
        if (! $this->seasonal_adjustments) {
            return 1.0;
        }

        $currentMonth = now()->month;
        $adjustments = collect($this->seasonal_adjustments);

        $applicable = $adjustments->first(function ($adjustment) use ($currentMonth) {
            return $currentMonth >= $adjustment['start_month']
                && $currentMonth <= $adjustment['end_month'];
        });

        return $applicable ? $applicable['multiplier'] : 1.0;
    }

    public function hasSpecialCondition($conditionType)
    {
        if (! $this->special_conditions) {
            return false;
        }

        return collect($this->special_conditions)->contains('type', $conditionType);
    }

    public function getSummary()
    {
        return [
            'category' => $this->fraisCategory->name,
            'systeme' => $this->systeme_academique ?? FraisScopeResolver::SYSTEME_BTS,
            'filiere' => $this->filiere->name ?? null,
            'parcours' => $this->parcours->name ?? null,
            'niveau' => $this->niveau->name,
            'annee' => $this->anneeUniversitaire ? $this->anneeUniversitaire->name : 'Generale',
            'amount' => $this->amount,
            'payment_deadline' => $this->payment_deadline_days . ' jours',
            'installments' => $this->allowsInstallments() ? 'Oui (' . $this->max_installments . ' max)' : 'Non',
            'early_discount' => $this->early_payment_discount > 0 ? $this->early_payment_discount . '%' : 'Non',
            'sibling_discount' => $this->sibling_discount_enabled ? 'Oui' : 'Non',
            'seasonal_adjustments' => ! empty($this->seasonal_adjustments) ? 'Oui' : 'Non',
        ];
    }

    public function getMontantByStatus($affectationStatus)
    {
        return match ($affectationStatus) {
            ESBTPInscription::DEFAULT_AFFECTATION_STATUS => $this->amount_affecte ?? $this->amount,
            'reaffecte', 'réaffecté' => $this->amount_reaffecte ?? $this->amount,
            'non_affecte', 'non_affecté' => $this->amount_non_affecte ?? $this->amount,
            default => $this->amount,
        };
    }

    public function hasDifferentiatedAmounts()
    {
        return $this->amount_affecte !== null
            || $this->amount_reaffecte !== null
            || $this->amount_non_affecte !== null;
    }

    public function getAllAmounts()
    {
        return [
            ESBTPInscription::DEFAULT_AFFECTATION_STATUS => $this->amount_affecte ?? $this->amount,
            'réaffecté' => $this->amount_reaffecte ?? $this->amount,
            'non_affecté' => $this->amount_non_affecte ?? $this->amount,
        ];
    }

    public function cloneForNewYear($newAnneeId, $userId)
    {
        $newConfig = $this->replicate(['id', 'created_at', 'updated_at']);
        $newConfig->annee_universitaire_id = $newAnneeId;
        $newConfig->created_by = $userId;
        $newConfig->effective_date = now();
        $newConfig->notes = 'Clonee depuis la configuration ID: ' . $this->id;
        $newConfig->save();

        foreach ($this->options as $option) {
            $newOption = $option->replicate(['id', 'created_at', 'updated_at']);
            $newOption->configuration_id = $newConfig->id;
            $newOption->save();
        }

        return $newConfig;
    }
}
