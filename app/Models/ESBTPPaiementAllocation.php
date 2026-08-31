<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * La part d'un versement affectee a un frais precis.
 *
 * Un paiement sans allocation garde son comportement historique : sa
 * `frais_category_id` fait foi, et le versement entier lui revient. Des qu'il
 * porte des allocations, ce sont elles qui disent ou l'argent est alle.
 */
class ESBTPPaiementAllocation extends Model
{
    use HasFactory;

    protected $table = 'esbtp_paiement_allocations';

    protected $fillable = [
        'paiement_id',
        'frais_category_id',
        'montant',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function paiement()
    {
        return $this->belongsTo(ESBTPPaiement::class, 'paiement_id');
    }

    public function fraisCategory()
    {
        return $this->belongsTo(ESBTPFraisCategory::class, 'frais_category_id');
    }
}
