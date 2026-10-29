<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPFactureDetail extends Model
{
    use HasFactory;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_facture_details';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'facture_id',
        'designation',
        'description',
        'montant',
        'quantite',
        'prix_unitaire',
        'total_ligne',
    ];

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array
     */
    protected $casts = [
        'quantite' => 'integer',
        'montant' => 'decimal:2',
        'total_ligne' => 'decimal:2',
    ];

    /**
     * Relation avec la facture.
     */
    public function facture()
    {
        return $this->belongsTo(ESBTPFacture::class, 'facture_id');
    }

    /**
     * Obtenir le montant formaté.
     */
    public function getMontantFormateAttribute()
    {
        return number_format($this->total_ligne, 0, ',', ' ') . ' FCFA';
    }
}
