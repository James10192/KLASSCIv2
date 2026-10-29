<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPTransactionFinanciere extends Model
{
    use HasFactory;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_transaction_financieres';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'transactionable_type',
        'transactionable_id',
        'montant',
        'sens',
        'categorie',
        'reference',
        'date_transaction',
        'description',
        'createur_id'
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'montant' => 'decimal:2',
        'date_transaction' => 'datetime',
    ];

    /**
     * Relation polymorphe avec l'objet lié.
     */
    public function transactionable()
    {
        return $this->morphTo();
    }

    /**
     * Relation avec l'utilisateur créateur.
     */
    public function createur()
    {
        return $this->belongsTo(User::class, 'createur_id');
    }
}
