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
     * Crédit gravé par la maquette en cours de lecture, quand elle en grave un.
     *
     * Une unité partagée entre deux parcours garde UNE fiche — donc UN
     * `credit` — mais chaque maquette peut lui donner le sien, sur la ligne de
     * pivot `esbtp_lmd_parcours_ue` qui la relie à ce parcours et à ce
     * semestre. C'est le chargeur des UE d'une classe qui pose cette valeur
     * ici, parce que lui seul sait quelle maquette est lue.
     *
     * Propriété PHP déclarée, et non attribut Eloquent : un attribut inventé
     * finirait dans un `save()` et chercherait une colonne qui n'existe pas.
     * Ici, rien ne peut la persister.
     *
     * `null` signifie « cette maquette ne grave pas de crédit », et se
     * distingue de `0`, qui est une décision de l'école — une unité qui ne
     * rapporte aucun crédit dans ce parcours-là.
     */
    public ?int $creditDeLaMaquette = null;

    /**
     * Le crédit à utiliser pour CETTE lecture : celui de la maquette s'il est
     * gravé, celui de la fiche sinon.
     *
     * Le `??` est ici légitime, à la différence de `$pivot->credit ?? $ue->credit`
     * que l'audit interdit : la valeur est posée à `null` UNIQUEMENT quand le
     * pivot n'en porte pas. Un pivot à `0` pose `0`, et `0 ?? x` vaut `0`.
     */
    public function creditEffectif(): int
    {
        return $this->creditDeLaMaquette ?? (int) $this->credit;
    }

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
        'ordre',            // repli d'affichage quand la classe n'a pas de parcours
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
        // `parcours_id` est declare ici, alors que rien ne le lit encore, parce
        // que son absence ne se voit pas : sans lui, `$ecue->pivot->parcours_id`
        // rend `null` SANS ERREUR, et tout element reserve a une maquette se
        // lirait comme commun — il fuiterait dans toutes les autres. Le declarer
        // des maintenant est sans effet (une colonne de plus au SELECT du pivot)
        // et supprime le piege pour de bon.
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
        // Les identifiants connus du pivot se relevent AVANT tout filtrage.
        //
        // Les calculer sur la collection filtree defaisait la regle trois lignes
        // plus bas : un element reserve au parcours B porte une ligne de pivot ET
        // la cle etrangere (l'import ecrit les deux). Lu pour le parcours A, il
        // etait bien ecarte du pivot, donc absent de cette liste, donc REPRIS par
        // le repli — et il entrait au bulletin des etudiants de A, sans son
        // pivot, donc avec le coefficient et le credit de la matiere au lieu de
        // ceux de la maquette. La contamination changeait de sens, elle ne
        // disparaissait pas.
        $idsPivot = $this->ecues->pluck('id')->all();

        $pivotEcues = $this->decouperParParcours($this->ecues, $parcoursId);

        // Le repli ne vaut que pour ce que le pivot ignore VRAIMENT.
        $parCleEtrangere = $this->matieres
            ->where('is_active', true)
            ->reject(fn ($matiere) => in_array($matiere->id, $idsPivot, true));

        return $pivotEcues->concat($parCleEtrangere->values())->values();
    }

    /**
     * Ne garder, pour chaque element, que la ligne qui vaut pour ce parcours.
     *
     * Une meme unite sert plusieurs maquettes : `parcours_id` a zero designe la
     * composition commune, une valeur non nulle une composition propre a un
     * parcours. Les deux ont le droit d'exister pour le meme element, et c'est
     * meme tout l'interet : l'ecole pose un coefficient commun, puis un parcours
     * le surcharge.
     *
     * Deux regles, dans cet ordre :
     *
     * 1. Les lignes reservees a un AUTRE parcours sont ecartees. Sans cela un
     *    element propre au Genie Civil apparaitrait au bulletin des juristes.
     * 2. Pour un element restant, la ligne reservee PRIME sur la commune. Sans
     *    cette regle les deux remonteraient et l'element serait compte deux fois
     *    dans `calculerResultatUE` : note doublee au numerateur, coefficient
     *    doublee au denominateur, credit doublee. La moyenne resterait juste par
     *    compensation, les credits non — et si les deux lignes portent des
     *    coefficients differents, la moyenne devient fausse elle aussi. Aucune
     *    erreur ne serait levee.
     *
     * Sans parcours (`null`), on garde tout : c'est l'ecran de l'unite, qui doit
     * montrer sa composition entiere, toutes maquettes confondues. Seul le
     * doublon exact y est reduit, le reserve d'abord.
     *
     * Le filtrage porte sur la collection DEJA chargee. Une requete par unite et
     * par etudiant couterait, sur une classe a huit unites et deux mille
     * inscrits, seize mille requetes ajoutees a la generation des bulletins.
     */
    private function decouperParParcours(
        \Illuminate\Support\Collection $ecues,
        ?int $parcoursId
    ): \Illuminate\Support\Collection {
        if ($parcoursId !== null) {
            $ecues = $ecues->filter(function ($ecue) use ($parcoursId) {
                $porte = (int) ($ecue->pivot->parcours_id ?? 0);

                return $porte === 0 || $porte === $parcoursId;
            });
        }

        if ($parcoursId === null) {
            // L'ecran de l'unite montre sa composition ENTIERE. Dedupliquer ici
            // ferait disparaitre la version du parcours 5 des que celle du 9
            // existe aussi — silencieusement, et sur le seul ecran dont c'est le
            // role de les montrer toutes.
            return $ecues->values();
        }

        // Une seule ligne par element : la reservee prime sur la commune. Sans
        // cette reduction les deux remonteraient et l'element serait compte DEUX
        // fois au bulletin — note doublee au numerateur, coefficient au
        // denominateur, credit doublee. La moyenne resterait juste par
        // compensation, les credits non, et deux coefficients differents la
        // fausseraient elle aussi. Aucune erreur ne serait levee.
        return $ecues
            ->groupBy('id')
            ->map(fn ($lignes) => $lignes->sortByDesc(
                fn ($ecue) => (int) ($ecue->pivot->parcours_id ?? 0)
            )->first())
            ->values();
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
        // `credit` : le poids en credits que CETTE maquette donne a l unite pour
        // CE semestre. `null` = pas de credit propre, on prend celui de l unite.
        // Meme raison de le declarer avant de le lire que pour `parcours_id`
        // ci-dessus : un pivot non declare rend null sans rien signaler.
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