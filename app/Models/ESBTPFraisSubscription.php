<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ESBTPFraisSubscription extends Model
{
    protected $table = 'esbtp_frais_subscriptions';
    
    protected $fillable = [
        'inscription_id',
        'frais_category_id',
        'selected_option_id',
        'amount',
        'is_active',
        'subscribed_at',
        'created_by',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'subscribed_at' => 'datetime'
    ];

    /**
     * Relation avec l'inscription
     */
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_id');
    }

    /**
     * Relation avec la catégorie de frais
     */
    public function fraisCategory(): BelongsTo
    {
        return $this->belongsTo(ESBTPFraisCategory::class, 'frais_category_id');
    }

    /**
     * Relation avec l'option sélectionnée (peut être une configuration de frais)
     */
    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(ESBTPFraisOption::class, 'selected_option_id');
    }

    /**
     * Alias pour accéder à la configuration via l'option ou directement
     */
    public function fraisConfiguration()
    {
        // Si on a une option sélectionnée, utiliser sa configuration
        if ($this->selected_option_id) {
            return $this->selectedOption?->fraisConfiguration ?? null;
        }

        // Sinon, chercher directement la configuration par catégorie
        return ESBTPFraisConfiguration::where('frais_category_id', $this->frais_category_id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Relation avec l'utilisateur qui a créé la souscription
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope pour les souscriptions actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les souscriptions inactives
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope pour une inscription spécifique
     */
    public function scopeForInscription($query, $inscriptionId)
    {
        return $query->where('inscription_id', $inscriptionId);
    }

    /**
     * Scope pour une catégorie de frais spécifique
     */
    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('frais_category_id', $categoryId);
    }

    /**
     * Vérifier si un étudiant est souscrit à un frais optionnel
     */
    public static function isSubscribed($inscriptionId, $fraisCategoryId): bool
    {
        return self::where('inscription_id', $inscriptionId)
            ->where('frais_category_id', $fraisCategoryId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Souscrire à un frais optionnel
     */
    public static function subscribe($inscriptionId, $fraisCategoryId, $amount, $userId, $notes = null)
    {
        return self::updateOrCreate(
            [
                'inscription_id' => $inscriptionId,
                'frais_category_id' => $fraisCategoryId,
            ],
            [
                'amount' => $amount,
                'is_active' => true,
                'subscribed_at' => Carbon::now(),
                'created_by' => $userId,
                'notes' => $notes,
            ]
        );
    }

    /**
     * Se désabonner d'un frais optionnel
     */
    public static function unsubscribe($inscriptionId, $fraisCategoryId)
    {
        return self::where('inscription_id', $inscriptionId)
            ->where('frais_category_id', $fraisCategoryId)
            ->update(['is_active' => false]);
    }

    /**
     * Obtenir toutes les souscriptions actives pour une inscription
     */
    public static function getActiveSubscriptions($inscriptionId)
    {
        return self::with(['fraisCategory'])
            ->where('inscription_id', $inscriptionId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Obtenir le montant total souscrit pour une inscription
     */
    public static function getTotalSubscribedAmount($inscriptionId)
    {
        return self::where('inscription_id', $inscriptionId)
            ->where('is_active', true)
            ->sum('amount');
    }

    /**
     * Obtenir les statistiques des souscriptions pour une catégorie
     */
    public static function getCategoryStats($fraisCategoryId)
    {
        return [
            'total_subscriptions' => self::where('frais_category_id', $fraisCategoryId)
                ->where('is_active', true)
                ->count(),
            'total_amount' => self::where('frais_category_id', $fraisCategoryId)
                ->where('is_active', true)
                ->sum('amount'),
            'average_amount' => self::where('frais_category_id', $fraisCategoryId)
                ->where('is_active', true)
                ->avg('amount')
        ];
    }

    /**
     * Formater le montant pour l'affichage
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Vérifier si la souscription est récente (moins de 24h)
     */
    public function getIsRecentAttribute()
    {
        return $this->subscribed_at->gt(Carbon::now()->subDay());
    }

    /**
     * Obtenir le nom complet de l'étudiant
     */
    public function getStudentNameAttribute()
    {
        return $this->inscription->etudiant->nom . ' ' . $this->inscription->etudiant->prenom;
    }
}