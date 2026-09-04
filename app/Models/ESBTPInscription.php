<?php

namespace App\Models;

use App\Helpers\SettingsHelper;
use App\Services\Dossier\MaterialisationPiecesService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPInscription extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    /**
     * Colonnes auditées (whitelist — éviter explosion volume).
     *
     * @var array
     */
    protected $auditInclude = [
        'etudiant_id',
        'classe_id',
        'classe_alternative_id',
        'annee_universitaire_id',
        'filiere_id',
        'niveau_id',
        'status',
        'workflow_step',
        'is_sous_reserve',
        'condition_reserve',
        'affectation_status',
        'type_inscription',
        'type_changement',
        'inscription_origine_id',
        'montant_scolarite',
        'frais_inscription',
        'paiement_validation_id',
        'comptabilite_activee',
        'date_inscription',
        'date_validation',
        'validated_by',
        'reinscription_status',
        'reinscription_validated_by',
        'est_transfert',
        'etablissement_origine',
    ];

    /**
     * Événements à auditer.
     *
     * @var array
     */
    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_inscriptions';

    /**
     * Hooks soft-delete / restore : cascade sur les paiements actifs.
     *
     * Pourquoi : sans cascade, soft-delete d'une inscription laisse ses paiements
     * actifs orphelins. Conséquences observées (audit 2026-06-04) :
     * - Discrepancy KPIs : stats compte le paiement (annee_universitaire_id direct),
     *   dashboard-kpis ne le compte pas (whereHas inscription échoue car soft-deleted).
     * - Risque comptable : paiement validé reste compté dans le total encaissé alors
     *   que l'inscription parente n'existe plus.
     *
     * Comportement : on soft-delete (ou restaure) les paiements actifs en cascade.
     * Les paiements DÉJÀ soft-deletés ou supprimés indépendamment ne sont PAS touchés
     * lors d'un restore (on ne ressuscite que ceux que NOTRE soft-delete a tués).
     */
    /**
     * Réglage d'instance : matérialiser le dossier à la création d'une
     * inscription. Une école qui importe des milliers d'inscriptions d'un coup
     * peut le couper le temps de l'import puis rattraper par la commande.
     */
    public const REGLAGE_MATERIALISATION_AUTO = 'dossier.materialisation_auto';

    protected static function booted(): void
    {
        static::deleting(function (self $inscription) {
            if ($inscription->isForceDeleting()) {
                return;
            }
            $now = now();
            $inscription->paiements()
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => $now,
                    'updated_at' => $now,
                ]);
        });

        static::restoring(function (self $inscription) {
            // Restaurer TOUS les paiements soft-deletés attachés à l'inscription
            // (Marcel 5 juin 2026 : pas de fenêtre ±2 min — UX intuitive,
            // cohérent avec ce qu'affiche le dialog cascading_restore corbeille).
            $inscription->paiements()
                ->onlyTrashed()
                ->update([
                    'deleted_at' => null,
                    'updated_at' => now(),
                ]);
        });

        static::created(function (self $inscription) {
            $inscription->materialiserDossier();
        });
    }

    /**
     * Crée les lignes de dossier manquantes depuis le catalogue applicable.
     *
     * Silencieux par construction : tant qu'une école n'a rien mis dans son
     * catalogue, rien n'est créé et aucun écran ne change. Le dossier ne doit
     * jamais empêcher une inscription d'exister, donc un échec est journalisé
     * (jamais avalé en silence) mais n'interrompt pas l'enregistrement.
     */
    public function materialiserDossier(?int $auteurId = null): int
    {
        // filter_var plutot qu'un simple test de verite : selon le `type` de la
        // ligne de reglage, la valeur peut arriver en booleen, en "0" ou en
        // "false" — et "false" est une chaine non vide, donc vraie.
        $actif = filter_var(
            SettingsHelper::get(self::REGLAGE_MATERIALISATION_AUTO, true),
            FILTER_VALIDATE_BOOLEAN
        );

        if (! $actif) {
            return 0;
        }

        try {
            return app(MaterialisationPiecesService::class)
                ->materialiser($this, $auteurId ?? auth()->id());
        } catch (\Throwable $e) {
            Log::error('Dossier : materialisation impossible pour l\'inscription '.$this->id, [
                'inscription_id' => $this->id,
                'message' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Pièces du dossier constituées pour CETTE inscription.
     *
     * Une par année : un étudiant de troisième année a trois jeux de lignes,
     * un par inscription, et c'est le besoin — l'école reprend un exemplaire
     * de chaque pièce à chaque rentrée pour les ministères.
     */
    public function piecesDossier()
    {
        return $this->hasMany(ESBTPInscriptionPiece::class, 'inscription_id');
    }

    /**
     * Nombre de pièces encore dues : ni fournies, ni écartées, et toujours
     * exigées par le catalogue. C'est ce compteur que la fiche affiche —
     * une pièce manquante signale, elle ne bloque pas la validation.
     */
    public function nombrePiecesDues(bool $obligatoiresSeulement = true): int
    {
        $query = $this->piecesDossier()->dues();

        if ($obligatoiresSeulement) {
            $query->obligatoires();
        }

        return $query->count();
    }

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
        'date_inscription',
        'type_inscription', // Première inscription, réinscription, etc.
        'is_redoublant', // Réinscription sur le même niveau d'étude
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
        // 'documents_fournis' retiré : colonne morte, jamais lue ni écrite.
        // L'état des pièces vit désormais dans esbtp_inscription_pieces.
        'date_validation',
        'validated_by',
        'created_by',
        'updated_by',
        'reinscription_status',
        'reinscription_validated_at',
        'reinscription_validated_by',
        'reinscription_observations',
        'est_transfert',
        'etablissement_origine',
        'statut_etablissement',
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
        'montant_scolarite' => 'float',
        'frais_inscription' => 'float',
        'comptabilite_activee' => 'boolean',
        'is_sous_reserve' => 'boolean',
        'affectation_status' => 'string',
        'est_transfert' => 'boolean',
        'is_redoublant' => 'boolean',
    ];

    // Constants for affectation status
    const DEFAULT_AFFECTATION_STATUS = 'affecté';

    public const STATUT_ETABLISSEMENT_NOUVEAU = 'nouveau';

    public const STATUT_ETABLISSEMENT_ANCIEN = 'ancien';

    public function affectationStatusLabel(): string
    {
        $status = $this->affectation_status ?: self::DEFAULT_AFFECTATION_STATUS;
        $normalized = ESBTPEcheancierRule::normalizeStatus($status);

        return match ($normalized) {
            ESBTPEcheancierRule::STATUS_AFFECTE => 'Affecté',
            ESBTPEcheancierRule::STATUS_REAFFECTE => 'Réaffecté',
            ESBTPEcheancierRule::STATUS_NON_AFFECTE => 'Non affecté',
            default => $status,
        };
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
     * Frais souscriptions liées à cette inscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fraisSubscriptions()
    {
        return $this->hasMany(ESBTPFraisSubscription::class, 'inscription_id');
    }

    /**
     * Snapshot du plan d'échéancier figé pour cette inscription.
     */
    public function echeancierSnapshot()
    {
        return $this->hasOne(ESBTPInscriptionEcheancierSnapshot::class, 'inscription_id');
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
        return ESBTPPaiement::netPaidForInscription((int) $this->id);
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
        $anneeEnCours = ESBTPAnneeUniversitaire::getCurrent();

        if (!$anneeEnCours) {
            return $query->whereRaw('1=0'); // Retourne une requête vide si aucune année en cours
        }

        return $query->where('annee_universitaire_id', $anneeEnCours->id);
    }

    /**
     * Scope : inscriptions en attente de validation.
     *
     * Couvre deux cas :
     *  - status explicite « en_attente » / « pending »
     *  - status = active mais workflow_step pas encore complété
     *
     * Utilisé par les widgets dashboard et l'index inscriptions.
     */
    public function scopePendingValidation($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('status', ['en_attente', 'pending'])
              ->orWhere(function ($subQ) {
                  $subQ->where('status', 'active')
                       ->where(function ($wq) {
                           $wq->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation'])
                              ->orWhereNull('workflow_step');
                       });
              });
        });
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

    public function phases()
    {
        return $this->hasMany(ESBTPInscriptionPhase::class, 'inscription_id')
            ->orderBy('semestre_debut')
            ->orderBy('id');
    }

    public function activePhase()
    {
        return $this->hasOne(ESBTPInscriptionPhase::class, 'inscription_id')
            ->where('is_active', true)
            ->latestOfMany('id');
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

    /**
     * Cet etudiant a-t-il deja une inscription VIVANTE pour cette annee ?
     *
     * Definition unique de l'invariant qui empeche la double facturation : le
     * portail public s'en sert pour refuser un depot, la scolarite pour
     * refuser une conversion. Les deux doivent repondre la meme chose, donc
     * ils posent la meme question au meme endroit.
     *
     * « Vivante » exclut « annulee » : une inscription annulee ne facture rien,
     * et la compter condamnerait definitivement un dossier legitime — invisible
     * au portail, refuse a la conversion, sans qu'aucun ecran n'explique
     * pourquoi. Elle exclut aussi « terminee », etat d'une annee achevee.
     */
    public static function aUneInscriptionVivantePour(int $etudiantId, int $anneeUniversitaireId): bool
    {
        return static::where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('status', ['en_attente', 'active'])
            ->exists();
    }

    /**
     * Inscription de l'annee qui precede chronologiquement l'annee cible.
     *
     * Le tri se fait sur la date de debut, jamais sur l'identifiant : chez
     * ESBTP Abidjan l'annee 2023-2024 porte l'identifiant 3 quand 2024-2025
     * porte l'identifiant 1. Un tri par identifiant designerait la mauvaise
     * annee de reference.
     *
     * `start_date` etant nullable sur cette table, une annee cible sans date
     * rendrait la comparaison SQL indeterminee et donc silencieusement fausse
     * pour tout le monde. Ce cas est journalise et traite comme « reference
     * inconnue » plutot que comme « pas de redoublement » implicite.
     *
     * Aucun filtre sur le statut : la question posee est factuelle — a quel
     * niveau cet etudiant etait-il l'annee d'avant — et non administrative.
     * Une inscription « terminee » est l'etat normal d'une annee achevee, donc
     * le cas dominant. Une inscription « annulee » compte aussi aujourd'hui ;
     * le jour ou max_redoublements sera applique, il faudra demander aux ecoles
     * si une annee annulee consomme un droit au redoublement. Les inscriptions
     * reellement supprimees sont deja ecartees par SoftDeletes.
     */
    public static function precedantAnnee(int $etudiantId, ESBTPAnneeUniversitaire $anneeCible): ?self
    {
        $debutCible = $anneeCible->start_date;

        if ($debutCible === null) {
            \Log::warning("Annee universitaire sans date de debut : reference de redoublement indeterminable", [
                'annee_universitaire_id' => $anneeCible->id,
                'etudiant_id' => $etudiantId,
            ]);

            return null;
        }

        return static::query()
            ->join('esbtp_annee_universitaires as au', 'au.id', '=', 'esbtp_inscriptions.annee_universitaire_id')
            ->where('esbtp_inscriptions.etudiant_id', $etudiantId)
            ->where('esbtp_inscriptions.annee_universitaire_id', '!=', $anneeCible->id)
            ->whereNotNull('au.start_date')
            ->where('au.start_date', '<', $debutCible)
            ->orderByDesc('au.start_date')
            ->orderByDesc('esbtp_inscriptions.id')
            ->select('esbtp_inscriptions.*')
            ->first();
    }

    /**
     * Redoubler, c'est rester au niveau d'etude qu'on occupait l'annee
     * precedente. Definition unique du domaine : les deux portes d'entree
     * d'une reinscription (le service de reinscription et la pre-inscription
     * en caisse) l'appellent, pour qu'un meme etudiant ne soit pas marque
     * differemment selon le guichet par lequel il est passe.
     *
     * @param  self|null  $inscriptionPrecedente  Inscription de l'annee quittee.
     * @param  int|null   $niveauCible            Niveau de la classe visee.
     */
    public static function estUnRedoublement(?self $inscriptionPrecedente, ?int $niveauCible): bool
    {
        if ($inscriptionPrecedente === null || $niveauCible === null) {
            return false;
        }

        if ($inscriptionPrecedente->niveau_id === null) {
            return false;
        }

        return (int) $inscriptionPrecedente->niveau_id === (int) $niveauCible;
    }
}
