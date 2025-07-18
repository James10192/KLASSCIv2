<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class ESBTPEnseignantAffectation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     */
    protected $table = 'esbtp_enseignant_affectations';

    /**
     * Les attributs qui sont assignables en masse.
     */
    protected $fillable = [
        'enseignant_profile_id',
        'planification_id',
        'matiere_id',
        'classe_id',
        'type_affectation',
        'heures_affectees',
        'type_cours',
        'date_debut',
        'date_fin',
        'statut',
        'note_performance',
        'commentaires',
        'feedback_etudiants',
        'affecte_par',
        'date_affectation'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     */
    protected $casts = [
        'heures_affectees' => 'integer',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'note_performance' => 'decimal:2',
        'feedback_etudiants' => 'array',
        'date_affectation' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Types d'affectation
     */
    const TYPE_PRINCIPAL = 'principal';
    const TYPE_SECONDAIRE = 'secondaire';
    const TYPE_REMPLACANT = 'remplacant';
    const TYPE_TEMPORAIRE = 'temporaire';

    /**
     * Types de cours
     */
    const TYPE_CM = 'cm';
    const TYPE_TD = 'td';
    const TYPE_TP = 'tp';
    const TYPE_STAGE = 'stage';
    const TYPE_PROJET = 'projet';

    /**
     * Statuts d'affectation
     */
    const STATUT_ACTIVE = 'active';
    const STATUT_TERMINEE = 'terminee';
    const STATUT_ANNULEE = 'annulee';
    const STATUT_SUSPENDUE = 'suspendue';

    /**
     * Relation avec le profil enseignant
     */
    public function enseignantProfile()
    {
        return $this->belongsTo(ESBTPEnseignantProfile::class, 'enseignant_profile_id');
    }

    /**
     * Relation avec la planification académique
     */
    public function planification()
    {
        return $this->belongsTo(ESBTPPlanificationAcademique::class, 'planification_id');
    }

    /**
     * Relation avec la matière
     */
    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    /**
     * Relation avec la classe
     */
    public function classe()
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    /**
     * Relation avec l'utilisateur qui a fait l'affectation
     */
    public function affectePar()
    {
        return $this->belongsTo(User::class, 'affecte_par');
    }

    /**
     * Scope pour les affectations actives
     */
    public function scopeActive($query)
    {
        return $query->where('statut', self::STATUT_ACTIVE);
    }

    /**
     * Scope pour filtrer par type d'affectation
     */
    public function scopeType($query, $type)
    {
        return $query->where('type_affectation', $type);
    }

    /**
     * Scope pour filtrer par type de cours
     */
    public function scopeTypeCours($query, $typeCours)
    {
        return $query->where('type_cours', $typeCours);
    }

    /**
     * Scope pour les affectations en cours
     */
    public function scopeEnCours($query)
    {
        $now = now();
        return $query->where('date_debut', '<=', $now)
                    ->where(function($q) use ($now) {
                        $q->whereNull('date_fin')
                          ->orWhere('date_fin', '>=', $now);
                    })
                    ->where('statut', self::STATUT_ACTIVE);
    }

    /**
     * Scope pour une période donnée
     */
    public function scopePeriode($query, $dateDebut, $dateFin)
    {
        return $query->where(function($q) use ($dateDebut, $dateFin) {
            $q->whereBetween('date_debut', [$dateDebut, $dateFin])
              ->orWhereBetween('date_fin', [$dateDebut, $dateFin])
              ->orWhere(function($q2) use ($dateDebut, $dateFin) {
                  $q2->where('date_debut', '<=', $dateDebut)
                     ->where('date_fin', '>=', $dateFin);
              });
        });
    }

    /**
     * Obtenir le libellé du type d'affectation
     */
    public function getLibelleTypeAffectationAttribute()
    {
        $libelles = [
            self::TYPE_PRINCIPAL => 'Enseignant Principal',
            self::TYPE_SECONDAIRE => 'Enseignant Secondaire',
            self::TYPE_REMPLACANT => 'Remplaçant',
            self::TYPE_TEMPORAIRE => 'Temporaire'
        ];
        
        return $libelles[$this->type_affectation] ?? 'Inconnu';
    }

    /**
     * Obtenir le libellé du type de cours
     */
    public function getLibelleTypeCoursAttribute()
    {
        $libelles = [
            self::TYPE_CM => 'Cours Magistral',
            self::TYPE_TD => 'Travaux Dirigés',
            self::TYPE_TP => 'Travaux Pratiques',
            self::TYPE_STAGE => 'Stage',
            self::TYPE_PROJET => 'Projet'
        ];
        
        return $libelles[$this->type_cours] ?? 'Inconnu';
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getLibelleStatutAttribute()
    {
        $libelles = [
            self::STATUT_ACTIVE => 'Active',
            self::STATUT_TERMINEE => 'Terminée',
            self::STATUT_ANNULEE => 'Annulée',
            self::STATUT_SUSPENDUE => 'Suspendue'
        ];
        
        return $libelles[$this->statut] ?? 'Inconnu';
    }

    /**
     * Obtenir la classe CSS pour le statut
     */
    public function getClasseCssStatutAttribute()
    {
        $classes = [
            self::STATUT_ACTIVE => 'success',
            self::STATUT_TERMINEE => 'secondary',
            self::STATUT_ANNULEE => 'danger',
            self::STATUT_SUSPENDUE => 'warning'
        ];
        
        return $classes[$this->statut] ?? 'secondary';
    }

    /**
     * Calculer la durée de l'affectation en jours
     */
    public function getDureeJoursAttribute()
    {
        if (!$this->date_fin) {
            return null; // Affectation indéfinie
        }
        
        return Carbon::parse($this->date_debut)->diffInDays(Carbon::parse($this->date_fin));
    }

    /**
     * Calculer la durée de l'affectation en semaines
     */
    public function getDureeSemainesAttribute()
    {
        if (!$this->date_fin) {
            return null; // Affectation indéfinie
        }
        
        return Carbon::parse($this->date_debut)->diffInWeeks(Carbon::parse($this->date_fin));
    }

    /**
     * Vérifier si l'affectation est en cours
     */
    public function estEnCours()
    {
        $now = now();
        return $this->statut === self::STATUT_ACTIVE &&
               $this->date_debut <= $now &&
               (!$this->date_fin || $this->date_fin >= $now);
    }

    /**
     * Vérifier si l'affectation va commencer
     */
    public function vaCommencer()
    {
        return $this->statut === self::STATUT_ACTIVE &&
               $this->date_debut > now();
    }

    /**
     * Calculer le pourcentage d'avancement
     */
    public function getPourcentageAvancementAttribute()
    {
        if (!$this->date_fin || !$this->estEnCours()) {
            return 0;
        }
        
        $debut = Carbon::parse($this->date_debut);
        $fin = Carbon::parse($this->date_fin);
        $maintenant = now();
        
        $dureeTotal = $debut->diffInDays($fin);
        $dureeEcoulee = $debut->diffInDays($maintenant);
        
        if ($dureeTotal == 0) {
            return 100;
        }
        
        return min(100, round(($dureeEcoulee / $dureeTotal) * 100, 2));
    }

    /**
     * Obtenir la note de performance formatée
     */
    public function getNotePerformanceFormateeAttribute()
    {
        if (!$this->note_performance) {
            return 'Non évaluée';
        }
        
        return number_format($this->note_performance, 2) . '/5';
    }

    /**
     * Obtenir le statut d'évaluation basé sur la note
     */
    public function getStatutEvaluationPerformanceAttribute()
    {
        if (!$this->note_performance) {
            return 'Non évaluée';
        }
        
        if ($this->note_performance >= 4.5) {
            return 'Excellente';
        } elseif ($this->note_performance >= 4.0) {
            return 'Très bonne';
        } elseif ($this->note_performance >= 3.5) {
            return 'Bonne';
        } elseif ($this->note_performance >= 3.0) {
            return 'Satisfaisante';
        } else {
            return 'Insuffisante';
        }
    }

    /**
     * Terminer l'affectation
     */
    public function terminer($notePerformance = null, $commentaires = null)
    {
        $this->update([
            'statut' => self::STATUT_TERMINEE,
            'date_fin' => $this->date_fin ?? now(),
            'note_performance' => $notePerformance,
            'commentaires' => $commentaires
        ]);
        
        // Mettre à jour la charge horaire de l'enseignant
        $this->enseignantProfile->mettreAJourChargeHoraire();
        
        return $this;
    }

    /**
     * Suspendre l'affectation
     */
    public function suspendre($motif = null)
    {
        $this->update([
            'statut' => self::STATUT_SUSPENDUE,
            'commentaires' => $motif
        ]);
        
        // Mettre à jour la charge horaire de l'enseignant
        $this->enseignantProfile->mettreAJourChargeHoraire();
        
        return $this;
    }

    /**
     * Réactiver l'affectation
     */
    public function reactiver()
    {
        $this->update(['statut' => self::STATUT_ACTIVE]);
        
        // Mettre à jour la charge horaire de l'enseignant
        $this->enseignantProfile->mettreAJourChargeHoraire();
        
        return $this;
    }

    /**
     * Annuler l'affectation
     */
    public function annuler($motif = null)
    {
        $this->update([
            'statut' => self::STATUT_ANNULEE,
            'commentaires' => $motif,
            'date_fin' => now()
        ]);
        
        // Mettre à jour la charge horaire de l'enseignant
        $this->enseignantProfile->mettreAJourChargeHoraire();
        
        return $this;
    }

    /**
     * Vérifier s'il y a conflit avec une autre affectation
     */
    public function aConflitAvec($autreAffectation)
    {
        // Même enseignant
        if ($this->enseignant_profile_id !== $autreAffectation->enseignant_profile_id) {
            return false;
        }
        
        // Vérifier le chevauchement des dates
        $debut1 = Carbon::parse($this->date_debut);
        $fin1 = $this->date_fin ? Carbon::parse($this->date_fin) : Carbon::now()->addYears(10);
        $debut2 = Carbon::parse($autreAffectation->date_debut);
        $fin2 = $autreAffectation->date_fin ? Carbon::parse($autreAffectation->date_fin) : Carbon::now()->addYears(10);
        
        return $debut1->lt($fin2) && $debut2->lt($fin1);
    }

    /**
     * Obtenir les statistiques d'une affectation
     */
    public function getStatistiques()
    {
        return [
            'duree_jours' => $this->duree_jours,
            'duree_semaines' => $this->duree_semaines,
            'pourcentage_avancement' => $this->pourcentage_avancement,
            'est_en_cours' => $this->estEnCours(),
            'va_commencer' => $this->vaCommencer(),
            'note_performance' => $this->note_performance,
            'statut_evaluation' => $this->statut_evaluation_performance
        ];
    }
}