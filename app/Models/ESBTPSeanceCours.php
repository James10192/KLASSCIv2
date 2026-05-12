<?php

namespace App\Models;

use App\Enums\TypeSeance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\ESBTPEvaluation;

class ESBTPSeanceCours extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_seance_cours';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'emploi_temps_id',
        'classe_id',
        'matiere_id',
        'teacher_id',
        'jour',
        'heure_debut',
        'heure_fin',
        'salle',
        'description',
        'type',
        'color',
        'homework_description',
        'homework_due_date',
        'homework_evaluation_id',
        'is_recurring',
        'recurrence_days',
        'priority',
        'annee_universitaire_id',
        'is_active',
        'date_seance',
        'type_seance',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'heure_debut' => 'datetime',
        'heure_fin' => 'datetime',
        'homework_due_date' => 'date',
        'is_recurring' => 'boolean',
        'recurrence_days' => 'array',
        'is_active' => 'boolean',
        'type_seance' => TypeSeance::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Session type constants
    const TYPE_COURSE = 'course';
    const TYPE_HOMEWORK = 'homework';
    const TYPE_BREAK = 'break';
    const TYPE_LUNCH = 'lunch';

    // Default colors for different types
    const DEFAULT_COLORS = [
        self::TYPE_COURSE => '#2196F3',   // Blue
        self::TYPE_HOMEWORK => '#4CAF50', // Green
        self::TYPE_BREAK => '#FF9800',    // Orange
        self::TYPE_LUNCH => '#F44336'     // Red
    ];

    /**
     * Relation avec l'emploi du temps associé à cette séance.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function emploiTemps()
    {
        // Inclure les emplois du temps soft-deleted pour éviter les erreurs
        return $this->belongsTo(ESBTPEmploiTemps::class, 'emploi_temps_id')->withTrashed();
    }

    /**
     * Relation avec la matière associée à cette séance.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    /**
     * Relation avec l'enseignant (teacher)
     */
    public function teacher()
    {
        return $this->belongsTo(ESBTPTeacher::class, 'teacher_id');
    }

    /**
     * Évaluation générée automatiquement pour un devoir.
     */
    public function homeworkEvaluation()
    {
        return $this->belongsTo(ESBTPEvaluation::class, 'homework_evaluation_id');
    }

    /**
     * Alias pour la relation teacher (pour compatibilité)
     */
    public function enseignant()
    {
        return $this->teacher();
    }

    /**
     * Relation avec la classe associée à cette séance.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classe()
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    /**
     * Relation avec les présences enseignants (émargements) pour cette séance.
     */
    public function teacherAttendances()
    {
        return $this->hasMany(ESBTPTeacherAttendance::class, 'course_id');
    }

    /**
     * Relation pour la première présence enseignant (émargement unique).
     */
    public function teacherAttendance()
    {
        return $this->hasOne(ESBTPTeacherAttendance::class, 'course_id');
    }

    /**
     * Relation avec les présences étudiants (appels) pour cette séance.
     */
    public function studentAttendances()
    {
        return $this->hasMany(ESBTPAttendance::class, 'seance_cours_id');
    }

    /**
     * Relation pour obtenir toutes les présences (alias pour compatibilité).
     */
    public function attendances()
    {
        return $this->studentAttendances();
    }

    /**
     * Relation vers le rapport de cours soumis par le professeur.
     */
    public function sessionReport()
    {
        return $this->hasOne(\App\Models\ESBTPSessionReport::class, 'seance_cours_id');
    }

    /**
     * Helper methods for session types
     */
    public function isCourse()
    {
        return $this->type === self::TYPE_COURSE;
    }

    public function isHomework()
    {
        return $this->type === self::TYPE_HOMEWORK;
    }

    public function isBreak()
    {
        return $this->type === self::TYPE_BREAK;
    }

    public function isLunch()
    {
        return $this->type === self::TYPE_LUNCH;
    }

    /**
     * Helper method to get the default color based on type
     */
    public function getDefaultColor()
    {
        return self::DEFAULT_COLORS[$this->type] ?? '#000000';
    }

    /**
     * Accessor to ensure we always have a color
     */
    public function getColorAttribute($value)
    {
        return $value ?? self::DEFAULT_COLORS[$this->type] ?? '#000000';
    }

    /**
     * Helper method to check if the session occurs on a specific day
     */
    public function occursOnDay($dayNumber)
    {
        if (!$this->is_recurring) {
            return $this->jour == $dayNumber;
        }
        return in_array($dayNumber, $this->recurrence_days ?: []);
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scopes for different types
     */
    public function scopeCourses($query)
    {
        return $query->where('type', self::TYPE_COURSE);
    }

    public function scopeHomework($query)
    {
        return $query->where('type', self::TYPE_HOMEWORK);
    }

    public function scopeBreaks($query)
    {
        return $query->where('type', self::TYPE_BREAK);
    }

    public function scopeLunch($query)
    {
        return $query->where('type', self::TYPE_LUNCH);
    }

    /**
     * Helper method to get formatted time range
     */
    public function getTimeRangeAttribute()
    {
        return $this->heure_debut->format('H:i') . ' - ' . $this->heure_fin->format('H:i');
    }

    /**
     * Obtenir le jour de la semaine en texte.
     *
     * @return string
     */
    public function getJourSemaineTexteAttribute()
    {
        $jours = [
            0 => 'Lundi',
            1 => 'Mardi',
            2 => 'Mercredi',
            3 => 'Jeudi',
            4 => 'Vendredi',
            5 => 'Samedi',
        ];

        return $jours[$this->jour] ?? 'Jour inconnu';
    }

    /**
     * Obtenir la durée de la séance en minutes.
     *
     * @return int
     */
    public function getDureeMinutesAttribute()
    {
        if (!$this->heure_debut || !$this->heure_fin) {
            return 0;
        }

        $debut = $this->heure_debut;
        $fin = $this->heure_fin;

        // Calculer la différence en minutes
        return $fin->diffInMinutes($debut);
    }

    /**
     * Obtenir la plage horaire au format HH:MM - HH:MM.
     *
     * @return string
     */
    public function getPlageHoraireAttribute()
    {
        $debut = $this->heure_debut ? $this->heure_debut->format('H:i') : '--:--';
        $fin = $this->heure_fin ? $this->heure_fin->format('H:i') : '--:--';

        return "{$debut} - {$fin}";
    }

    /**
     * Vérifie si la séance est en conflit avec une autre séance.
     *
     * @param ESBTPSeanceCours $autreSeance
     * @return bool
     */
    public function estEnConflitAvec(ESBTPSeanceCours $autreSeance)
    {
        // Vérifier si les séances sont le même jour
        if ($this->jour !== $autreSeance->jour) {
            return false;
        }

        // Vérifier si les plages horaires se chevauchent
        return ($this->heure_debut < $autreSeance->heure_fin) &&
               ($this->heure_fin > $autreSeance->heure_debut);
    }

    /**
     * Calcule la date réelle de la séance en fonction du jour de la semaine et de la période de l'emploi du temps.
     *
     * @return \Carbon\Carbon|null La date de la séance ou null si l'emploi du temps n'est pas défini
     */
    public function getDateSeance()
    {
        if (!$this->emploiTemps) {
            return null;
        }

        // Récupérer la date de début de l'emploi du temps
        $dateDebut = \Carbon\Carbon::parse($this->emploiTemps->date_debut);

        // Convertir le nom du jour en numéro (1 = lundi, 7 = dimanche)
        $joursMapping = [
            'lundi' => 1,
            'mardi' => 2,
            'mercredi' => 3,
            'jeudi' => 4,
            'vendredi' => 5,
            'samedi' => 6,
            'dimanche' => 7,
        ];

        $jourSeance = $joursMapping[strtolower($this->jour)] ?? null;

        if (!$jourSeance) {
            return null;
        }

        // Calculer le décalage entre le jour de la semaine de la date de début (1 = lundi, 7 = dimanche)
        // et le jour de la séance (1 = lundi, 7 = dimanche)
        $jourDebutSemaine = $dateDebut->dayOfWeek ?: 7; // Carbon retourne 0 pour dimanche, on le convertit en 7

        // Calculer le nombre de jours à ajouter
        $joursAAjouter = 0;
        if ($jourSeance >= $jourDebutSemaine) {
            $joursAAjouter = $jourSeance - $jourDebutSemaine;
        } else {
            $joursAAjouter = 7 - $jourDebutSemaine + $jourSeance;
        }

        // Si le jour calculé dépasse la date de fin, on retourne null
        $dateSeance = $dateDebut->copy()->addDays($joursAAjouter);
        if ($dateSeance->isAfter($this->emploiTemps->date_fin)) {
            return null;
        }

        return $dateSeance;
    }

    /**
     * Retourne le nom du jour de la semaine.
     *
     * @return string Le nom du jour de la semaine
     */
    public function getNomJour()
    {
        $jours = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi'
        ];

        return $jours[$this->jour] ?? 'Jour inconnu';
    }

    /**
     * Accessors and Mutators
     */
    public function getHeureDebutAttribute($value)
    {
        return Carbon::parse($value);
    }

    public function getHeureFinAttribute($value)
    {
        return Carbon::parse($value);
    }

    public function setHeureDebutAttribute($value)
    {
        $this->attributes['heure_debut'] = Carbon::parse($value)->format('H:i:s');
    }

    public function setHeureFinAttribute($value)
    {
        $this->attributes['heure_fin'] = Carbon::parse($value)->format('H:i:s');
    }

    /**
     * Helper methods
     */
    public function getDuration()
    {
        return $this->heure_debut->diffInMinutes($this->heure_fin);
    }

    public function getDureeEnMinutes()
    {
        return $this->getDuration();
    }

    public function isOverlapping(ESBTPSeanceCours $other)
    {
        if ($this->jour !== $other->jour) {
            return false;
        }

        return $this->heure_debut < $other->heure_fin && $this->heure_fin > $other->heure_debut;
    }

    public function hasConflictWith(ESBTPSeanceCours $other)
    {
        if (!$this->isOverlapping($other)) {
            return false;
        }

        // Check for same teacher
        if ($this->teacher_id && $other->teacher_id && $this->teacher_id === $other->teacher_id) {
            return true;
        }

        // Check for same room (except for breaks)
        if ($this->type !== self::TYPE_BREAK && $other->type !== self::TYPE_BREAK &&
            $this->salle && $other->salle && $this->salle === $other->salle) {
            return true;
        }

        // Check for same class
        return $this->emploi_temps_id === $other->emploi_temps_id;
    }

    public function getRecurrenceText()
    {
        if (!$this->is_recurring || empty($this->recurrence_days)) {
            return null;
        }

        $days = collect($this->recurrence_days)->map(function ($day) {
            $jours = [
                1 => 'Lundi',
                2 => 'Mardi',
                3 => 'Mercredi',
                4 => 'Jeudi',
                5 => 'Vendredi',
                6 => 'Samedi'
            ];
            return $jours[$day] ?? '';
        })->filter()->join(', ');

        return "Récurrent chaque : $days";
    }

    public function getSessionTypeText()
    {
        $types = [
            self::TYPE_COURSE => 'Cours',
            self::TYPE_HOMEWORK => 'Devoir',
            self::TYPE_BREAK => 'Récréation',
            self::TYPE_LUNCH => 'Pause déjeuner'
        ];

        return $types[$this->type] ?? 'Cours';
    }
    
    public function getTypeIcon()
    {
        $icons = [
            self::TYPE_COURSE => 'fa-chalkboard-teacher',
            self::TYPE_HOMEWORK => 'fa-file-alt', 
            self::TYPE_BREAK => 'fa-coffee',
            self::TYPE_LUNCH => 'fa-utensils'
        ];

        return $icons[$this->type] ?? 'fa-chalkboard-teacher';
    }

    public function getSessionDescription()
    {
        $description = $this->getSessionTypeText();

        if (in_array($this->type, [self::TYPE_COURSE, self::TYPE_HOMEWORK])) {
            $description .= ' - ' . ($this->matiere->name ?? 'Matière non définie');
            if ($this->teacher) {
                $description .= ' avec ' . $this->teacher->name;
            }
        }

        if ($this->salle) {
            $description .= ' (Salle: ' . $this->salle . ')';
        }

        if ($this->type === self::TYPE_HOMEWORK && $this->homework_due_date) {
            $description .= ' - À rendre le ' . Carbon::parse($this->homework_due_date)->format('d/m/Y');
        }

        return $description;
    }


    /**
     * Accesseur pour obtenir le nom de l'enseignant
     */
    public function getEnseignantNameAttribute()
    {
        return $this->enseignant ? $this->enseignant->name : 'Non assigné';
    }

    /**
     * Calculer la date complète de la séance à partir de l'emploi du temps et du jour
     */
    public function getDateCompleteSeance()
    {
        if (!$this->emploiTemps || !$this->emploiTemps->date_debut) {
            return null;
        }

        $dateDebut = $this->emploiTemps->date_debut;
        $jourSeance = (int)$this->jour; // 1=lundi, 2=mardi, etc.

        // Convertir le jour de la séance en jour de la semaine ISO (1=lundi, 7=dimanche)
        $jourISO = $jourSeance === 7 ? 7 : $jourSeance;

        // Trouver le premier occurrence de ce jour dans la période de l'emploi du temps
        $dateRecherche = clone $dateDebut;

        // Obtenir le jour de la semaine de la date de début (1=lundi, 7=dimanche)
        $jourDateDebut = (int)$dateRecherche->dayOfWeekIso;

        // Calculer combien de jours ajouter pour atteindre le jour voulu dans la même semaine
        if ($jourISO >= $jourDateDebut) {
            // Le jour est dans la même semaine
            $joursAjouter = $jourISO - $jourDateDebut;
        } else {
            // Le jour est dans la semaine suivante
            $joursAjouter = (7 - $jourDateDebut) + $jourISO;
        }
        
        $dateSeance = $dateRecherche->addDays($joursAjouter);
        
        // Vérifier si la date calculée est dans la période de l'emploi du temps
        if ($this->emploiTemps->date_fin && $dateSeance->gt($this->emploiTemps->date_fin)) {
            // Si on dépasse la date de fin, prendre la dernière occurrence possible
            $dateSeance = clone $this->emploiTemps->date_fin;
            $jourDateFin = $dateSeance->dayOfWeekIso;
            
            if ($jourISO <= $jourDateFin) {
                // Reculer pour trouver le jour dans la même semaine
                $joursReculer = $jourDateFin - $jourISO;
                $dateSeance = $dateSeance->subDays($joursReculer);
            } else {
                // Reculer à la semaine précédente
                $joursReculer = 7 - ($jourISO - $jourDateFin);
                $dateSeance = $dateSeance->subDays($joursReculer);
            }
        }
        
        return $dateSeance;
    }

    /**
     * Obtenir la date complète formatée de la séance
     */
    public function getDateCompleteFormattee()
    {
        $jourMapping = [
            1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi',
            4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'
        ];

        if (!empty($this->date_seance)) {
            $date = $this->date_seance instanceof Carbon
                ? $this->date_seance
                : Carbon::parse($this->date_seance);

            $jourIso = (int) $date->dayOfWeekIso;
            $nomJour = $jourMapping[$jourIso] ?? $date->format('l');

            return $nomJour . ' ' . $date->format('d/m/Y');
        }

        $date = $this->getDateCompleteSeance();
        if (!$date) {
            return 'Date non disponible';
        }

        $nomJour = $jourMapping[$this->jour] ?? $jourMapping[(int) $date->dayOfWeekIso] ?? 'Jour inconnu';

        return $nomJour . ' ' . $date->format('d/m/Y');
    }
}
