<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle pour les options de frais (remplace ESBTPFraisVariant)
 * Gère les variants spécifiques comme les arrêts de transport, types de cantine, etc.
 */
class ESBTPFraisOption extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'esbtp_frais_options';

    protected $fillable = [
        'configuration_id',        // NULL pour options globales, ID pour options par classe
        'frais_category_id',       // Ajouté pour options globales
        'name',
        'description',
        'additional_amount',
        'is_default',
        'is_active',
        'option_type',             // 'class_based' ou 'global'
        'available_from',
        'available_to',
        'eligibility_conditions',
        'max_selections',
        'sort_order',
    ];

    protected $casts = [
        'additional_amount' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'available_from' => 'datetime',
        'available_to' => 'datetime',
        'eligibility_conditions' => 'array',
        'max_selections' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Relations
     */
    public function configuration()
    {
        return $this->belongsTo(ESBTPFraisConfiguration::class, 'configuration_id');
    }

    public function fraisCategory()
    {
        return $this->belongsTo(ESBTPFraisCategory::class, 'frais_category_id');
    }


    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subscriptions()
    {
        return $this->hasMany(ESBTPFraisSubscription::class, 'option_id');
    }

    public function assignments()
    {
        return $this->hasMany(ESBTPOptionAssignment::class, 'option_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('esbtp_frais_options.is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('frais_category_id', $categoryId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeAvailable($query)
    {
        return $query->active()->where(function ($q) {
            $q->whereNull('capacity_limit')
              ->orWhereRaw('capacity_limit > (SELECT COUNT(*) FROM esbtp_frais_subscriptions WHERE option_id = esbtp_frais_options.id AND is_active = 1)');
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('option_type', $type);
    }

    /**
     * Scope pour les options globales (non liées à une classe)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('configuration_id')->where('option_type', 'global');
    }

    /**
     * Scope pour les options par classe
     */
    public function scopeClassBased($query)
    {
        return $query->whereNotNull('configuration_id')->where('option_type', 'class_based');
    }

    /**
     * Scope pour une catégorie de frais spécifique
     */
    public function scopeForFraisCategory($query, $categoryId)
    {
        return $query->where('frais_category_id', $categoryId);
    }

    /**
     * Méthodes métier
     */

    /**
     * Vérifie si l'option est globale (transport, cantine, etc.)
     */
    public function isGlobal()
    {
        return $this->configuration_id === null && $this->option_type === 'global';
    }

    /**
     * Vérifie si l'option est liée à une classe
     */
    public function isClassBased()
    {
        return $this->configuration_id !== null && $this->option_type === 'class_based';
    }

    /**
     * Obtient le contexte de l'option (classe ou global)
     */
    public function getContext()
    {
        if ($this->isGlobal()) {
            return [
                'type' => 'global',
                'category' => $this->fraisCategory->name ?? 'N/A',
                'scope' => 'Tous les étudiants'
            ];
        }

        if ($this->isClassBased() && $this->configuration) {
            return [
                'type' => 'class_based',
                'class' => $this->configuration->filiere->name . ' - ' . $this->configuration->niveau->name,
                'scope' => 'Spécifique à la classe'
            ];
        }

        return [
            'type' => 'unknown',
            'scope' => 'Non défini'
        ];
    }

    /**
     * Calcule le prix final en appliquant le modificateur
     */
    public function calculateFinalPrice($baseConfigurationAmount = null)
    {
        $baseAmount = $baseConfigurationAmount ?? $this->base_amount;
        
        if (!$baseAmount) {
            return 0;
        }

        switch ($this->modifier_type) {
            case 'fixed':
                return $this->base_amount ?: $this->amount_modifier;
                
            case 'percentage':
                return $baseAmount * (1 + $this->amount_modifier / 100);
                
            case 'addition':
                return $baseAmount + $this->amount_modifier;
                
            case 'subtraction':
                return max(0, $baseAmount - $this->amount_modifier);
                
            case 'multiplication':
                return $baseAmount * $this->amount_modifier;
                
            default:
                return $baseAmount;
        }
    }

    /**
     * Vérifie si l'option est disponible pour un étudiant
     */
    public function isAvailableForStudent(ESBTPInscription $inscription)
    {
        // Vérifier si l'option est active
        if (!$this->is_active) {
            return false;
        }

        // Vérifier la capacité
        if ($this->capacity_limit && $this->subscriptions()->active()->count() >= $this->capacity_limit) {
            return false;
        }

        // Vérifier les conditions d'availability
        if (!$this->checkAvailabilityConditions($inscription)) {
            return false;
        }

        // Vérifier les restrictions géographiques
        if (!$this->checkGeographicRestrictions($inscription)) {
            return false;
        }

        // Vérifier la période d'effectivité
        if (!$this->checkEffectivePeriod()) {
            return false;
        }

        return true;
    }

    /**
     * Vérifie les conditions de disponibilité
     */
    private function checkAvailabilityConditions(ESBTPInscription $inscription)
    {
        if (!$this->availability_conditions) {
            return true;
        }

        foreach ($this->availability_conditions as $condition) {
            switch ($condition['type']) {
                case 'filiere':
                    if (!in_array($inscription->filiere_id, $condition['values'])) {
                        return false;
                    }
                    break;
                    
                case 'niveau':
                    if (!in_array($inscription->niveau_id, $condition['values'])) {
                        return false;
                    }
                    break;
                    
                case 'residence':
                    $studentCity = $inscription->etudiant->ville_residence ?? '';
                    if (!in_array($studentCity, $condition['values'])) {
                        return false;
                    }
                    break;
                    
                case 'age_min':
                    $age = $inscription->etudiant->date_naissance ? 
                        $inscription->etudiant->date_naissance->age : 0;
                    if ($age < $condition['value']) {
                        return false;
                    }
                    break;
                    
                case 'age_max':
                    $age = $inscription->etudiant->date_naissance ? 
                        $inscription->etudiant->date_naissance->age : 0;
                    if ($age > $condition['value']) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Vérifie les restrictions géographiques
     */
    private function checkGeographicRestrictions(ESBTPInscription $inscription)
    {
        if (!$this->geographic_restrictions) {
            return true;
        }

        $studentCity = $inscription->etudiant->ville_residence ?? '';
        $studentRegion = $inscription->etudiant->region_residence ?? '';

        foreach ($this->geographic_restrictions as $restriction) {
            switch ($restriction['type']) {
                case 'cities_only':
                    return in_array($studentCity, $restriction['values']);
                    
                case 'cities_except':
                    return !in_array($studentCity, $restriction['values']);
                    
                case 'regions_only':
                    return in_array($studentRegion, $restriction['values']);
                    
                case 'regions_except':
                    return !in_array($studentRegion, $restriction['values']);
                    
                case 'radius':
                    // Calcul de distance (nécessiterait des coordonnées GPS)
                    return true; // Placeholder
            }
        }

        return true;
    }

    /**
     * Vérifie la période d'effectivité
     */
    private function checkEffectivePeriod()
    {
        if (!$this->effective_period) {
            return true;
        }

        $now = now();
        
        if (isset($this->effective_period['start_date'])) {
            if ($now->lt($this->effective_period['start_date'])) {
                return false;
            }
        }
        
        if (isset($this->effective_period['end_date'])) {
            if ($now->gt($this->effective_period['end_date'])) {
                return false;
            }
        }

        // Vérifier les jours de la semaine si applicable
        if (isset($this->effective_period['days_of_week'])) {
            $currentDayOfWeek = $now->dayOfWeek; // 0 = dimanche, 1 = lundi, etc.
            if (!in_array($currentDayOfWeek, $this->effective_period['days_of_week'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtient les places disponibles
     */
    public function getAvailableSpots()
    {
        if (!$this->capacity_limit) {
            return null; // Capacité illimitée
        }

        $currentSubscriptions = $this->subscriptions()->active()->count();
        return max(0, $this->capacity_limit - $currentSubscriptions);
    }

    /**
     * Vérifie si l'option a des places disponibles
     */
    public function hasAvailableSpots()
    {
        if (!$this->capacity_limit) {
            return true;
        }

        return $this->getAvailableSpots() > 0;
    }

    /**
     * Obtient les métadonnées spécifiques
     */
    public function getMetadata($key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Définit une métadonnée
     */
    public function setMetadata($key, $value)
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;
    }

    /**
     * Obtient les statistiques de l'option
     */
    public function getStats()
    {
        return [
            'total_subscriptions' => $this->subscriptions()->count(),
            'active_subscriptions' => $this->subscriptions()->active()->count(),
            'available_spots' => $this->getAvailableSpots(),
            'utilization_rate' => $this->capacity_limit ? 
                ($this->subscriptions()->active()->count() / $this->capacity_limit * 100) : null,
        ];
    }

    /**
     * Formatte le prix pour l'affichage
     */
    public function getFormattedPriceAttribute()
    {
        $price = $this->calculateFinalPrice();
        return number_format($price, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Obtient la description complète avec les détails
     */
    public function getFullDescriptionAttribute()
    {
        $description = $this->description ?: $this->name;
        
        if ($this->capacity_limit) {
            $available = $this->getAvailableSpots();
            $description .= " (Places disponibles: {$available}/{$this->capacity_limit})";
        }
        
        if ($this->requires_approval) {
            $description .= " (Sous réserve d'approbation)";
        }
        
        return $description;
    }
}