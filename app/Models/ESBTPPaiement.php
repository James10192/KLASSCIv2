<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CacheInvalidationTrait;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class ESBTPPaiement extends Model implements Auditable
{
    use HasFactory, SoftDeletes, AuditableTrait, CacheInvalidationTrait;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_paiements';

    /**
     * Configuration de l'audit pour la sécurité financière
     *
     * @var array
     */
    protected $auditInclude = [
        'montant',
        'reference_paiement',
        'mode_paiement',
        'numero_transaction',
        'date_paiement',
        'statut',
        'validateur_id',
        'date_validation',
        'numero_recu',
        'reference_externe',
        'metadata',
        'relance_id'
    ];

    /**
     * Exclure les champs sensibles de l'audit (seront chiffrés séparément)
     *
     * @var array
     */
    protected $auditExclude = [];

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
        // NOTE: 'retrieved' est volontairement désactivé pour:
        // 1. Éviter une charge massive d'audits (chaque lecture = 1 audit)
        // 2. Éviter le cycle infini avec UserResolver
        // 3. Les old_values et new_values d'un retrieved sont toujours vides
        // Les événements create/update/delete suffisent pour la traçabilité financière
    ];

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'inscription_id',
        'etudiant_id',
        'annee_universitaire_id',
        'type_paiement',
        'categorie_id',
        'frais_category_id',
        'montant',
        'reference_paiement',
        'mode_paiement',
        'numero_transaction',
        'date_paiement',
        'date_echeance',
        'statut',
        'createur_id',
        'validateur_id',
        'date_validation',
        'motif', // Scolarité, frais d'inscription, frais divers, etc.
        'numero_recu',
        'commentaire',
        'status', // En attente, validé, rejeté, etc.
        'created_by',
        'updated_by',
        // Nouvelles colonnes ajoutées en Task #1
        'reference_externe',
        'metadata',
        'relance_id',
        'reliquat_detail_id',
        // Phase 3 chantier analytics — allocation explicite à une tranche d'échéancier précise
        'target_due_line_key',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'montant' => 'float',
        'date_paiement' => 'date',
        'date_echeance' => 'date',
        'date_validation' => 'datetime',
        'metadata' => 'json', // Ajouté pour la nouvelle colonne JSON
    ];

    /**
     * Les attributs qui doivent être chiffrés pour la sécurité
     *
     * @var array
     */
    protected $encrypted = [
        // Ces champs seront chiffrés pour la sécurité des données financières
        // 'numero_transaction', // Peut être activé si nécessaire
        // 'reference_paiement', // Peut être activé si nécessaire
    ];

    /**
     * Relation avec la catégorie de paiement.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function categorie()
    {
        return $this->belongsTo(ESBTPCategoriePaiement::class, 'categorie_id');
    }

    /**
     * Relation avec la catégorie de frais (nouveau système).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fraisCategory()
    {
        return $this->belongsTo(ESBTPFraisCategory::class, 'frais_category_id');
    }

    /**
     * Relation avec l'étudiant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    /**
     * Relation avec l'année universitaire.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    /**
     * Relation avec l'utilisateur qui a créé le paiement.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createur()
    {
        return $this->belongsTo(User::class, 'createur_id');
    }

    /**
     * Relation avec l'utilisateur qui a validé le paiement.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function validateur()
    {
        return $this->belongsTo(User::class, 'validateur_id');
    }

    /**
     * Alias pour la relation validateur (compatibilité avec le contrôleur).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function validatedBy()
    {
        return $this->validateur();
    }

    /**
     * Relation avec l'inscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function inscription()
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_id');
    }

    public function relance()
    {
        return $this->belongsTo(ESBTPRelance::class, 'relance_id');
    }

    /**
     * Utilisateur qui a créé l'entrée.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alias canonique pour la relation createdBy — l'encaisseur du paiement.
     *
     * Utilisée pour afficher "Encaissé par : ..." sur l'index, le détail et le reçu PDF.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Utilisateur qui a mis à jour l'entrée.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope pour filtrer les paiements créés par un utilisateur donné (ownership).
     *
     * Utilisé pour la permission `paiements.view_own` : un utilisateur (caissier
     * notamment) ne voit que les paiements qu'il a lui-même encaissés.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \App\Models\User|int  $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOwnedBy($query, $user)
    {
        $userId = is_object($user) ? $user->getKey() : $user;

        return $query->where('created_by', $userId);
    }

    /**
     * Scope pour filtrer les paiements validés.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValides($query)
    {
        return $query->where('status', 'validé');
    }

    /**
     * Scope pour filtrer les paiements en attente.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnAttente($query)
    {
        return $query->where('status', 'en_attente');
    }

    /**
     * Scope pour les paiements rejetés.
     */
    public function scopeRejetes($query)
    {
        return $query->where('status', 'rejeté');
    }

    /**
     * Scope pour les paiements de scolarité.
     */
    public function scopeScolarite($query)
    {
        return $query->where('type_paiement', 'scolarite');
    }

    /**
     * Scope pour les frais d'inscription.
     */
    public function scopeFraisInscription($query)
    {
        return $query->where('type_paiement', 'inscription');
    }

    /**
     * Scope pour filtrer les paiements par année universitaire.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $anneeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParAnnee($query, $anneeId)
    {
        return $query->whereHas('inscription', function ($q) use ($anneeId) {
            $q->where('annee_universitaire_id', $anneeId);
        });
    }

    /**
     * Scope pour filtrer les paiements de l'année en cours.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAnneeEnCours($query)
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (!$anneeEnCours) {
            return $query->whereRaw('1=0'); // Retourne une requête vide si aucune année en cours
        }

        return $query->whereHas('inscription', function ($q) use ($anneeEnCours) {
            $q->where('annee_universitaire_id', $anneeEnCours->id);
        });
    }

    /**
     * Accesseur pour obtenir le statut formaté pour l'affichage.
     *
     * @return string
     */
    public function getStatusFormatteAttribute()
    {
        switch ($this->status) {
            case 'en_attente':
                return 'En attente';
            case 'validé':
                return 'Validé';
            case 'rejeté':
                return 'Rejeté';
            default:
                return ucfirst($this->status);
        }
    }

    /**
     * Accesseur pour obtenir la classe CSS selon le statut.
     *
     * @return string
     */
    public function getStatusClassAttribute()
    {
        switch ($this->status) {
            case 'en_attente':
                return 'warning';
            case 'validé':
                return 'success';
            case 'rejeté':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    /**
     * Générer un numéro de reçu unique.
     *
     * @param string $prefix Préfixe pour le numéro de reçu (ex: SCOL, INSC, etc.)
     * @return string
     */
    public static function genererNumeroRecu($prefix = 'REC')
    {
        // Récupérer l'année universitaire en cours
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneeCode = $anneeEnCours ? substr($anneeEnCours->code, 2, 2) : date('y');

        // Récupérer le dernier numéro de reçu pour ce préfixe et cette année
        $lastRecu = self::where('numero_recu', 'like', "{$prefix}{$anneeCode}-%")
                        ->orderByRaw('CAST(SUBSTRING_INDEX(numero_recu, "-", -1) AS UNSIGNED) DESC')
                        ->first();

        $seq = 1;
        if ($lastRecu) {
            $parts = explode('-', $lastRecu->numero_recu);
            $lastSeq = intval(end($parts));
            $seq = $lastSeq + 1;
        }

        // Formater le numéro séquentiel sur 5 chiffres
        $seqFormatted = str_pad($seq, 5, '0', STR_PAD_LEFT);

        return "{$prefix}{$anneeCode}-{$seqFormatted}";
    }
}
