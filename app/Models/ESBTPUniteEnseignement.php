<?php

namespace App\Models;

use App\Enums\TypeUE;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPUniteEnseignement extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    /**
     * Valeur de `esbtp_ue_matiere.parcours_id` qui signifie « commun ».
     *
     * Zero, et non NULL : voir la migration qui pose la colonne. Un element
     * commun est suivi par TOUTES les maquettes qui portent cette unite.
     */
    public const PARCOURS_COMMUN = 0;

    /**
     * Credit de cette unite DANS LA MAQUETTE EN COURS DE LECTURE.
     *
     * Propriete PHP declaree, et non attribut Eloquent : elle ne correspond a
     * aucune colonne de `esbtp_unites_enseignement` et ne doit jamais partir en
     * base. Elle est renseignee par la lecture qui connait le parcours — au
     * bulletin, `getUEsForSemestre()` la remplit depuis la ligne de pivot
     * qu'elle vient deja de lire, donc sans une requete de plus.
     */
    public ?int $creditParcours = null;

    /**
     * Whitelist des colonnes auditees (rule pre-merge-checklist + feedback Owen-IT).
     *
     * Sans cette whitelist, table audits exploserait avec metadata sur chaque
     * touch (updated_at, etc.). On ne suit que les colonnes business-critical.
     */
    protected array $auditInclude = [
        'name',
        'code',
        'credit',
        'semestre',
        'type_ue',
        'responsable_ue_id',
        'is_active',
    ];

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_unites_enseignement';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    /**
     * Les evenements reellement audites : les MUTATIONS, pas les lectures.
     *
     * `config/audit.php` active aussi `retrieved`, ce qui fait ecrire une ligne
     * dans `audits` a chaque fois qu'un modele est LU. Vingt et un modeles s'en
     * protegent deja par cette meme propriete ; ceux-ci ne le faisaient pas.
     *
     * Le cout n'etait pas theorique : c'est par ce canal que la table `audits` a
     * enfle au point que la page qui la consulte ne repondait plus.
     *
     * Ce qui reste trace : creation, modification, suppression, restauration.
     * La conservation OHADA porte sur les mutations, pas sur les consultations.
     */
    protected $auditEvents = ['created', 'updated', 'deleted', 'restored'];
    protected $fillable = [
        'name',
        'code',
        'description',
        'credit',
        'semestre',
        'type_ue',          // App\Enums\TypeUE — 7 catégories UEMOA
        'filiere_id',
        'niveau_id',
        'parcours_id',
        'responsable_ue_id', // FK users — directive UEMOA 03/2007/CM (1 responsable par UE)
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'credit' => 'integer',
        'semestre' => 'integer',
        'is_active' => 'boolean',
        'type_ue' => TypeUE::class,
    ];

    /**
     * Relation avec les matières appartenant à cette UE.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    /**
     * ECUEs via pivot (many-to-many) — avec coefficient/credit contextuels.
     * Prioritaire pour le bulletin quand le pivot existe.
     */
    public function ecues()
    {
        // `parcours_id` DOIT figurer ici. Sans lui, Eloquent n'expose pas la
        // colonne et `$ecue->pivot->parcours_id` rend null SANS LA MOINDRE
        // ERREUR : tout element reserve a un parcours se relit alors comme
        // commun et fuit dans les autres maquettes. La panne serait muette,
        // et c'est un test d'ABSENCE qui l'attrape, pas un test de presence.
        return $this->belongsToMany(ESBTPMatiere::class, 'esbtp_ue_matiere', 'unite_enseignement_id', 'matiere_id')
            ->withPivot('coefficient_ecue', 'credit_ecue', 'ordre_bulletin', 'parcours_id')
            ->withTimestamps();
    }

    /**
     * Matières liées par FK direct (rétro-compat HasMany).
     * Fallback quand le pivot n'est pas rempli.
     */
    public function matieres()
    {
        return $this->hasMany(ESBTPMatiere::class, 'unite_enseignement_id');
    }

    /**
     * Retourne les ECUEs de l'UE : l'UNION du pivot et de la clé étrangère.
     *
     * La priorité exclusive d'autrefois — « pivot s'il existe, sinon clé
     * étrangère » — rendait une UE définitivement aveugle dès sa première ligne
     * de pivot : l'import de maquettes n'écrit QUE `unite_enseignement_id`, donc
     * tout élément importé ensuite existait en base sans jamais apparaître au
     * bulletin, au relevé, ni au calcul des crédits. Même effet pour un élément
     * réactivé après coup. L'union supprime la bascule : chaque lien compte.
     *
     * Le pivot reste prioritaire là où il parle — il porte le coefficient, le
     * crédit et l'ordre propres à CETTE UE — et un élément présent des deux
     * côtés n'est retourné qu'une fois, dans sa version pivot.
     *
     * `is_active` n'est filtré que sur la voie clé étrangère, comme avant : le
     * pivot ne l'a jamais filtré, et le poser ici retirerait d'un bulletin déjà
     * délivré tout élément désactivé depuis. Le retrait passe par destroyECUE(),
     * qui détache le pivot ET libère la clé — c'est le geste qui fait foi.
     */
    public function getEcuesEffectifs(?int $parcoursId = null): \Illuminate\Support\Collection
    {
        $pivotEcues = $this->dedupliquerLiensPivot($this->ecues, $parcoursId);
        $idsPivot = $pivotEcues->pluck('id')->all();

        $parCleEtrangere = $this->matieres
            ->where('is_active', true)
            ->reject(fn ($matiere) => in_array($matiere->id, $idsPivot, true));

        return $pivotEcues->concat($parCleEtrangere->values())->values();
    }

    /**
     * Retient une seule ligne de pivot par element constitutif, et ne garde que
     * celles qui concernent le parcours demande.
     *
     * Deux protections en une, parce qu'elles portent sur la meme collection.
     *
     * 1. LE FILTRE. Une ligne commune vaut pour toutes les maquettes ; une ligne
     *    reservee ne vaut que pour la sienne. Sans parcours demande, on ne
     *    filtre rien : c'est le comportement d'avant, celui des appelants qui
     *    ne savent pas encore dans quelle maquette ils lisent.
     *
     * 2. LA DEDUPLICATION. Rien n'interdit qu'un meme element porte a la fois
     *    une ligne commune et une ligne reservee. Il reviendrait alors DEUX
     *    FOIS : sa note serait comptee deux fois dans la moyenne de l'unite et
     *    son credit deux fois dans le total. Aucune erreur, juste un bulletin
     *    faux. La ligne la plus precise gagne — celle du parcours demande —
     *    parce que c'est celle que quelqu'un a explicitement posee. Hors
     *    contexte de parcours, on retient la commune : c'est le choix neutre,
     *    et surtout il est stable d'une lecture a l'autre.
     *
     * On travaille ici sur la collection DEJA CHARGEE. Rien ne doit repartir en
     * base : au bulletin, cette methode est appelee une fois par unite et par
     * etudiant — sur deux mille inscrits, une requete de plus serait ruineuse.
     */
    private function dedupliquerLiensPivot(
        \Illuminate\Support\Collection $liens,
        ?int $parcoursId
    ): \Illuminate\Support\Collection {
        $retenus = [];

        foreach ($liens as $ecue) {
            $parcoursDuLien = (int) ($ecue->pivot->parcours_id ?? self::PARCOURS_COMMUN);

            $concerne = $parcoursDuLien === self::PARCOURS_COMMUN
                || $parcoursId === null
                || $parcoursDuLien === $parcoursId;

            if (! $concerne) {
                continue;
            }

            $id = (int) $ecue->id;

            if (! isset($retenus[$id])) {
                $retenus[$id] = $ecue;
                continue;
            }

            $dejaRetenu = (int) ($retenus[$id]->pivot->parcours_id ?? self::PARCOURS_COMMUN);

            // Le lien reserve supplante le commun quand on lit une maquette
            // precise ; sinon c'est l'inverse, pour rester deterministe.
            $remplace = $parcoursId !== null
                ? ($parcoursDuLien === $parcoursId && $dejaRetenu !== $parcoursId)
                : ($parcoursDuLien === self::PARCOURS_COMMUN && $dejaRetenu !== self::PARCOURS_COMMUN);

            if ($remplace) {
                $retenus[$id] = $ecue;
            }
        }

        return collect(array_values($retenus));
    }

    /**
     * Credit a retenir pour cette unite, maquette comprise.
     *
     * La maquette prime quand elle s'est prononcee ; sinon c'est le credit de
     * l'unite, comme avant. Tant que personne ne renseigne de credit par
     * parcours, cette methode rend exactement `credit` : rien ne bouge dans
     * les bulletins deja produits.
     */
    public function creditEffectif(): int
    {
        return (int) ($this->creditParcours ?? $this->credit ?? 0);
    }

    /**
     * Relation avec la filière associée.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    /**
     * Relation avec le niveau d'études associé.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function niveau()
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_id');
    }

    public function parcours()
    {
        return $this->belongsTo(ESBTPLMDParcours::class, 'parcours_id');
    }

    /**
     * Relation Responsable de l'UE (directive UEMOA 03/2007/CM).
     *
     * 1 responsable par UE — distinct des Enseignants charges ECUE qui sont
     * stockes sur esbtp_planifications_academiques.enseignant_principal_id.
     *
     * Le responsable doit avoir le role enseignant (verifie cote controller
     * via teacherExists() — pas en model pour eviter une dependance Spatie ici).
     */
    public function responsableUe()
    {
        return $this->belongsTo(User::class, 'responsable_ue_id');
    }

    /**
     * Parcours associes via table pivot (many-to-many).
     */
    public function parcoursMultiple()
    {
        return $this->belongsToMany(
            ESBTPLMDParcours::class,
            'esbtp_lmd_parcours_ue',
            'unite_enseignement_id',
            'parcours_id'
        )->withPivot('semestre', 'is_optional', 'ordre', 'credit')->withTimestamps();
    }

    /**
     * Resultats UE pour les bulletins LMD.
     */
    public function resultatsLMD()
    {
        return $this->hasMany(ESBTPLMDResultatUE::class, 'unite_enseignement_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySemestre($query, int $semestre)
    {
        return $query->where('semestre', $semestre);
    }

    /**
     * Calculer la moyenne de l'UE pour un étudiant et un semestre donnés.
     *
     * @param int $etudiantId ID de l'étudiant
     * @param int $semestreId ID du semestre
     * @param int $classeId ID de la classe (optionnel)
     * @return array Tableau contenant la moyenne, les détails des notes et le statut
     */
    public function calculerMoyenne($etudiantId, $semestreId, $classeId = null)
    {
        // Initialisation des variables
        $totalPoints = 0;
        $totalCoefficients = 0;
        $detailsMatieres = [];
        $statutUE = 'non_valide';
        
        // Récupérer les matières de cette UE
        $matieres = $this->matieres()->where('is_active', true)->get();
        
        foreach ($matieres as $matiere) {
            // Déterminer le coefficient selon la classe si disponible
            $coefficient = $classeId 
                ? $matiere->getCoefficientForClasse($classeId) 
                : $matiere->coefficient_default;
            
            // Calculer la moyenne de l'étudiant pour cette matière dans ce semestre
            $moyenneMatiere = ESBTPNote::calculerMoyenneMatiere(
                $etudiantId, 
                $matiere->id, 
                $semestreId
            );
            
            if ($moyenneMatiere !== null) {
                // Ajouter les points pondérés à la somme
                $totalPoints += $moyenneMatiere * $coefficient;
                $totalCoefficients += $coefficient;
                
                // Stocker les détails pour cette matière
                $detailsMatieres[] = [
                    'matiere_id' => $matiere->id,
                    'matiere_nom' => $matiere->name,
                    'matiere_code' => $matiere->code,
                    'moyenne' => $moyenneMatiere,
                    'coefficient' => $coefficient,
                    'points' => $moyenneMatiere * $coefficient,
                ];
            }
        }
        
        // Calculer la moyenne finale
        $moyenne = $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : null;
        
        // Déterminer si l'UE est validée (moyenne >= 10)
        if ($moyenne !== null && $moyenne >= 10) {
            $statutUE = 'valide';
        }
        
        return [
            'moyenne' => $moyenne,
            'details_matieres' => $detailsMatieres,
            'total_coefficients' => $totalCoefficients,
            'total_points' => $totalPoints,
            'statut' => $statutUE,
        ];
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
} 