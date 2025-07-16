<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle pour les règles de frais par filière/niveau/année
 * Permet de définir des montants spécifiques selon le contexte académique
 */
class ESBTPFraisRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'esbtp_frais_rules';

    protected $fillable = [
        'frais_category_id',
        'filiere_id',
        'niveau_id',
        'annee_universitaire_id',
        'amount',
        'payment_deadline_days',
        'installments_allowed',
        'max_installments',
        'min_installment_amount',
        'late_fee_percentage',
        'late_fee_amount',
        'is_active',
        'effective_date',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_deadline_days' => 'integer',
        'installments_allowed' => 'boolean',
        'max_installments' => 'integer',
        'min_installment_amount' => 'decimal:2',
        'late_fee_percentage' => 'decimal:2',
        'late_fee_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'effective_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Catégorie de frais associée
     */
    public function fraisCategory()
    {
        return $this->belongsTo(ESBTPFraisCategory::class, 'frais_category_id');
    }

    /**
     * Filière associée
     */
    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    /**
     * Niveau d'étude associé
     */
    public function niveau()
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_id');
    }

    /**
     * Année universitaire associée
     */
    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    /**
     * Scope pour les règles actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les règles en vigueur
     */
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

    /**
     * Scope pour une filière spécifique
     */
    public function scopeForFiliere($query, $filiereId)
    {
        return $query->where('filiere_id', $filiereId);
    }

    /**
     * Scope pour un niveau spécifique
     */
    public function scopeForNiveau($query, $niveauId)
    {
        return $query->where('niveau_id', $niveauId);
    }

    /**
     * Scope pour une année universitaire spécifique
     */
    public function scopeForAnneeUniversitaire($query, $anneeUniversitaireId)
    {
        return $query->where('annee_universitaire_id', $anneeUniversitaireId);
    }

    /**
     * Scope pour les règles générales (sans année spécifique)
     */
    public function scopeGeneral($query)
    {
        return $query->whereNull('annee_universitaire_id');
    }

    /**
     * Obtient la règle applicable pour une catégorie/filière/niveau/année donnée
     */
    public static function getApplicableRule($fraisCategoryId, $filiereId, $niveauId, $anneeUniversitaireId = null)
    {
        $query = static::active()
            ->valid()
            ->where('frais_category_id', $fraisCategoryId)
            ->where('filiere_id', $filiereId)
            ->where('niveau_id', $niveauId);

        if ($anneeUniversitaireId) {
            // Priorité à la règle spécifique à l'année
            $rule = (clone $query)->where('annee_universitaire_id', $anneeUniversitaireId)->first();
            if ($rule) return $rule;
        }

        // Fallback: règle générale (sans année spécifique)
        return $query->whereNull('annee_universitaire_id')->first();
    }

    /**
     * Calcule le montant des frais de retard
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
     * Obtient un récapitulatif des informations de la règle
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
            'late_fee' => $this->late_fee_percentage > 0 || $this->late_fee_amount > 0 ? 'Oui' : 'Non',
        ];
    }

    /**
     * Crée une règle par défaut pour une catégorie/filière/niveau
     */
    public static function createDefaultRule($fraisCategoryId, $filiereId, $niveauId, $amount = null, $anneeUniversitaireId = null)
    {
        $category = ESBTPFraisCategory::findOrFail($fraisCategoryId);
        
        return static::create([
            'frais_category_id' => $fraisCategoryId,
            'filiere_id' => $filiereId,
            'niveau_id' => $niveauId,
            'annee_universitaire_id' => $anneeUniversitaireId,
            'amount' => $amount ?: $category->default_amount,
            'payment_deadline_days' => $category->payment_deadline_days,
            'installments_allowed' => false,
            'max_installments' => 1,
            'min_installment_amount' => null,
            'late_fee_percentage' => 0,
            'late_fee_amount' => 0,
            'is_active' => true,
            'effective_date' => now(),
            'expiry_date' => null,
        ]);
    }
}