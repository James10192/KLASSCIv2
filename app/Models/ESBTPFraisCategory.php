<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle pour les catégories de frais de l'établissement
 * Gère les frais obligatoires (inscription, scolarité) et optionnels (cantine, transport, etc.)
 */
class ESBTPFraisCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'esbtp_frais_categories';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_mandatory',
        'is_active',
        'category_type',
        'sort_order',
        'default_amount',
        'payment_deadline_days',
        'icon',
        'color',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'default_amount' => 'decimal:2',
        'payment_deadline_days' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Règles de validation pour cette catégorie
     */
    public function rules()
    {
        return $this->hasMany(ESBTPFraisRule::class, 'frais_category_id');
    }

    /**
     * Paiements associés à cette catégorie
     */
    public function paiements()
    {
        return $this->hasMany(ESBTPPaiement::class, 'frais_category_id');
    }

    /**
     * Variants associés à cette catégorie
     */
    public function variants()
    {
        return $this->hasMany(ESBTPFraisVariant::class, 'frais_category_id');
    }

    /**
     * Variants actifs associés à cette catégorie
     */
    public function activeVariants()
    {
        return $this->variants()->active()->ordered();
    }

    /**
     * Scope pour les catégories actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les catégories obligatoires
     */
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * Scope pour les catégories optionnelles
     */
    public function scopeOptional($query)
    {
        return $query->where('is_mandatory', false);
    }

    /**
     * Scope pour ordonner par ordre de tri
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope pour les frais académiques (inscription, scolarité)
     */
    public function scopeAcademic($query)
    {
        return $query->where('category_type', 'academic');
    }

    /**
     * Scope pour les frais de services (cantine, transport)
     */
    public function scopeService($query)
    {
        return $query->where('category_type', 'service');
    }

    /**
     * Scope pour les frais administratifs (documentation, examens)
     */
    public function scopeAdministrative($query)
    {
        return $query->where('category_type', 'administrative');
    }

    /**
     * Obtient la règle applicable pour une filière/niveau/année donnée
     */
    public function getApplicableRule($filiereId, $niveauId, $anneeUniversitaireId = null)
    {
        $query = $this->rules()
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
     * Obtient le montant applicable pour une filière/niveau/année donnée
     */
    public function getAmountForContext($filiereId, $niveauId, $anneeUniversitaireId = null)
    {
        $rule = $this->getApplicableRule($filiereId, $niveauId, $anneeUniversitaireId);
        return $rule ? $rule->amount : $this->default_amount;
    }

    /**
     * Vérifie si cette catégorie est configurée pour une filière/niveau
     */
    public function isConfiguredFor($filiereId, $niveauId, $anneeUniversitaireId = null)
    {
        return $this->getApplicableRule($filiereId, $niveauId, $anneeUniversitaireId) !== null;
    }

    /**
     * Vérifie si cette catégorie a des variants
     */
    public function hasVariants()
    {
        return $this->variants()->exists();
    }

    /**
     * Obtient le variant par défaut
     */
    public function getDefaultVariant()
    {
        return $this->variants()->default()->first();
    }

    /**
     * Obtient le montant avec variant pour une filière/niveau/année donnée
     */
    public function getAmountWithVariant($filiereId, $niveauId, $variantId = null, $anneeUniversitaireId = null)
    {
        if ($variantId) {
            $variant = $this->variants()->find($variantId);
            if ($variant) {
                return $variant->calculatePrice($this->getAmountForContext($filiereId, $niveauId, $anneeUniversitaireId));
            }
        }
        
        return $this->getAmountForContext($filiereId, $niveauId, $anneeUniversitaireId);
    }

    /**
     * Obtient les catégories obligatoires par défaut
     */
    public static function getDefaultMandatoryCategories()
    {
        return [
            [
                'name' => 'Frais d\'inscription',
                'code' => 'INSCRIPTION',
                'description' => 'Frais d\'inscription obligatoire pour tous les étudiants',
                'is_mandatory' => true,
                'is_active' => true,
                'category_type' => 'academic',
                'sort_order' => 1,
                'default_amount' => 50000,
                'payment_deadline_days' => 30,
                'icon' => 'fas fa-user-plus',
                'color' => 'primary',
            ],
            [
                'name' => 'Frais de scolarité',
                'code' => 'SCOLARITE',
                'description' => 'Frais de scolarité obligatoire pour tous les étudiants',
                'is_mandatory' => true,
                'is_active' => true,
                'category_type' => 'academic',
                'sort_order' => 2,
                'default_amount' => 200000,
                'payment_deadline_days' => 60,
                'icon' => 'fas fa-graduation-cap',
                'color' => 'success',
            ],
        ];
    }

    /**
     * Obtient les catégories optionnelles par défaut
     */
    public static function getDefaultOptionalCategories()
    {
        return [
            [
                'name' => 'Frais de cantine',
                'code' => 'CANTINE',
                'description' => 'Frais de restauration scolaire',
                'is_mandatory' => false,
                'is_active' => true,
                'category_type' => 'service',
                'sort_order' => 3,
                'default_amount' => 30000,
                'payment_deadline_days' => 15,
                'icon' => 'fas fa-utensils',
                'color' => 'warning',
            ],
            [
                'name' => 'Frais de transport',
                'code' => 'TRANSPORT',
                'description' => 'Frais de transport scolaire',
                'is_mandatory' => false,
                'is_active' => true,
                'category_type' => 'service',
                'sort_order' => 4,
                'default_amount' => 25000,
                'payment_deadline_days' => 15,
                'icon' => 'fas fa-bus',
                'color' => 'info',
            ],
            [
                'name' => 'Frais de documentation',
                'code' => 'DOCUMENTATION',
                'description' => 'Frais de documentation et supports pédagogiques',
                'is_mandatory' => false,
                'is_active' => true,
                'category_type' => 'administrative',
                'sort_order' => 5,
                'default_amount' => 15000,
                'payment_deadline_days' => 30,
                'icon' => 'fas fa-books',
                'color' => 'secondary',
            ],
            [
                'name' => 'Frais d\'examen',
                'code' => 'EXAMEN',
                'description' => 'Frais d\'examen et de certification',
                'is_mandatory' => false,
                'is_active' => true,
                'category_type' => 'administrative',
                'sort_order' => 6,
                'default_amount' => 20000,
                'payment_deadline_days' => 45,
                'icon' => 'fas fa-certificate',
                'color' => 'danger',
            ],
        ];
    }
}