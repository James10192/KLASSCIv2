<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPCours extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_cours';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'matiere_id',
        'enseignant_id',
        'classe_id',
        'annee_universitaire_id',
        'jour_semaine',
        'heure_debut',
        'heure_fin',
        'salle',
        'type_cours', // CM (Cours Magistral), TD (Travaux Dirigés), TP (Travaux Pratiques)
        'is_active',
        'created_by',
        'updated_by'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'jour_semaine' => 'integer', // 0 = Lundi, 1 = Mardi, etc.
        'heure_debut' => 'datetime:H:i',
        'heure_fin' => 'datetime:H:i',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relation avec la matière associée à ce cours.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    /**
     * Relation avec l'enseignant associé à ce cours.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function enseignant()
    {
        return $this->belongsTo(User::class, 'enseignant_id');
    }

    /**
     * Relation avec la classe associée à ce cours.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classe()
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    /**
     * Relation avec l'année universitaire associée à ce cours.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    /**
     * Relation avec l'emploi du temps (via la classe)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function emploiTemps()
    {
        return $this->hasManyThrough(
            ESBTPEmploiTemps::class,
            ESBTPClasse::class,
            'id', // Clé étrangère de ESBTPClasse
            'classe_id', // Clé étrangère de ESBTPEmploiTemps
            'classe_id', // Clé locale de ESBTPCours
            'id' // Clé locale de ESBTPClasse
        );
    }

    /**
     * Relation avec les absences associées à ce cours.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function absences()
    {
        return $this->hasMany(ESBTPAbsence::class, 'cours_id');
    }

    /**
     * Relation avec les présences associées à ce cours.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendances()
    {
        return $this->hasMany(ESBTPAttendance::class, 'seance_cours_id');
    }

    /**
     * Obtenir le jour de la semaine en texte.
     *
     * @return string
     */
    public function getJourSemaineTextAttribute()
    {
        $jours = [
            0 => 'Lundi',
            1 => 'Mardi',
            2 => 'Mercredi',
            3 => 'Jeudi',
            4 => 'Vendredi',
            5 => 'Samedi',
        ];

        return $jours[$this->jour_semaine] ?? 'Inconnu';
    }

    /**
     * Obtenir la durée du cours en heures.
     *
     * @return float
     */
    public function getDureeHeuresAttribute()
    {
        if (!$this->heure_debut || !$this->heure_fin) {
            return 0;
        }

        $debut = $this->heure_debut;
        $fin = $this->heure_fin;

        return round($fin->diffInMinutes($debut) / 60, 2);
    }

    /**
     * Obtenir l'horaire formaté du cours.
     *
     * @return string
     */
    public function getHoraireFormateAttribute()
    {
        if (!$this->heure_debut || !$this->heure_fin) {
            return 'Horaire non défini';
        }

        return $this->heure_debut->format('H:i') . ' - ' . $this->heure_fin->format('H:i');
    }
}
