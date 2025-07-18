<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class ESBTPEnseignantProfile extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     */
    protected $table = 'esbtp_enseignant_profiles';

    /**
     * Les attributs qui sont assignables en masse.
     */
    protected $fillable = [
        'user_id',
        'matricule_enseignant',
        'titre_academique',
        'grade_academique',
        'diplome_principal',
        'universite_diplome',
        'annee_diplome',
        'specialites',
        'competences_techniques',
        'certifications',
        'langues',
        'annees_experience_enseignement',
        'annees_experience_professionnelle',
        'experiences_anterieures',
        'projets_recherche',
        'publications',
        'disponibilites_hebdomadaires',
        'preferences_horaires',
        'contraintes_horaires',
        'charge_horaire_max_semaine',
        'charge_horaire_actuelle',
        'note_evaluation_moyenne',
        'nombre_evaluations',
        'evaluations_competences',
        'taux_assiduite',
        'nombre_retards',
        'nombre_absences',
        'formations_suivies',
        'formations_prevues',
        'derniere_formation',
        'type_contrat',
        'statut_emploi',
        'taux_horaire',
        'date_embauche',
        'fin_contrat',
        'methodes_enseignement_preferees',
        'outils_pedagogiques_maitrise',
        'accepte_enseignement_distance',
        'accepte_cours_weekend',
        'accepte_cours_soir',
        'motivation',
        'objectifs_pedagogiques',
        'projets_innovants',
        'statut',
        'profil_valide',
        'valide_par',
        'date_validation',
        'observations_rh',
        'notes_direction',
        'historique_modifications',
        'created_by',
        'updated_by'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     */
    protected $casts = [
        'specialites' => 'array',
        'competences_techniques' => 'array',
        'certifications' => 'array',
        'langues' => 'array',
        'experiences_anterieures' => 'array',
        'projets_recherche' => 'array',
        'publications' => 'array',
        'disponibilites_hebdomadaires' => 'array',
        'preferences_horaires' => 'array',
        'contraintes_horaires' => 'array',
        'evaluations_competences' => 'array',
        'formations_suivies' => 'array',
        'formations_prevues' => 'array',
        'methodes_enseignement_preferees' => 'array',
        'outils_pedagogiques_maitrise' => 'array',
        'historique_modifications' => 'array',
        'annee_diplome' => 'integer',
        'annees_experience_enseignement' => 'integer',
        'annees_experience_professionnelle' => 'integer',
        'charge_horaire_max_semaine' => 'integer',
        'charge_horaire_actuelle' => 'integer',
        'note_evaluation_moyenne' => 'decimal:2',
        'nombre_evaluations' => 'integer',
        'taux_assiduite' => 'decimal:2',
        'nombre_retards' => 'integer',
        'nombre_absences' => 'integer',
        'taux_horaire' => 'decimal:2',
        'accepte_enseignement_distance' => 'boolean',
        'accepte_cours_weekend' => 'boolean',
        'accepte_cours_soir' => 'boolean',
        'profil_valide' => 'boolean',
        'date_embauche' => 'date',
        'fin_contrat' => 'date',
        'derniere_formation' => 'date',
        'date_validation' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Types de contrat disponibles
     */
    const TYPE_CONTRAT_PERMANENT = 'permanent';
    const TYPE_CONTRAT_TEMPORAIRE = 'temporaire';
    const TYPE_CONTRAT_VACATAIRE = 'vacataire';
    const TYPE_CONTRAT_CONSULTANT = 'consultant';

    /**
     * Statuts d'emploi disponibles
     */
    const STATUT_TEMPS_PLEIN = 'temps_plein';
    const STATUT_TEMPS_PARTIEL = 'temps_partiel';
    const STATUT_VACATIONS = 'vacations';

    /**
     * Statuts de profil disponibles
     */
    const STATUT_ACTIF = 'actif';
    const STATUT_INACTIF = 'inactif';
    const STATUT_SUSPENDU = 'suspendu';
    const STATUT_EN_FORMATION = 'en_formation';
    const STATUT_CONGE = 'conge';

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation avec l'utilisateur qui a validé le profil
     */
    public function validateur()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    /**
     * Relation avec l'utilisateur qui a créé le profil
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a modifié le profil
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relation avec les disponibilités
     */
    public function disponibilites()
    {
        return $this->hasMany(ESBTPEnseignantDisponibilite::class, 'enseignant_profile_id');
    }

    /**
     * Relation avec les affectations
     */
    public function affectations()
    {
        return $this->hasMany(ESBTPEnseignantAffectation::class, 'enseignant_profile_id');
    }

    /**
     * Relation avec les affectations actives
     */
    public function affectationsActives()
    {
        return $this->hasMany(ESBTPEnseignantAffectation::class, 'enseignant_profile_id')
                   ->where('statut', ESBTPEnseignantAffectation::STATUT_ACTIVE);
    }

    /**
     * Scope pour les profils actifs
     */
    public function scopeActif($query)
    {
        return $query->where('statut', self::STATUT_ACTIF);
    }

    /**
     * Scope pour les profils validés
     */
    public function scopeValide($query)
    {
        return $query->where('profil_valide', true);
    }

    /**
     * Scope pour les enseignants disponibles
     */
    public function scopeDisponible($query)
    {
        return $query->where('statut', self::STATUT_ACTIF)
                    ->where('profil_valide', true);
    }

    /**
     * Scope pour filtrer par type de contrat
     */
    public function scopeTypeContrat($query, $type)
    {
        return $query->where('type_contrat', $type);
    }

    /**
     * Scope pour filtrer par grade académique
     */
    public function scopeGradeAcademique($query, $grade)
    {
        return $query->where('grade_academique', $grade);
    }

    /**
     * Obtenir le nom complet avec titre
     */
    public function getNomCompletAvecTitreAttribute()
    {
        $titre = $this->titre_academique ? $this->titre_academique . ' ' : '';
        return $titre . $this->user->name;
    }

    /**
     * Obtenir l'expérience totale
     */
    public function getExperienceTotaleAttribute()
    {
        return $this->annees_experience_enseignement + $this->annees_experience_professionnelle;
    }

    /**
     * Calculer le taux de charge actuel
     */
    public function getTauxChargeAttribute()
    {
        if ($this->charge_horaire_max_semaine == 0) {
            return 0;
        }
        return round(($this->charge_horaire_actuelle / $this->charge_horaire_max_semaine) * 100, 2);
    }

    /**
     * Vérifier si l'enseignant peut prendre plus d'heures
     */
    public function peutPrendreHeuresSupplementaires($heures)
    {
        return ($this->charge_horaire_actuelle + $heures) <= $this->charge_horaire_max_semaine;
    }

    /**
     * Obtenir les spécialités sous forme de string
     */
    public function getSpecialitesStringAttribute()
    {
        return is_array($this->specialites) ? implode(', ', $this->specialites) : '';
    }

    /**
     * Obtenir le statut d'évaluation
     */
    public function getStatutEvaluationAttribute()
    {
        if ($this->nombre_evaluations == 0) {
            return 'Non évalué';
        }
        
        if ($this->note_evaluation_moyenne >= 4.5) {
            return 'Excellent';
        } elseif ($this->note_evaluation_moyenne >= 4.0) {
            return 'Très bien';
        } elseif ($this->note_evaluation_moyenne >= 3.5) {
            return 'Bien';
        } elseif ($this->note_evaluation_moyenne >= 3.0) {
            return 'Satisfaisant';
        } else {
            return 'Amélioration nécessaire';
        }
    }

    /**
     * Vérifier si le profil est complet
     */
    public function isProfilComplet()
    {
        $champsRequis = [
            'diplome_principal', 'specialites', 'annees_experience_enseignement',
            'charge_horaire_max_semaine', 'type_contrat', 'statut_emploi'
        ];
        
        foreach ($champsRequis as $champ) {
            if (empty($this->$champ)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Obtenir les compétences principales
     */
    public function getCompetencesPrincipales($limit = 5)
    {
        if (!is_array($this->competences_techniques)) {
            return [];
        }
        
        return array_slice($this->competences_techniques, 0, $limit);
    }

    /**
     * Calculer l'ancienneté dans l'établissement
     */
    public function getAncienneteAttribute()
    {
        if (!$this->date_embauche) {
            return 0;
        }
        
        return Carbon::parse($this->date_embauche)->diffInYears(now());
    }

    /**
     * Vérifier si l'enseignant a besoin de formation
     */
    public function aBesoinFormation()
    {
        // Si aucune formation dans les 2 dernières années
        if (!$this->derniere_formation) {
            return true;
        }
        
        return Carbon::parse($this->derniere_formation)->diffInYears(now()) >= 2;
    }

    /**
     * Obtenir les heures disponibles
     */
    public function getHeuresDisponiblesAttribute()
    {
        return $this->charge_horaire_max_semaine - $this->charge_horaire_actuelle;
    }

    /**
     * Vérifier la compatibilité avec une planification
     */
    public function estCompatibleAvecPlanification(ESBTPPlanificationAcademique $planification)
    {
        // Vérifier la charge horaire
        if (!$this->peutPrendreHeuresSupplementaires($planification->volume_horaire_total)) {
            return false;
        }
        
        // Vérifier les spécialités
        if (is_array($this->specialites) && !empty($this->specialites)) {
            $matiereNom = strtolower($planification->matiere->name ?? '');
            foreach ($this->specialites as $specialite) {
                if (str_contains($matiereNom, strtolower($specialite))) {
                    return true;
                }
            }
            return false; // Aucune spécialité compatible trouvée
        }
        
        return true; // Si pas de spécialités définies, on considère compatible
    }

    /**
     * Mettre à jour la charge horaire
     */
    public function mettreAJourChargeHoraire()
    {
        $totalHeures = $this->affectationsActives()
                           ->sum('heures_affectees');
        
        $this->update(['charge_horaire_actuelle' => $totalHeures]);
        
        return $totalHeures;
    }

    /**
     * Obtenir l'historique des modifications formaté
     */
    public function getHistoriqueFormateAttribute()
    {
        if (!is_array($this->historique_modifications)) {
            return [];
        }
        
        return collect($this->historique_modifications)
               ->map(function ($modification) {
                   return [
                       'date' => Carbon::parse($modification['date'] ?? now())->format('d/m/Y H:i'),
                       'utilisateur' => $modification['utilisateur'] ?? 'Système',
                       'action' => $modification['action'] ?? 'Modification',
                       'details' => $modification['details'] ?? ''
                   ];
               })
               ->sortByDesc('date')
               ->values()
               ->all();
    }
}