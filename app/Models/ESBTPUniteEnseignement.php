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
    /**
     * `parcours_id` fait partie du pivot : sans lui dans `withPivot`, la lecture
     * de `$ecue->pivot->parcours_id` rend null SANS lever d'erreur, et tout
     * element reserve a une maquette se lirait comme commun — il fuirait dans
     * toutes les autres. C'est une omission qui ne se voit qu'a l'usage.
     *
     * Cette relation ne sert QU'A LIRE : elle ne connait que le couple
     * (unite, matiere), donc `attach`/`detach`/`syncWithoutDetaching` y visent la
     * mauvaise ligne des que la maquette entre dans la cle. Les ecritures passent
     * par App\Services\LMD\CompositionUeService.
     */
    public function ecues()
    {
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
     *
     * $parcoursId est la maquette depuis laquelle on lit : elle ne voit que le
     * commun et ce qui lui est réservé. Null — la valeur par défaut — rend la
     * composition entière, c'est-à-dire exactement ce que cette méthode rendait
     * avant que la maquette entre dans le pivot : aucun appelant existant ne
     * change de comportement tant qu'il ne transmet pas de maquette.
     */
    public function getEcuesEffectifs(?int $parcoursId = null): \Illuminate\Support\Collection
    {
        $pivotEcues = $this->ecues->values();

        // Une même matière peut désormais apparaître plusieurs fois dans le
        // pivot — une ligne commune et une ligne réservée. Sans cette résolution
        // elle serait comptée deux fois dans la moyenne de l'UE et deux fois
        // dans ses crédits, sans qu'aucune erreur ne soit levée.
        $lignes = [];
        foreach ($pivotEcues as $rang => $ecue) {
            $lignes[] = [
                'matiere_id' => (int) $ecue->id,
                'parcours_id' => (int) ($ecue->pivot->parcours_id ?? \App\Services\LMD\CompositionUeService::TOUTES_MAQUETTES),
                'rang' => $rang,
            ];
        }

        $retenues = \App\Services\LMD\CompositionUeService::resoudre($lignes, $parcoursId);
        $ecuesRetenus = collect($retenues)->map(fn ($ligne) => $pivotEcues[$ligne['rang']]);

        // Le repli par clé étrangère se tait dès que le pivot parle de cette
        // matière dans cette unité — y compris quand la maquette lue ne la voit
        // pas. Sans cela, un élément réservé à Bâtiment reviendrait dans Travaux
        // Publics par la clé étrangère, qui n'a aucune notion de maquette.
        $idsPivot = $pivotEcues->pluck('id')->map(fn ($id) => (int) $id)->all();

        $parCleEtrangere = $this->matieres
            ->where('is_active', true)
            ->reject(fn ($matiere) => in_array((int) $matiere->id, $idsPivot, true));

        return $ecuesRetenus->concat($parCleEtrangere->values())->values();
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
        )->withPivot('semestre', 'is_optional', 'ordre')->withTimestamps();
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