<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     * 
     * @var string
     */
    protected $table = 'students';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'student_id', // Numéro étudiant
        'parcours_id',
        'promotion', // Année d'entrée
        'current_year', // L1, L2, L3, M1, M2, etc.
        'status', // Actif, en congé, diplômé, etc.
        'registration_date',
        'expected_graduation_date',
        'actual_graduation_date',
        'scholarship_status',
        'scholarship_details',
        'special_needs',
        'international_student',
        'country_of_origin',
        'visa_status',
        'visa_expiry_date',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'previous_institution',
        'previous_qualification',
        'admission_score',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'registration_date' => 'date',
        'expected_graduation_date' => 'date',
        'actual_graduation_date' => 'date',
        'visa_expiry_date' => 'date',
        'scholarship_details' => 'array',
        'special_needs' => 'array',
        'notes' => 'array',
    ];

    /**
     * Relation avec l'utilisateur.
     * 
     * Un étudiant est lié à un utilisateur.
     * Imaginez l'utilisateur comme une personne, et l'étudiant comme son "rôle" 
     * à l'université avec des informations spécifiques à ce rôle.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le parcours auquel l'étudiant est inscrit.
     */
    public function parcours()
    {
        return $this->belongsTo(Parcours::class);
    }

    /**
     * Relation avec les inscriptions aux UEs.
     */
    public function ueEnrollments()
    {
        return $this->hasMany(UEEnrollment::class);
    }

    /**
     * Relation avec les inscriptions aux ECs.
     */
    public function ecEnrollments()
    {
        return $this->hasMany(ECEnrollment::class);
    }

    /**
     * Relation avec les présences aux sessions de cours.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Relation avec les notes d'évaluation.
     */
    public function evaluationResults()
    {
        return $this->hasMany(EvaluationResult::class);
    }

    /**
     * Relation avec les documents soumis par l'étudiant.
     */
    public function submittedDocuments()
    {
        return $this->hasMany(Document::class, 'submitted_by');
    }

    /**
     * Relation avec l'utilisateur qui a créé ce profil.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour ce profil.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtenir le nom complet de l'étudiant.
     * 
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->user->full_name;
    }

    /**
     * Obtenir l'adresse email de l'étudiant.
     * 
     * @return string
     */
    public function getEmailAttribute()
    {
        return $this->user->email;
    }

    /**
     * Obtenir le numéro de téléphone de l'étudiant.
     * 
     * @return string|null
     */
    public function getPhoneAttribute()
    {
        return $this->user->phone;
    }

    /**
     * Calculer la moyenne générale de l'étudiant pour une année spécifique.
     * 
     * @param string $year L'année académique (ex: "2023-2024")
     * @return float|null
     */
    public function calculateGPA($year = null)
    {
        // Si aucune année n'est spécifiée, utiliser l'année en cours
        if (!$year) {
            $year = date('Y') . '-' . (date('Y') + 1);
        }

        $ueEnrollments = $this->ueEnrollments()
            ->where('academic_year', $year)
            ->with('evaluationResults')
            ->get();

        if ($ueEnrollments->isEmpty()) {
            return null;
        }

        $totalCredits = 0;
        $weightedSum = 0;

        foreach ($ueEnrollments as $enrollment) {
            $ueGrade = $enrollment->final_grade;
            $ueCredits = $enrollment->ue->credits;

            if ($ueGrade !== null && $ueCredits > 0) {
                $weightedSum += $ueGrade * $ueCredits;
                $totalCredits += $ueCredits;
            }
        }

        if ($totalCredits === 0) {
            return null;
        }

        return round($weightedSum / $totalCredits, 2);
    }

    /**
     * Vérifier si l'étudiant a validé une UE spécifique.
     * 
     * @param int $ueId L'ID de l'UE
     * @return bool
     */
    public function hasPassedUE($ueId)
    {
        $enrollment = $this->ueEnrollments()
            ->where('unite_enseignement_id', $ueId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$enrollment) {
            return false;
        }

        // La note de passage est généralement 10/20 en France
        return $enrollment->final_grade >= 10;
    }

    /**
     * Vérifier si l'étudiant a validé un EC spécifique.
     * 
     * @param int $ecId L'ID de l'EC
     * @return bool
     */
    public function hasPassedEC($ecId)
    {
        $enrollment = $this->ecEnrollments()
            ->where('element_constitutif_id', $ecId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$enrollment) {
            return false;
        }

        // La note de passage est généralement 10/20 en France
        return $enrollment->final_grade >= 10;
    }

    /**
     * Calculer le taux de présence aux cours pour une période donnée.
     * 
     * @param string|null $startDate Date de début (format Y-m-d)
     * @param string|null $endDate Date de fin (format Y-m-d)
     * @return float Pourcentage de présence
     */
    public function calculateAttendanceRate($startDate = null, $endDate = null)
    {
        $query = $this->attendances();
        
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        
        $attendances = $query->get();
        
        if ($attendances->isEmpty()) {
            return 0;
        }
        
        $totalSessions = $attendances->count();
        $presentSessions = $attendances->where('status', 'present')->count();
        
        return round(($presentSessions / $totalSessions) * 100, 2);
    }

    /**
     * Scope pour filtrer les étudiants par parcours.
     */
    public function scopeInParcours($query, $parcoursId)
    {
        return $query->where('parcours_id', $parcoursId);
    }

    /**
     * Scope pour filtrer les étudiants par année d'étude.
     */
    public function scopeInYear($query, $year)
    {
        return $query->where('current_year', $year);
    }

    /**
     * Scope pour filtrer les étudiants par statut.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pour filtrer les étudiants internationaux.
     */
    public function scopeInternational($query, $isInternational = true)
    {
        return $query->where('international_student', $isInternational);
    }

    /**
     * Scope pour filtrer les étudiants boursiers.
     */
    public function scopeWithScholarship($query, $hasScholarship = true)
    {
        if ($hasScholarship) {
            return $query->whereNotNull('scholarship_status')
                         ->where('scholarship_status', '!=', 'none');
        }
        
        return $query->where(function($q) {
            $q->whereNull('scholarship_status')
              ->orWhere('scholarship_status', 'none');
        });
    }

    /**
     * Relation avec les inscriptions ESBTP.
     * 
     * Un étudiant peut avoir plusieurs inscriptions ESBTP.
     * Par exemple, un étudiant peut être inscrit en première année BTS Génie Civil,
     * puis en deuxième année BTS Génie Civil l'année suivante.
     */
    public function esbtpInscriptions()
    {
        return $this->hasMany(ESBTPInscription::class, 'student_id');
    }

    /**
     * Obtenir les filières ESBTP auxquelles l'étudiant est inscrit.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function esbtpFilieres()
    {
        return ESBTPFiliere::whereIn('id', $this->esbtpInscriptions()->pluck('filiere_id')->unique());
    }

    /**
     * Obtenir les niveaux d'études ESBTP auxquels l'étudiant est inscrit.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function esbtpNiveauxEtudes()
    {
        return ESBTPNiveauEtude::whereIn('id', $this->esbtpInscriptions()->pluck('niveau_etude_id')->unique());
    }

    /**
     * Vérifier si l'étudiant est inscrit à une filière ESBTP spécifique.
     * 
     * @param int $filiereId L'ID de la filière
     * @param int|null $anneeUniversitaireId L'ID de l'année universitaire (optionnel)
     * @return bool
     */
    public function isInscritFiliere($filiereId, $anneeUniversitaireId = null)
    {
        $query = $this->esbtpInscriptions()->where('filiere_id', $filiereId);
        
        if ($anneeUniversitaireId) {
            $query->where('annee_universitaire_id', $anneeUniversitaireId);
        }
        
        return $query->exists();
    }

    /**
     * Vérifier si l'étudiant est inscrit à un niveau d'études ESBTP spécifique.
     * 
     * @param int $niveauEtudeId L'ID du niveau d'études
     * @param int|null $anneeUniversitaireId L'ID de l'année universitaire (optionnel)
     * @return bool
     */
    public function isInscritNiveauEtude($niveauEtudeId, $anneeUniversitaireId = null)
    {
        $query = $this->esbtpInscriptions()->where('niveau_etude_id', $niveauEtudeId);
        
        if ($anneeUniversitaireId) {
            $query->where('annee_universitaire_id', $anneeUniversitaireId);
        }
        
        return $query->exists();
    }

    /**
     * Relation avec le parent/tuteur.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function guardian()
    {
        return $this->belongsTo(ESBTPParent::class, 'guardian_id');
    }
} 