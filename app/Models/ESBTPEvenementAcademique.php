<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class ESBTPEvenementAcademique extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'esbtp_evenements_academiques';

    protected $fillable = [
        'annee_universitaire_id',
        'titre',
        'description',
        'date_debut',
        'date_fin',
        'type',
        'icone',
        'couleur',
        'afficher_calendrier',
        'afficher_timeline',
        'notification_active',
        'jours_notification',
        'notes',
        'participants',
        'lieu',
        'heure_debut',
        'heure_fin',
        'statut',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'heure_debut' => 'datetime:H:i',
        'heure_fin' => 'datetime:H:i',
        'participants' => 'array',
        'afficher_calendrier' => 'boolean',
        'afficher_timeline' => 'boolean',
        'notification_active' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Types d'événements disponibles
    const TYPES = [
        'rentree' => 'Rentrée',
        'orientation' => 'Orientation',
        'examens' => 'Examens',
        'vacances' => 'Vacances',
        'reprise' => 'Reprise',
        'soutenances' => 'Soutenances',
        'ceremonie' => 'Cérémonie',
        'fermeture' => 'Fermeture',
        'stage' => 'Stage',
        'reunion' => 'Réunion',
        'formation' => 'Formation',
        'conference' => 'Conférence',
        'autre' => 'Autre'
    ];

    // Statuts disponibles
    const STATUTS = [
        'planifie' => 'Planifié',
        'confirme' => 'Confirmé',
        'annule' => 'Annulé',
        'reporte' => 'Reporté',
        'termine' => 'Terminé'
    ];

    // Couleurs disponibles
    const COULEURS = [
        'primary' => 'Bleu',
        'secondary' => 'Gris',
        'success' => 'Vert',
        'danger' => 'Rouge',
        'warning' => 'Orange',
        'info' => 'Cyan',
        'light' => 'Clair',
        'dark' => 'Foncé'
    ];

    // Icônes suggérées par type
    const ICONES_TYPES = [
        'rentree' => 'graduation-cap',
        'orientation' => 'compass',
        'examens' => 'file-alt',
        'vacances' => 'calendar-times',
        'reprise' => 'play-circle',
        'soutenances' => 'presentation',
        'ceremonie' => 'trophy',
        'fermeture' => 'flag-checkered',
        'stage' => 'briefcase',
        'reunion' => 'users',
        'formation' => 'chalkboard-teacher',
        'conference' => 'microphone',
        'autre' => 'calendar'
    ];

    // Couleurs par défaut par type
    const COULEURS_TYPES = [
        'rentree' => 'success',
        'orientation' => 'info',
        'examens' => 'warning',
        'vacances' => 'secondary',
        'reprise' => 'success',
        'soutenances' => 'primary',
        'ceremonie' => 'warning',
        'fermeture' => 'dark',
        'stage' => 'info',
        'reunion' => 'secondary',
        'formation' => 'success',
        'conference' => 'primary',
        'autre' => 'light'
    ];

    /**
     * Relation avec l'année universitaire
     */
    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    /**
     * Relation avec l'utilisateur créateur
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur modificateur
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope pour filtrer par année universitaire
     */
    public function scopeForAnnee($query, $anneeId)
    {
        return $query->where('annee_universitaire_id', $anneeId);
    }

    /**
     * Scope pour les événements actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les événements visibles dans le calendrier
     */
    public function scopeVisibleCalendrier($query)
    {
        return $query->where('afficher_calendrier', true);
    }

    /**
     * Scope pour les événements visibles dans la timeline
     */
    public function scopeVisibleTimeline($query)
    {
        return $query->where('afficher_timeline', true);
    }

    /**
     * Scope pour filtrer par type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeWithStatus($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    /**
     * Scope pour les événements dans une période
     */
    public function scopeInPeriod($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('date_debut', [$dateDebut, $dateFin]);
    }

    /**
     * Scope pour les événements à venir
     */
    public function scopeUpcoming($query)
    {
        return $query->where('date_debut', '>=', now()->toDateString());
    }

    /**
     * Scope pour les événements passés
     */
    public function scopePast($query)
    {
        return $query->where('date_debut', '<', now()->toDateString());
    }

    /**
     * Accessor pour le libellé du type
     */
    public function getTypeLibelleAttribute()
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Accessor pour le libellé du statut
     */
    public function getStatutLibelleAttribute()
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    /**
     * Accessor pour le libellé de la couleur
     */
    public function getCouleurLibelleAttribute()
    {
        return self::COULEURS[$this->couleur] ?? $this->couleur;
    }

    /**
     * Accessor pour la durée de l'événement
     */
    public function getDureeAttribute()
    {
        if (!$this->date_fin) {
            return '1 jour';
        }
        
        $debut = Carbon::parse($this->date_debut);
        $fin = Carbon::parse($this->date_fin);
        $jours = $debut->diffInDays($fin) + 1;
        
        return $jours . ' jour' . ($jours > 1 ? 's' : '');
    }

    /**
     * Accessor pour vérifier si l'événement est en cours
     */
    public function getIsCurrentAttribute()
    {
        $aujourdhui = now()->toDateString();
        
        if ($this->date_fin) {
            return $aujourdhui >= $this->date_debut && $aujourdhui <= $this->date_fin;
        }
        
        return $aujourdhui === $this->date_debut;
    }

    /**
     * Accessor pour vérifier si l'événement est à venir
     */
    public function getIsUpcomingAttribute()
    {
        return $this->date_debut > now()->toDateString();
    }

    /**
     * Accessor pour vérifier si l'événement est passé
     */
    public function getIsPastAttribute()
    {
        $dateFin = $this->date_fin ?: $this->date_debut;
        return $dateFin < now()->toDateString();
    }

    /**
     * Accessor pour la date formatée
     */
    public function getDateFormateeAttribute()
    {
        $debut = Carbon::parse($this->date_debut);
        
        if ($this->date_fin) {
            $fin = Carbon::parse($this->date_fin);
            if ($debut->format('Y-m') === $fin->format('Y-m')) {
                return $debut->format('d') . ' - ' . $fin->format('d/m/Y');
            }
            return $debut->format('d/m/Y') . ' - ' . $fin->format('d/m/Y');
        }
        
        return $debut->format('d/m/Y');
    }

    /**
     * Mutator pour définir automatiquement l'icône selon le type
     */
    public function setTypeAttribute($value)
    {
        $this->attributes['type'] = $value;
        
        // Définir l'icône automatiquement si pas encore définie
        if (!isset($this->attributes['icone']) || empty($this->attributes['icone'])) {
            $this->attributes['icone'] = self::ICONES_TYPES[$value] ?? 'calendar';
        }
        
        // Définir la couleur automatiquement si pas encore définie
        if (!isset($this->attributes['couleur']) || empty($this->attributes['couleur'])) {
            $this->attributes['couleur'] = self::COULEURS_TYPES[$value] ?? 'primary';
        }
    }

    /**
     * Vérifier si l'événement a besoin d'une notification
     */
    public function needsNotification()
    {
        if (!$this->notification_active) {
            return false;
        }
        
        $dateNotification = Carbon::parse($this->date_debut)->subDays($this->jours_notification);
        return now()->toDateString() === $dateNotification->toDateString();
    }

    /**
     * Obtenir les participants formatés
     */
    public function getParticipantsFormattedAttribute()
    {
        if (!$this->participants || empty($this->participants)) {
            return 'Toute l\'école';
        }
        
        $participants = [];
        foreach ($this->participants as $participant) {
            $participants[] = $participant['nom'] ?? $participant;
        }
        
        return implode(', ', $participants);
    }

    /**
     * Vérifier si l'événement peut être modifié
     */
    public function isEditable()
    {
        return !$this->is_past && $this->statut !== 'termine';
    }

    /**
     * Vérifier si l'événement peut être supprimé
     */
    public function isDeletable()
    {
        return !$this->is_past && $this->statut !== 'termine';
    }
}
