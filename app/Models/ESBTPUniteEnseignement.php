<?php

namespace App\Models;

use App\Enums\TypeUE;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPUniteEnseignement extends Model
{
    use HasFactory, SoftDeletes;

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
        return $this->belongsToMany(ESBTPMatiere::class, 'esbtp_ue_matiere', 'unite_enseignement_id', 'matiere_id')
            ->withPivot('coefficient_ecue', 'credit_ecue', 'ordre_bulletin')
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
     * Retourne les ECUEs : pivot si disponible, sinon fallback HasMany.
     */
    public function getEcuesEffectifs(): \Illuminate\Support\Collection
    {
        $pivotEcues = $this->ecues;
        if ($pivotEcues->isNotEmpty()) {
            return $pivotEcues;
        }
        return $this->matieres->where('is_active', true);
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