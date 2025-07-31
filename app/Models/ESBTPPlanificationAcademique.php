<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPPlanificationAcademique extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     */
    protected $table = 'esbtp_planifications_academiques';

    /**
     * Les attributs qui sont assignables en masse.
     */
    protected $fillable = [
        'annee_universitaire_id',
        'filiere_id',
        'niveau_etude_id',
        'semestre',
        'matiere_id',
        'volume_horaire_total',
        'volume_horaire_cm', // Cours Magistraux
        'volume_horaire_td', // Travaux Dirigés  
        'volume_horaire_tp', // Travaux Pratiques
        'coefficient',
        'credits_ects',
        'periode_debut',
        'periode_fin',
        'enseignant_principal_id',
        'enseignants_secondaires', // JSON des enseignants secondaires
        'contraintes_pedagogiques', // JSON des contraintes spécifiques
        'objectifs_pedagogiques',
        'prerequis',
        'modalites_evaluation', // JSON des modalités d'évaluation
        'ressources_necessaires', // JSON des ressources (salles, matériel)
        'statut', // planifie, valide, archive
        'observations',
        'is_active',
        'created_by',
        'updated_by',
        'heures_effectuees',
        'derniere_mise_a_jour_heures'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     */
    protected $casts = [
        'periode_debut' => 'date',
        'periode_fin' => 'date',
        'volume_horaire_total' => 'integer',
        'volume_horaire_cm' => 'integer',
        'volume_horaire_td' => 'integer',
        'volume_horaire_tp' => 'integer',
        'coefficient' => 'decimal:2',
        'credits_ects' => 'integer',
        'enseignants_secondaires' => 'array',
        'contraintes_pedagogiques' => 'array',
        'modalites_evaluation' => 'array',
        'ressources_necessaires' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Statuts possibles pour la planification
     */
    const STATUT_BROUILLON = 'brouillon';
    const STATUT_PLANIFIE = 'planifie';
    const STATUT_VALIDE = 'valide';
    const STATUT_EN_COURS = 'en_cours';
    const STATUT_TERMINE = 'termine';
    const STATUT_ARCHIVE = 'archive';

    /**
     * Types de cours
     */
    const TYPE_CM = 'cm'; // Cours Magistral
    const TYPE_TD = 'td'; // Travaux Dirigés
    const TYPE_TP = 'tp'; // Travaux Pratiques
    const TYPE_STAGE = 'stage';
    const TYPE_PROJET = 'projet';

    /**
     * Relation avec l'année universitaire
     */
    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    /**
     * Relation avec la filière
     */
    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    /**
     * Relation avec le niveau d'étude
     */
    public function niveauEtude()
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_etude_id');
    }

    /**
     * Relation avec la matière
     */
    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    /**
     * Relation avec l'enseignant principal
     */
    public function enseignantPrincipal()
    {
        return $this->belongsTo(User::class, 'enseignant_principal_id');
    }

    /**
     * Relation avec l'utilisateur qui a créé la planification
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a modifié la planification
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relation avec les séances de cours générées à partir de cette planification
     */
    public function seances()
    {
        return $this->hasMany(ESBTPSeanceCours::class, 'planification_id');
    }

    /**
     * Scope pour filtrer par année universitaire
     */
    public function scopeForAnnee($query, $anneeId)
    {
        return $query->where('annee_universitaire_id', $anneeId);
    }

    /**
     * Scope pour filtrer par filière
     */
    public function scopeForFiliere($query, $filiereId)
    {
        return $query->where('filiere_id', $filiereId);
    }

    /**
     * Scope pour filtrer par niveau
     */
    public function scopeForNiveau($query, $niveauId)
    {
        return $query->where('niveau_etude_id', $niveauId);
    }

    /**
     * Scope pour filtrer par semestre
     */
    public function scopeForSemestre($query, $semestre)
    {
        return $query->where('semestre', $semestre);
    }

    /**
     * Scope pour les planifications actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les planifications par statut
     */
    public function scopeWithStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    /**
     * Obtenir le volume horaire total calculé
     */
    public function getVolumeHoraireTotalCalculeAttribute()
    {
        return ($this->volume_horaire_cm ?? 0) + 
               ($this->volume_horaire_td ?? 0) + 
               ($this->volume_horaire_tp ?? 0);
    }

    /**
     * Obtenir la répartition des volumes horaires en pourcentage
     */
    public function getRepartitionVolumeHoraireAttribute()
    {
        $total = $this->volume_horaire_total_calcule;
        
        if ($total == 0) {
            return ['cm' => 0, 'td' => 0, 'tp' => 0];
        }

        return [
            'cm' => round(($this->volume_horaire_cm / $total) * 100, 1),
            'td' => round(($this->volume_horaire_td / $total) * 100, 1),
            'tp' => round(($this->volume_horaire_tp / $total) * 100, 1),
        ];
    }

    /**
     * Vérifier si la planification est modifiable
     */
    public function isModifiable()
    {
        return in_array($this->statut, [
            self::STATUT_BROUILLON,
            self::STATUT_PLANIFIE
        ]);
    }

    /**
     * Obtenir la charge de travail de l'enseignant principal
     */
    public function getChargeEnseignantPrincipal()
    {
        if (!$this->enseignant_principal_id) {
            return 0;
        }

        return static::where('enseignant_principal_id', $this->enseignant_principal_id)
                    ->where('annee_universitaire_id', $this->annee_universitaire_id)
                    ->sum('volume_horaire_total');
    }

    /**
     * Obtenir le nombre de séances nécessaires par semaine
     */
    public function getNombreSeancesHebdomadaires($dureeSeance = 2)
    {
        $nombreSemaines = $this->periode_debut && $this->periode_fin 
            ? $this->periode_debut->diffInWeeks($this->periode_fin)
            : 30; // Valeur par défaut

        if ($nombreSemaines == 0) {
            return 0;
        }

        return round($this->volume_horaire_total / ($dureeSeance * $nombreSemaines), 2);
    }

    /**
     * Valider la cohérence de la planification
     */
    public function validerCoherence()
    {
        $erreurs = [];

        // Vérifier que la somme CM + TD + TP = Total
        $somme = ($this->volume_horaire_cm ?? 0) + 
                 ($this->volume_horaire_td ?? 0) + 
                 ($this->volume_horaire_tp ?? 0);
        
        if ($somme != $this->volume_horaire_total) {
            $erreurs[] = "La somme des volumes horaires détaillés ({$somme}h) ne correspond pas au total ({$this->volume_horaire_total}h)";
        }

        // Vérifier les périodes
        if ($this->periode_debut && $this->periode_fin && $this->periode_debut->gte($this->periode_fin)) {
            $erreurs[] = "La période de fin doit être postérieure à la période de début";
        }

        // Vérifier qu'il y a un enseignant assigné
        if (!$this->enseignant_principal_id) {
            $erreurs[] = "Un enseignant principal doit être assigné";
        }

        return $erreurs;
    }
}