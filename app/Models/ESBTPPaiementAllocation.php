<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * La part d'un versement affectee a un frais precis.
 *
 * Un paiement sans allocation garde son comportement historique : sa
 * `frais_category_id` fait foi, et le versement entier lui revient. Des qu'il
 * porte des allocations, ce sont elles qui disent ou l'argent est alle.
 *
 * AUDITEE, parce que ces lignes sont desormais REECRITES : la correction
 * d'imputation ({@see \App\Services\Frais\RepartitionDuVersement::remplacer()})
 * supprime et recree des lignes sur de l'argent deja encaisse. Sans trace au
 * niveau de la ligne, une part deplacee d'un frais a l'autre ne laisserait rien
 * derriere elle.
 *
 * Le versement porte en plus UN audit de synthese par correction — evenement
 * `reventilation`, avec l'ancienne ventilation, la nouvelle et le motif. Les
 * deux se completent : celui-ci est lisible, ceux-la sont exhaustifs.
 */
class ESBTPPaiementAllocation extends Model implements Auditable
{
    use AuditableTrait, HasFactory;

    protected $table = 'esbtp_paiement_allocations';

    /**
     * Liste blanche : la table ne porte rien d'autre que ces trois colonnes de
     * fond, mais l'expliciter empeche qu'une colonne ajoutee demain se retrouve
     * auditee sans qu'on l'ait decide.
     *
     * @var array<int, string>
     */
    protected $auditInclude = [
        'paiement_id',
        'frais_category_id',
        'montant',
    ];

    /**
     * @var array<int, string>
     */
    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

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
