<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPFraisVariant extends Model
{
    use HasFactory;

    protected $table = 'esbtp_frais_variants';

    protected $fillable = [
        'frais_category_id',
        'name',
        'description',
        'amount',
        'additional_data',
        'is_default',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'additional_data' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'amount' => 'decimal:2',
        'sort_order' => 'integer'
    ];

    // Relations
    public function fraisCategory()
    {
        return $this->belongsTo(ESBTPFraisCategory::class, 'frais_category_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(ESBTPFraisSubscription::class, 'variant_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }

    // Mutators
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucfirst(trim($value));
    }

    // Méthodes utilitaires
    public function hasAdditionalData($key = null)
    {
        if ($key) {
            return isset($this->additional_data[$key]);
        }
        return !empty($this->additional_data);
    }

    public function getAdditionalData($key, $default = null)
    {
        return $this->additional_data[$key] ?? $default;
    }

    public function setAdditionalData($key, $value)
    {
        $data = $this->additional_data ?? [];
        $data[$key] = $value;
        $this->additional_data = $data;
    }

    /**
     * Vérifie si ce variant est applicable pour une classe donnée
     */
    public function isApplicableForClass($filiereId, $niveauId)
    {
        // Logique pour vérifier si le variant est applicable
        // Par exemple, certains arrêts de transport peuvent être spécifiques à certaines classes
        if ($this->hasAdditionalData('applicable_classes')) {
            $applicableClasses = $this->getAdditionalData('applicable_classes', []);
            return in_array("{$filiereId}-{$niveauId}", $applicableClasses);
        }
        
        return true; // Par défaut, applicable à toutes les classes
    }

    /**
     * Calcule le prix pour ce variant (peut inclure des modificateurs)
     */
    public function calculatePrice($baseAmount = null)
    {
        // Si un montant de base est fourni, ce variant peut être un modificateur
        if ($baseAmount !== null && $this->hasAdditionalData('price_type')) {
            $priceType = $this->getAdditionalData('price_type');
            
            if ($priceType === 'percentage') {
                return $baseAmount * ($this->amount / 100);
            } elseif ($priceType === 'modifier') {
                return $baseAmount + $this->amount;
            }
        }
        
        return $this->amount;
    }
}