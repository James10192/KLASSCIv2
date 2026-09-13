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

    protected $attributes = [
        'nature' => 'encaissement',
    ];

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
        'relance_id',
        // PR2 réconciliation
        'reconciliation_locked_at',
        'last_reconciliation_session_id',
        'nature',
        'avoir_kind',
        'parent_paiement_id',
        'numero_avoir',
        // Correction d'imputation : la date vit sur le versement pour que le
        // recu puisse se declarer rectifie sans interroger le journal d'audit.
        'ventilation_rectifiee_le',
        // Suppression : qui, et pourquoi. Écrits juste avant deleted_at.
        'deleted_by',
        'motif_suppression',
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
        'target_due_line_key',
        'nature',
        'avoir_kind',
        'parent_paiement_id',
        'numero_avoir',
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
        'ventilation_rectifiee_le' => 'datetime',
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
     * Comment nommer ce versement quand on l'affiche en une ligne.
     *
     * Un paiement peut couvrir plusieurs frais. Afficher la seule categorie
     * portee par la colonne `frais_category_id` laisserait croire que tout
     * l'argent y est alle, alors qu'il a ete reparti — c'est precisement le
     * malentendu que la repartition existe pour lever.
     *
     * Un versement non reparti garde donc le nom de sa categorie ; un versement
     * reparti annonce combien de frais il couvre, et la fiche du paiement en
     * donne le detail.
     */
    public function getLibelleCategorieAttribute(): string
    {
        return $this->ventilation()->pluck('nom')->implode(' + ');
    }

    /**
     * La repartition de ce versement sur les differents frais.
     *
     * Vide pour un paiement historique : sa categorie unique fait alors foi.
     */
    public function allocations()
    {
        return $this->hasMany(ESBTPPaiementAllocation::class, 'paiement_id');
    }

    /**
     * Qui signe le recu.
     *
     * Celui qui l'a EMIS, donc celui qui a encaisse. Le cachet engage la
     * personne qui a recu l'argent et remis le papier, pas celle qui a coche la
     * validation ensuite — souvent un autre poste, parfois un autre jour. C'est
     * le meme nom que « Encaisse par », et c'est voulu.
     *
     * Ici plutot que dans les gabarits : la meme expression y etait copiee
     * trois fois — recu, apercu, avoir. Trois copies d'une regle qui doit rester
     * identique, dont la divergence ne se verrait sur aucun test, et seulement
     * sur un papier imprime.
     *
     * Le repli suit l'ordre de ce qu'on sait : l'emetteur, sinon le validateur
     * pour les recus anciens qui n'ont pas de createur, sinon la fonction.
     */
    public function getSignataireAttribute(): string
    {
        return $this->creator->name
            ?? $this->validatedBy->name
            ?? 'Le Comptable';
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

    public function scopeEncaissements($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('nature')->orWhere('nature', 'encaissement');
        });
    }

    /**
     * Ecarte les versements « reliquat », qui eteignent une dette d'une annee
     * anterieure et non un frais de l'annee en cours.
     *
     * Un reliquat porte l'inscription COURANTE et la `frais_category_id` de la
     * dette d'origine, mais ce qu'il solde vit dans
     * `esbtp_reliquat_details.montant_restant`. L'imputer EN PLUS au frais
     * courant de meme categorie le compterait deux fois, et donnerait pour
     * solde un frais de l'annee alors que c'est l'arriere qui a ete regle.
     *
     * Cette condition existait recopiee a huit endroits. La huitieme — celle du
     * service qui ECRIT la repartition — l'avait oubliee : un reliquat devenait
     * candidat, consommait du reste et recevait une allocation que le lecteur
     * jetait ensuite. Le versement reel qui aurait du couvrir ce frais etait
     * alors impute ailleurs. Une condition recopiee est une condition qu'on
     * finit par oublier quelque part : elle vit desormais ici, une seule fois.
     *
     * `type_paiement` est nullable : un versement sans type n'est pas un
     * reliquat, il doit rester dans le perimetre.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHorsReliquat($query)
    {
        return $query->where(fn ($q) => $q->where('type_paiement', '!=', 'reliquat')
            ->orWhereNull('type_paiement'));
    }

    public function scopeAvoires($query)
    {
        return $query->where('nature', 'avoir');
    }

    public function isAvoir(): bool
    {
        return $this->nature === 'avoir';
    }

    public function getAvoirDisponibleAttribute(): float
    {
        if ($this->isAvoir() || $this->status !== 'validé') {
            return 0.0;
        }

        $used = $this->relationLoaded('childAvoirs')
            ? (float) $this->childAvoirs->where('status', 'validé')->sum('montant')
            : (float) $this->childAvoirs()->valides()->sum('montant');

        return max(0.0, (float) $this->montant - $used);
    }

    public function parentPaiement()
    {
        return $this->belongsTo(self::class, 'parent_paiement_id');
    }

    public function childAvoirs()
    {
        return $this->hasMany(self::class, 'parent_paiement_id');
    }

    public function scopeCashMovements($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($inner) {
                $inner->whereNull('nature')->orWhere('nature', 'encaissement');
            })->orWhere(function ($inner) {
                $inner->where('nature', 'avoir')->where('avoir_kind', 'refund');
            });
        });
    }

    public static function netPaidForInscription(int $inscriptionId, ?int $categoryId = null, bool $includePending = false): float
    {
        // Sur UN frais precis, c'est netPaidByCategory() qui sait repondre.
        //
        // Filtrer sur `frais_category_id` ignore les allocations : un versement
        // reparti sur plusieurs frais porte toujours la categorie que le caissier
        // avait designee, et les autres frais semblent alors n'avoir rien reçu.
        //
        // Ce n'est pas qu'un defaut d'affichage. Cette methode arme le garde-fou
        // d'encaissement (ESBTPInscriptionPaiementController) : un frais deja
        // solde par repartition ressortait a zero paye, donc entierement du, et
        // la caisse invitait a l'encaisser une SECONDE FOIS. Elle alimente aussi
        // l'ecriture des reliquats de reinscription, qui reportait sur l'annee
        // suivante une dette deja eteinte.
        if ($categoryId) {
            return (float) (self::netPaidByCategory($inscriptionId, $includePending)[$categoryId] ?? 0);
        }

        $query = self::query()->where('inscription_id', $inscriptionId);
        if ($includePending) {
            $query->whereIn('status', ['validé', 'en_attente']);
        } else {
            $query->valides();
        }

        // `horsReliquat()` ici aussi, et pas seulement dans la branche par
        // categorie ci-dessus. Celle-ci passe par MontantsParFrais, qui l'applique
        // (MontantsParFrais:100) ; celle-la construisait sa propre requete et
        // l'oubliait. La MEME fonction rendait donc deux reponses differentes
        // selon qu'on lui passait une categorie ou non.
        //
        // Un versement de reliquat porte `type_paiement = 'reliquat'` et
        // `inscription_id` = l'inscription de DESTINATION : il transite par
        // l'annee en cours, mais il eteint une dette de l'annee precedente. Le
        // compter ici le faisait passer pour un paiement de la scolarite
        // courante. Un etudiant reglant 250 000 d'arriere ressortait crediteur
        // sur son annee, et `peutSeReinscrire()` — qui appelle cette branche —
        // lui ouvrait l'annee suivante alors qu'il devait encore la sienne.
        $encaisse = (float) (clone $query)->horsReliquat()->encaissements()->sum('montant');
        $avoirs = (float) (clone $query)->horsReliquat()->avoires()->valides()->sum('montant');

        return max(0.0, $encaisse - $avoirs);
    }

    public static function netCashSum($query): float
    {
        $encaisse = (float) (clone $query)->encaissements()->sum('montant');
        $refunds = (float) (clone $query)->avoires()->where('avoir_kind', 'refund')->sum('montant');

        return $encaisse - $refunds;
    }

    public static function netStudentPaidSum($query): float
    {
        $encaisse = (float) (clone $query)->encaissements()->sum('montant');
        $avoirs = (float) (clone $query)->avoires()->sum('montant');

        return max(0.0, $encaisse - $avoirs);
    }

    public static function netCashFrom($paiements): float
    {
        $items = collect($paiements);
        $encaisse = (float) $items->filter(fn ($p) => ! $p->isAvoir())->sum('montant');
        $refunds = (float) $items->filter(fn ($p) => $p->isAvoir() && ($p->avoir_kind ?? '') === 'refund')->sum('montant');

        return $encaisse - $refunds;
    }

    public static function netStudentPaidFrom($paiements): float
    {
        $items = collect($paiements);
        $encaisse = (float) $items->filter(fn ($p) => ($p->status ?? '') === 'validé' && ! $p->isAvoir())->sum('montant');
        $avoirs = (float) $items->filter(fn ($p) => ($p->status ?? '') === 'validé' && $p->isAvoir())->sum('montant');

        return max(0.0, $encaisse - $avoirs);
    }

    public static function pendingEncaissementsFrom($paiements): float
    {
        return (float) collect($paiements)
            ->filter(fn ($p) => ($p->status ?? '') === 'en_attente' && ! $p->isAvoir())
            ->sum('montant');
    }

    public static function sqlStudentPaidCase(): string
    {
        return "CASE WHEN COALESCE(nature, 'encaissement') = 'avoir' THEN -montant ELSE montant END";
    }

    public static function sqlCashCase(): string
    {
        return "CASE WHEN COALESCE(nature, 'encaissement') <> 'avoir' THEN montant WHEN avoir_kind = 'refund' THEN -montant ELSE 0 END";
    }

    /**
     * Ce que chaque frais a reellement encaisse.
     *
     * Un versement peut se repartir sur plusieurs frais — c'est le cas des qu'un
     * etudiant paie une somme couvrant son inscription, sa scolarite et sa
     * ramette d'un seul geste. Les allocations disent alors ou l'argent est alle.
     *
     * Un paiement sans allocation garde son comportement historique : sa
     * categorie unique fait foi et le versement entier lui revient. C'est ce qui
     * permet d'introduire la repartition sans rien deplacer de l'existant.
     *
     * `$saufPaiementId` retire UN versement du compte. Il sert a rejouer une
     * imputation deja ecrite : reventiler un versement demande ce que les frais
     * reclament SANS lui, sinon il se compare a lui-meme et se refuse sa propre
     * part — un versement de 150 000 F qui solde la scolarite verrait la
     * scolarite a zero de reste, et ne pourrait plus y etre impute.
     */
    public static function netPaidByCategory(
        int $inscriptionId,
        bool $includePending = false,
        ?int $saufPaiementId = null
    ): \Illuminate\Support\Collection {
        $montants = app(\App\Services\Frais\MontantsParFrais::class);

        $encaisse = $montants->parFrais(self::versementsDe(
            $inscriptionId,
            'encaissements',
            $includePending ? ['validé', 'en_attente'] : ['validé'],
            $saufPaiementId
        ));
        // Un avoir ne compte que valide, meme quand on inclut les encaissements
        // en attente : un remboursement pas encore valide n'a pas quitte la caisse.
        //
        // L'exclusion vaut ici aussi : l'identifiant est unique toutes natures
        // confondues, donc exclure un encaissement ne retire aucun avoir. La
        // passer quand meme evite qu'un futur appel sur un avoir oublie la
        // moitie du filtre.
        $avoirs = $montants->parFrais(self::versementsDe(
            $inscriptionId,
            'avoires',
            ['validé'],
            $saufPaiementId
        ));

        return $encaisse->map(function ($total, $categoryId) use ($avoirs) {
            return max(0.0, (float) $total - (float) ($avoirs[$categoryId] ?? 0));
        });
    }

    /**
     * Additionne par categorie, en prenant les allocations quand il y en a.
     *
     * Deux sources, jamais comptees deux fois : les paiements QUI PORTENT des
     * allocations sont lus par leurs allocations, ceux qui n'en portent pas par
     * leur categorie propre.
     *
     * Cette methode ne peut ignorer la categorie propre d'un versement alloue
     * que si ses allocations couvrent la TOTALITE du montant : sinon la
     * difference sort des totaux sans erreur ni trace. Cet invariant n'etait
     * qu'affirme ici ; il est desormais verifie a l'ecriture
     * (RepartitionTropPercu leve AllocationIncoherenteException) et
     * controlable a tout moment par `php artisan frais:verifier-allocations`.
     */
    /**
     * Les versements d'une inscription, dans le perimetre que la repartition
     * ecrit elle-meme : meme statut, meme nature, meme exclusion des reliquats.
     *
     * Un versement candidat cote ecriture mais hors perimetre cote lecture
     * recevrait une allocation invisible, et priverait au passage un versement
     * reel du frais qu'il aurait du couvrir.
     */
    private static function versementsDe(
        int $inscriptionId,
        string $nature,
        array $statuts,
        ?int $saufPaiementId = null
    ): \Illuminate\Database\Eloquent\Builder {
        return self::query()
            ->where('inscription_id', $inscriptionId)
            ->whereIn('status', $statuts)
            ->{$nature}()
            ->horsReliquat()
            // Le versement qu'on est en train de reventiler ne compte pas dans
            // ce qu'il doit encore couvrir.
            ->when($saufPaiementId !== null, fn ($q) => $q->where('id', '!=', $saufPaiementId));
    }

    /**
     * Les versements qui concernent CE frais.
     *
     * Porte d'entree Eloquent vers la regle unique
     * ({@see \App\Services\Frais\MontantsParFrais}) : un scope se chaine
     * naturellement dans une requete, un service non.
     */
    public function scopePourCategorie($query, $categoryId)
    {
        return app(\App\Services\Frais\MontantsParFrais::class)->filtrer($query, (int) $categoryId);
    }

    /**
     * Ce que CE versement a porte sur CE frais.
     */
    public function partPourCategorie(int $categoryId): float
    {
        return app(\App\Services\Frais\MontantsParFrais::class)->part($this, $categoryId);
    }

    /**
     * La ventilation de ce versement, sous forme affichable.
     *
     * Rend toujours au moins une ligne — un versement non reparti vaut sa
     * propre categorie. Les quatre surfaces qui l'affichent (ligne de liste,
     * PDF, Excel, recu) partagent ainsi la meme lecture, au lieu de redecouvrir
     * chacune le repli vers la categorie du guichet.
     *
     * @return \Illuminate\Support\Collection<int, array{frais_id: int|null, nom: string, montant: float, type: string}>
     */
    public function ventilation(): \Illuminate\Support\Collection
    {
        return app(\App\Services\Frais\MontantsParFrais::class)->ventilation($this);
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
