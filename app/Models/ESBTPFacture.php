<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPFacture extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_factures';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'numero',
        'fournisseur_id',
        'date_emission',
        'date_echeance',
        'montant_ht',
        'montant_tva',
        'montant_total',
        'montant_paye',
        'statut',
        'notes',
        'createur_id',
        'validateur_id',
        'date_validation',
        'path_fichier',
    ];

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array
     */
    protected $casts = [
        'date_emission' => 'date',
        'date_echeance' => 'date',
        'date_validation' => 'datetime',
        'montant_ht' => 'decimal:2',
        'montant_tva' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'montant_paye' => 'decimal:2',
    ];

    /**
     * Relation avec le fournisseur.
     */
    public function fournisseur()
    {
        return $this->belongsTo(ESBTPFournisseur::class, 'fournisseur_id');
    }

    /**
     * Relation avec l'utilisateur qui a créé la facture.
     */
    public function createur()
    {
        return $this->belongsTo(User::class, 'createur_id');
    }

    /**
     * Relation avec l'utilisateur qui a validé la facture.
     */
    public function validateur()
    {
        return $this->belongsTo(User::class, 'validateur_id');
    }

    /**
     * Relation avec les détails de la facture.
     */
    public function details()
    {
        return $this->hasMany(ESBTPFactureDetail::class, 'facture_id');
    }

    /**
     * Obtenir le montant restant à payer.
     */
    public function getMontantRestantAttribute()
    {
        return max(0, $this->montant_total - $this->montant_paye);
    }

    /**
     * Obtenir le montant total formaté.
     */
    public function getMontantTotalFormateAttribute()
    {
        return number_format($this->montant_total, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Déterminer si la facture est payée.
     */
    public function estPayee()
    {
        return $this->statut === 'payée' || $this->montant_paye >= $this->montant_total;
    }

    /**
     * Déterminer si la facture est en attente.
     */
    public function estEnAttente()
    {
        return $this->statut === 'en attente';
    }

    public function etudiant()
    {
        return $this->belongsTo(\App\Models\ESBTPEtudiant::class, 'etudiant_id');
    }

    public function inscription()
    {
        return $this->belongsTo(\App\Models\ESBTPInscription::class, 'inscription_id');
    }

    public function anneeUniversitaire()
    {
        return $this->belongsTo(\App\Models\ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }
}
