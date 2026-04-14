<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPInscription extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_inscriptions';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'etudiant_id',
        'annee_universitaire_id',
        'filiere_id',
        'niveau_id',
        'classe_id',
        'classe_alternative_id',
        'affectation_status', // Nouveau: statut d'affectation (affecté, réaffecté, non_affecté)
        'is_boursier', // Étudiant boursier pour cette inscription
        'date_inscription',
        'type_inscription', // Première inscription, réinscription, etc.
        'status', // active, annulée, etc.
        'is_sous_reserve', // Inscription conditionnelle (ex: sous réserve du BAC)
        'condition_reserve', // Motif de la réserve (ex: BACCALAURÉAT)
        'workflow_step', // Nouveau: étape du workflow
        'montant_scolarite',
        'frais_inscription',
        'numero_recu',
        'date_paiement',
        'mode_paiement',
        'paiement_validation_id', // Nouveau: référence paiement validation
        'comptabilite_activee', // Nouveau: flag comptabilité
        'observations',
        'documents_fournis', // JSON avec liste des documents
        'date_validation',
        'validated_by',
        'created_by',
        'updated_by',
        'reinscription_status',
        'reinscription_validated_at',
        'reinscription_validated_by',
        'reinscription_observations',
        'est_transfert', // Transfert d'un autre établissement
        'etablissement_origine', // Nom de l'établissement d'origine
        'inscription_origine_id', // Lien vers inscription tronc commun
        'type_changement', // Type de changement (specialisation)
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'date_inscription' => 'date',
        'date_paiement' => 'date',
        'date_validation' => 'date',
        'documents_fournis' => 'array',
        'montant_scolarite' => 'float',
        'frais_inscription' => 'float',
        'comptabilite_activee' => 'boolean',
        'is_sous_reserve' => 'boolean',
        'is_boursier' => 'boolean',
        'affectation_status' => 'string',
        'est_transfert' => 'boolean',
    ];

    // Constants for affectation status
    const DEFAULT_AFFECTATION_STATUS = 'affecté';

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
     * Relation avec la filière.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    /**
     * Relation avec le niveau d'étude.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function niveau()
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_id');
    }

    /**
     * Relation avec la classe.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classe()
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    /**
     * Relation avec les paiements de scolarité.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function paiements()
    {
        return $this->hasMany(ESBTPPaiement::class, 'inscription_id');
    }

    /**
     * Relation avec les paiements de scolarité (hors frais d'inscription).
     */
    public function paiementsScolarite()
    {
        return $this->hasMany(ESBTPPaiement::class, 'inscription_id')->where('type', 'scolarite');
    }

    /**
     * Relation pour le paiement des frais d'inscription.
     */
    public function paiementInscription()
    {
        return $this->hasOne(ESBTPPaiement::class, 'inscription_id')->where('type', 'inscription');
    }

    /**
     * Relation avec les paiements centralisés (nouveau système).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments()
    {
        return $this->hasMany(\App\Models\ESBTP\Payment::class, 'inscription_id');
    }

    /**
     * Frais souscriptions liées à cette inscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fraisSubscriptions()
    {
        return $this->hasMany(ESBTPFraisSubscription::class, 'inscription_id');
    }

    /**
     * Utilisateur qui a validé l'inscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Utilisateur qui a validé la réinscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reinscriptionValidatedBy()
    {
        return $this->belongsTo(User::class, 'reinscription_validated_by');
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
     * Utilisateur qui a mis à jour l'entrée.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtenir le montant total payé pour cette inscription.
     *
     * @return float
     */
    public function getMontantPayeAttribute()
    {
        return $this->paiements()->where('status', 'validé')->sum('montant');
    }

    /**
     * Obtenir le solde restant à payer.
     *
     * @return float
     */
    public function getSoldeRestantAttribute()
    {
        return $this->montant_scolarite - $this->montant_paye;
    }

    /**
     * Vérifier si l'inscription est entièrement payée.
     *
     * @return bool
     */
    public function getEstPayeeAttribute()
    {
        return $this->solde_restant <= 0;
    }

    /**
     * Obtenir le pourcentage payé de la scolarité.
     *
     * @return int
     */
    public function getPourcentagePayeAttribute()
    {
        if ($this->montant_scolarite <= 0) {
            return 100;
        }

        return min(100, round(($this->montant_paye / $this->montant_scolarite) * 100));
    }

    /**
     * Scope pour filtrer les inscriptions actives.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActives($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pour filtrer les inscriptions par année universitaire.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $anneeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParAnnee($query, $anneeId)
    {
        return $query->where('annee_universitaire_id', $anneeId);
    }

    /**
     * Scope pour filtrer les inscriptions de l'année en cours.
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

        return $query->where('annee_universitaire_id', $anneeEnCours->id);
    }

    /**
     * Scope pour filtrer les inscriptions avec au moins un paiement.
     */
    public function scopeAvecPaiements($query)
    {
        return $query->has('paiements');
    }

    /**
     * Vérifie si l'inscription est pour l'année en cours.
     *
     * @return bool
     */
    public function getEstPourAnneeEnCoursAttribute()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (!$anneeEnCours) {
            return false;
        }

        return $this->annee_universitaire_id === $anneeEnCours->id;
    }

    /**
     * Alias pour la relation avec le niveau d'étude.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function niveauEtude()
    {
        return $this->niveau();
    }

    /**
     * Relation avec la classe alternative.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classeAlternative()
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_alternative_id');
    }

    /**
     * Inscription d'origine (tronc commun → spécialisation).
     */
    public function inscriptionOrigine()
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_origine_id');
    }

    /**
     * Inscription de spécialisation (issue de cette inscription tronc commun).
     */
    public function inscriptionSpecialisation()
    {
        return $this->hasOne(ESBTPInscription::class, 'inscription_origine_id');
    }

    /**
     * Vérifie si cette inscription est issue d'un tronc commun.
     */
    public function isSpecialisation(): bool
    {
        return $this->type_changement === 'specialisation' && $this->inscription_origine_id !== null;
    }

    /**
     * Vérifie si cette inscription a donné lieu à une spécialisation.
     */
    public function hasSpecialisation(): bool
    {
        return $this->inscriptionSpecialisation()->exists();
    }

    /**
     * Relation avec le paiement de validation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paiementValidation()
    {
        return $this->belongsTo(ESBTPPaiement::class, 'paiement_validation_id');
    }

    /**
     * Relation avec l'historique du workflow.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function workflowHistory()
    {
        return $this->hasMany(ESBTPInscriptionWorkflowHistory::class, 'inscription_id')
                    ->orderBy('action_timestamp', 'desc');
    }

    /**
     * Scope pour filtrer par étape du workflow.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $step
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWorkflowStep($query, $step)
    {
        return $query->where('workflow_step', $step);
    }

    /**
     * Scope pour les inscriptions prospects.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProspects($query)
    {
        return $query->where('workflow_step', 'prospect');
    }

    /**
     * Scope pour les inscriptions validées.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValidees($query)
    {
        return $query->where('workflow_step', 'valide');
    }

    /**
     * Scope pour les inscriptions avec comptabilité activée.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeComptabiliteActivee($query)
    {
        return $query->where('comptabilite_activee', true);
    }

    /**
     * Scope pour filtrer les étudiants affectés.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAffectes($query)
    {
        return $query->where('affectation_status', static::DEFAULT_AFFECTATION_STATUS);
    }

    /**
     * Scope pour filtrer les étudiants réaffectés.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReaffectes($query)
    {
        return $query->where('affectation_status', 'réaffecté');
    }

    /**
     * Scope pour filtrer les étudiants non affectés.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNonAffectes($query)
    {
        return $query->where('affectation_status', 'non_affecté');
    }

    /**
     * Méthode pour faire avancer le workflow.
     *
     * @param string $nouvelleEtape
     * @param int $userId
     * @param string|null $commentaires
     * @param array|null $metadata
     * @return bool
     */
    public function avancerWorkflow(string $nouvelleEtape, int $userId, ?string $commentaires = null, ?array $metadata = null): bool
    {
        $ancienneEtape = $this->workflow_step;
        
        // Vérifier si la transition est valide
        if (!$this->isValidWorkflowTransition($ancienneEtape, $nouvelleEtape)) {
            return false;
        }
        
        // Mettre à jour l'étape
        $this->workflow_step = $nouvelleEtape;
        $saved = $this->save();
        
        if ($saved) {
            // Créer l'entrée d'historique
            ESBTPInscriptionWorkflowHistory::createEntry(
                $this->id,
                $ancienneEtape,
                $nouvelleEtape,
                'avancement_workflow',
                $userId,
                $commentaires,
                $metadata
            );
        }
        
        return $saved;
    }

    /**
     * Vérifier si une transition workflow est valide.
     *
     * @param string|null $from
     * @param string $to
     * @return bool
     */
    protected function isValidWorkflowTransition(?string $from, string $to): bool
    {
        $validTransitions = [
            'prospect' => ['documents_complets', 'en_validation'],
            'documents_complets' => ['en_validation', 'prospect'],
            'en_validation' => ['valide', 'prospect', 'documents_complets'],
            'valide' => ['etudiant_cree'],
            'etudiant_cree' => [], // État final, pas de transition possible
        ];
        
        if ($from === null) {
            return in_array($to, ['prospect', 'documents_complets']);
        }
        
        return in_array($to, $validTransitions[$from] ?? []);
    }

    /**
     * Obtenir le libellé de l'étape workflow.
     *
     * @return string
     */
    public function getWorkflowStepLabelAttribute(): string
    {
        $labels = [
            'prospect' => 'Prospect',
            'documents_complets' => 'Documents complets',
            'en_validation' => 'En validation',
            'valide' => 'Validé',
            'etudiant_cree' => 'Compte étudiant créé',
        ];
        
        return $labels[$this->workflow_step] ?? $this->workflow_step;
    }

    /**
     * Scope pour filtrer les inscriptions sous réserve.
     */
    public function scopeSousReserve($query)
    {
        return $query->where('is_sous_reserve', true);
    }

    /**
     * Lever la réserve de cette inscription.
     */
    public function leverReserve(): bool
    {
        $this->is_sous_reserve = false;
        $this->condition_reserve = null;
        return $this->save();
    }
}
