<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ESBTPEnseignantDisponibilite extends Model
{
    use HasFactory;

    /**
     * La table associée au modèle.
     */
    protected $table = 'esbtp_enseignant_disponibilites';

    /**
     * Les attributs qui sont assignables en masse.
     */
    protected $fillable = [
        'enseignant_profile_id',
        'jour_semaine',
        'heure_debut',
        'heure_fin',
        'type_disponibilite',
        'motif',
        'date_debut',
        'date_fin',
        'est_recurrent',
        'semaines_exception',
        'is_active'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     */
    protected $casts = [
        'jour_semaine' => 'integer',
        'heure_debut' => 'datetime:H:i',
        'heure_fin' => 'datetime:H:i',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'est_recurrent' => 'boolean',
        'semaines_exception' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Types de disponibilité
     */
    const TYPE_DISPONIBLE = 'disponible';
    const TYPE_PREFERE = 'prefere';
    const TYPE_EVITER = 'eviter';
    const TYPE_INDISPONIBLE = 'indisponible';

    /**
     * Jours de la semaine
     */
    const JOURS_SEMAINE = [
        0 => 'Lundi',
        1 => 'Mardi',
        2 => 'Mercredi',
        3 => 'Jeudi',
        4 => 'Vendredi',
        5 => 'Samedi',
        6 => 'Dimanche'
    ];

    /**
     * Relation avec le profil enseignant
     */
    public function enseignantProfile()
    {
        return $this->belongsTo(ESBTPEnseignantProfile::class, 'enseignant_profile_id');
    }

    /**
     * Scope pour les disponibilités actives
     */
    public function scopeActif($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour filtrer par jour de la semaine
     */
    public function scopeJour($query, $jour)
    {
        return $query->where('jour_semaine', $jour);
    }

    /**
     * Scope pour filtrer par type de disponibilité
     */
    public function scopeType($query, $type)
    {
        return $query->where('type_disponibilite', $type);
    }

    /**
     * Scope pour les créneaux disponibles
     */
    public function scopeDisponible($query)
    {
        return $query->whereIn('type_disponibilite', [self::TYPE_DISPONIBLE, self::TYPE_PREFERE]);
    }

    /**
     * Scope pour une période donnée
     */
    public function scopePeriode($query, $dateDebut = null, $dateFin = null)
    {
        if ($dateDebut) {
            $query->where(function($q) use ($dateDebut) {
                $q->whereNull('date_debut')
                  ->orWhere('date_debut', '<=', $dateDebut);
            });
        }
        
        if ($dateFin) {
            $query->where(function($q) use ($dateFin) {
                $q->whereNull('date_fin')
                  ->orWhere('date_fin', '>=', $dateFin);
            });
        }
        
        return $query;
    }

    /**
     * Obtenir le nom du jour
     */
    public function getNomJourAttribute()
    {
        return self::JOURS_SEMAINE[$this->jour_semaine] ?? 'Inconnu';
    }

    /**
     * Obtenir le créneau formaté
     */
    public function getCreneauAttribute()
    {
        return Carbon::parse($this->heure_debut)->format('H:i') . ' - ' . 
               Carbon::parse($this->heure_fin)->format('H:i');
    }

    /**
     * Obtenir la durée en heures
     */
    public function getDureeHeuresAttribute()
    {
        $debut = Carbon::parse($this->heure_debut);
        $fin = Carbon::parse($this->heure_fin);
        
        return $fin->diffInHours($debut);
    }

    /**
     * Obtenir la durée en minutes
     */
    public function getDureeMinutesAttribute()
    {
        $debut = Carbon::parse($this->heure_debut);
        $fin = Carbon::parse($this->heure_fin);
        
        return $fin->diffInMinutes($debut);
    }

    /**
     * Vérifier si le créneau est valide pour une date donnée
     */
    public function estValidePourDate($date)
    {
        $date = Carbon::parse($date);
        
        // Vérifier la période de validité
        if ($this->date_debut && $date->lt(Carbon::parse($this->date_debut))) {
            return false;
        }
        
        if ($this->date_fin && $date->gt(Carbon::parse($this->date_fin))) {
            return false;
        }
        
        // Si non récurrent, vérifier que c'est exactement dans la période
        if (!$this->est_recurrent) {
            return $this->date_debut && $this->date_fin &&
                   $date->gte(Carbon::parse($this->date_debut)) &&
                   $date->lte(Carbon::parse($this->date_fin));
        }
        
        // Vérifier le jour de la semaine
        $jourSemaine = $date->dayOfWeek == 0 ? 6 : $date->dayOfWeek - 1; // Convertir dimanche=0 vers samedi=6
        if ($jourSemaine !== $this->jour_semaine) {
            return false;
        }
        
        // Vérifier les exceptions
        if (is_array($this->semaines_exception)) {
            $numeroSemaine = $date->weekOfYear;
            if (in_array($numeroSemaine, $this->semaines_exception)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Vérifier s'il y a conflit avec un autre créneau
     */
    public function aConflitAvec($autreDisponibilite)
    {
        // Même jour de la semaine
        if ($this->jour_semaine !== $autreDisponibilite->jour_semaine) {
            return false;
        }
        
        // Vérifier le chevauchement des heures
        $debut1 = Carbon::parse($this->heure_debut);
        $fin1 = Carbon::parse($this->heure_fin);
        $debut2 = Carbon::parse($autreDisponibilite->heure_debut);
        $fin2 = Carbon::parse($autreDisponibilite->heure_fin);
        
        return $debut1->lt($fin2) && $debut2->lt($fin1);
    }

    /**
     * Obtenir le libellé du type de disponibilité
     */
    public function getLibelleTypeAttribute()
    {
        $libelles = [
            self::TYPE_DISPONIBLE => 'Disponible',
            self::TYPE_PREFERE => 'Préféré',
            self::TYPE_EVITER => 'À éviter',
            self::TYPE_INDISPONIBLE => 'Indisponible'
        ];
        
        return $libelles[$this->type_disponibilite] ?? 'Inconnu';
    }

    /**
     * Obtenir la classe CSS pour l'affichage
     */
    public function getClasseCssAttribute()
    {
        $classes = [
            self::TYPE_DISPONIBLE => 'success',
            self::TYPE_PREFERE => 'primary',
            self::TYPE_EVITER => 'warning',
            self::TYPE_INDISPONIBLE => 'danger'
        ];
        
        return $classes[$this->type_disponibilite] ?? 'secondary';
    }

    /**
     * Vérifier si la disponibilité chevauche avec une période
     */
    public function chevaucheAvecPeriode($heureDebut, $heureFin)
    {
        $debut = Carbon::parse($this->heure_debut);
        $fin = Carbon::parse($this->heure_fin);
        $debutTest = Carbon::parse($heureDebut);
        $finTest = Carbon::parse($heureFin);
        
        return $debut->lt($finTest) && $debutTest->lt($fin);
    }

    /**
     * Obtenir les créneaux disponibles pour un enseignant sur une période
     */
    public static function getCreneauxDisponibles($enseignantProfileId, $dateDebut = null, $dateFin = null)
    {
        return self::where('enseignant_profile_id', $enseignantProfileId)
                  ->actif()
                  ->disponible()
                  ->periode($dateDebut, $dateFin)
                  ->orderBy('jour_semaine')
                  ->orderBy('heure_debut')
                  ->get();
    }

    /**
     * Créer les disponibilités par défaut pour un enseignant
     */
    public static function creerDisponibilitesParDefaut($enseignantProfileId)
    {
        $creneauxDefaut = [
            // Lundi à Vendredi, 8h-12h et 14h-18h
            ['jour' => 0, 'debut' => '08:00', 'fin' => '12:00'],
            ['jour' => 0, 'debut' => '14:00', 'fin' => '18:00'],
            ['jour' => 1, 'debut' => '08:00', 'fin' => '12:00'],
            ['jour' => 1, 'debut' => '14:00', 'fin' => '18:00'],
            ['jour' => 2, 'debut' => '08:00', 'fin' => '12:00'],
            ['jour' => 2, 'debut' => '14:00', 'fin' => '18:00'],
            ['jour' => 3, 'debut' => '08:00', 'fin' => '12:00'],
            ['jour' => 3, 'debut' => '14:00', 'fin' => '18:00'],
            ['jour' => 4, 'debut' => '08:00', 'fin' => '12:00'],
            ['jour' => 4, 'debut' => '14:00', 'fin' => '18:00'],
        ];
        
        foreach ($creneauxDefaut as $creneau) {
            self::create([
                'enseignant_profile_id' => $enseignantProfileId,
                'jour_semaine' => $creneau['jour'],
                'heure_debut' => $creneau['debut'],
                'heure_fin' => $creneau['fin'],
                'type_disponibilite' => self::TYPE_DISPONIBLE,
                'est_recurrent' => true,
                'is_active' => true
            ]);
        }
    }
}