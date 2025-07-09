<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class ESBTPDepense extends Model implements Auditable
{
    use HasFactory, SoftDeletes, AuditableTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'esbtp_depenses';

    /**
     * Configuration de l'audit pour la sécurité financière
     *
     * @var array
     */
    protected $auditInclude = [
        'montant',
        'reference',
        'libelle',
        'statut',
        'mode_paiement',
        'numero_transaction',
        'validateur_id',
        'date_validation',
        // Nouvelles colonnes workflow de Task #1
        'numero_bon',
        'statut_workflow',
        'workflow_data',
        'approved_by',
        'date_approbation'
    ];

    /**
     * Exclure les champs sensibles de l'audit
     *
     * @var array
     */
    protected $auditExclude = [
        'path_justificatif', // Exclure les chemins de fichiers
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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'categorie_id',
        'reference',
        'libelle',
        'description',
        'montant',
        'date_depense',
        'mode_paiement',
        'numero_transaction',
        'fournisseur_id',
        'statut',
        'createur_id',
        'validateur_id',
        'date_validation',
        'path_justificatif',
        'notes_internes',
        // Nouvelles colonnes workflow ajoutées en Task #1
        'numero_bon',
        'statut_workflow',
        'workflow_data',
        'approved_by',
        'date_approbation'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'montant' => 'decimal:2',
        'date_depense' => 'date',
        'date_validation' => 'datetime',
        'workflow_data' => 'json', // Nouvelle colonne JSON
        'date_approbation' => 'datetime', // Nouvelle colonne
    ];

    /**
     * Les attributs qui doivent être chiffrés pour la sécurité
     *
     * @var array
     */
    protected $encrypted = [
        // Ces champs peuvent être chiffrés si nécessaire
        // 'numero_transaction',
        // 'notes_internes', // Notes sensibles
    ];

    /**
     * Get the category associated with the expense.
     */
    public function categorie()
    {
        return $this->belongsTo(ESBTPCategorieDepense::class, 'categorie_id');
    }

    /**
     * Get the supplier associated with the expense.
     */
    public function fournisseur()
    {
        return $this->belongsTo(ESBTPFournisseur::class, 'fournisseur_id');
    }

    /**
     * Get the creator of the expense.
     */
    public function createur()
    {
        return $this->belongsTo(User::class, 'createur_id');
    }

    /**
     * Get the validator of the expense.
     */
    public function validateur()
    {
        return $this->belongsTo(User::class, 'validateur_id');
    }

    /**
     * Get the financial transaction associated with the expense.
     */
    public function transaction()
    {
        return $this->morphOne(ESBTPTransactionFinanciere::class, 'transactionable');
    }

    /**
     * Scope a query to filter expenses by status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $statut
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    /**
     * Scope a query to filter expenses by date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $dateDebut
     * @param  string  $dateFin
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePeriode($query, $dateDebut, $dateFin)
    {
        if ($dateDebut && $dateFin) {
            return $query->whereBetween('date_depense', [$dateDebut, $dateFin]);
        } elseif ($dateDebut) {
            return $query->where('date_depense', '>=', $dateDebut);
        } elseif ($dateFin) {
            return $query->where('date_depense', '<=', $dateFin);
        }

        return $query;
    }

    /**
     * Scope a query to filter expenses by category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $categorieId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategorie($query, $categorieId)
    {
        if ($categorieId) {
            return $query->where('categorie_id', $categorieId);
        }

        return $query;
    }

    /**
     * Get the formatted amount.
     *
     * @return string
     */
    public function getMontantFormateAttribute()
    {
        return number_format($this->montant, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Get the formatted date.
     *
     * @return string
     */
    public function getDateFormateAttribute()
    {
        return $this->date_depense->format('d/m/Y');
    }

    /**
     * Check if the expense is validated.
     *
     * @return bool
     */
    public function estValidee()
    {
        return $this->statut === 'validée';
    }

    /**
     * Check if the expense is pending validation.
     *
     * @return bool
     */
    public function estEnAttente()
    {
        return $this->statut === 'en attente';
    }

    /**
     * Check if the expense is canceled.
     *
     * @return bool
     */
    public function estAnnulee()
    {
        return $this->statut === 'annulée';
    }

    /**
     * Get CSS class based on status.
     *
     * @return string
     */
    public function getStatusClassAttribute()
    {
        switch ($this->statut) {
            case 'validée':
                return 'success';
            case 'en attente':
                return 'warning';
            case 'annulée':
                return 'danger';
            default:
                return 'secondary';
        }
    }
}
