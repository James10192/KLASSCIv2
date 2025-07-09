<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class ESBTPFacture extends Model implements Auditable
{
    use HasFactory, SoftDeletes, AuditableTrait;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_factures';

    /**
     * Configuration de l'audit pour la sécurité financière
     *
     * @var array
     */
    protected $auditInclude = [
        'numero',
        'montant_ht',
        'montant_tva',
        'montant_total',
        'montant_paye',
        'statut',
        'validateur_id',
        'date_validation',
        'date_emission',
        'date_echeance',
        // Nouvelles colonnes workflow si ajoutées
        'workflow_status',
        'approved_by',
        'approval_date'
    ];

    /**
     * Exclure les champs sensibles de l'audit
     *
     * @var array
     */
    protected $auditExclude = [
        'path_fichier', // Exclure les chemins de fichiers
    ];

    /**
     * Activer les timestamps dans l'audit
     *
     * @var bool
     */
    protected $auditTimestamps = true;

    /**
     * Events à auditer pour la sécurité
     *
     * @var array
     */
    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
        'retrieved', // Important pour tracer l'accès aux données financières
    ];

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
        // Nouvelles colonnes workflow potentielles
        'workflow_status',
        'approved_by',
        'approval_date'
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
        'approval_date' => 'datetime', // Nouvelle colonne workflow
    ];

    /**
     * Les attributs qui doivent être chiffrés pour la sécurité
     *
     * @var array
     */
    protected $encrypted = [
        // Ces champs peuvent être chiffrés si nécessaire
        // 'numero', // Numéro de facture sensible
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
