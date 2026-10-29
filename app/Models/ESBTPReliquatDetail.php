<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ESBTPReliquatDetail extends Model
{
    use HasFactory;

    protected $table = 'esbtp_reliquats_details';

    protected $fillable = [
        'inscription_source_id',
        'inscription_destination_id',
        'frais_subscription_id',
        'montant_attendu',
        'montant_paye',
        'montant_reliquat',
        'montant_regle',
        'statut',
        'date_creation',
        'date_derniere_maj',
        'created_by',
        'notes'
    ];

    protected $casts = [
        'montant_attendu' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'montant_reliquat' => 'decimal:2',
        'montant_regle' => 'decimal:2',
        'date_creation' => 'datetime',
        'date_derniere_maj' => 'datetime'
    ];

    /**
     * Inscription source (année N)
     */
    public function inscriptionSource(): BelongsTo
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_source_id');
    }

    /**
     * Inscription destination (année N+1)
     */
    public function inscriptionDestination(): BelongsTo
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_destination_id');
    }

    /**
     * Souscription de frais concernée
     */
    public function fraisSubscription(): BelongsTo
    {
        return $this->belongsTo(ESBTPFraisSubscription::class, 'frais_subscription_id');
    }

    /**
     * Utilisateur qui a créé le reliquat
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculer le solde restant du reliquat
     */
    public function getSoldeRestantAttribute(): float
    {
        return $this->montant_reliquat - $this->montant_regle;
    }

    /**
     * Vérifier si le reliquat est totalement soldé
     */
    public function getIsSoldeAttribute(): bool
    {
        return $this->solde_restant <= 0;
    }

    /**
     * Scope pour les reliquats actifs
     */
    public function scopeActifs($query)
    {
        return $query->whereIn('statut', ['actif', 'partiellement_regle']);
    }

    /**
     * Scope pour une inscription destination donnée
     */
    public function scopePourInscription($query, $inscriptionId)
    {
        return $query->where('inscription_destination_id', $inscriptionId);
    }
}