<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ligne d'un bulletin de paie enseignant : un gain (heures×taux, prime) ou une
 * retenue (impôt ITS, CNPS, avance, autre). Σ gains − Σ retenues = net.
 *
 * @see App\Models\ESBTPSalaire
 */
class ESBTPSalaireDetail extends Model
{
    protected $table = 'esbtp_salaire_details';

    public const CAT_GAIN = 'gain';
    public const CAT_RETENUE = 'retenue';

    protected $fillable = [
        'salaire_id',
        'categorie',
        'type',
        'libelle',
        'heures',
        'taux',
        'montant',
        'ordre',
    ];

    protected $casts = [
        'heures'  => 'decimal:2',
        'taux'    => 'decimal:2',
        'montant' => 'decimal:2',
        'ordre'   => 'integer',
    ];

    public function salaire()
    {
        return $this->belongsTo(ESBTPSalaire::class, 'salaire_id');
    }
}
