<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classroom extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'classrooms';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', // Nom ou numéro de la salle
        'building', // Bâtiment où se trouve la salle
        'floor', // Étage
        'room_number', // Numéro de la salle
        'capacity', // Capacité d'accueil (nombre de places)
        'type', // Type de salle (amphithéâtre, salle de TD, laboratoire, etc.)
        'has_projector', // Présence d'un projecteur
        'has_computers', // Présence d'ordinateurs
        'has_whiteboard', // Présence d'un tableau blanc
        'has_blackboard', // Présence d'un tableau noir
        'has_internet', // Accès à Internet
        'is_accessible', // Accessibilité pour personnes à mobilité réduite
        'notes', // Notes supplémentaires
        'status', // Statut (disponible, en maintenance, hors service)
        'created_by',
        'updated_by',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'capacity' => 'integer',
        'floor' => 'integer',
        'has_projector' => 'boolean',
        'has_computers' => 'boolean',
        'has_whiteboard' => 'boolean',
        'has_blackboard' => 'boolean',
        'has_internet' => 'boolean',
        'is_accessible' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtenir les séances de cours programmées dans cette salle.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function courseSessions()
    {
        return $this->hasMany(CourseSession::class);
    }

    /**
     * Obtenir les évaluations programmées dans cette salle.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class, 'location', 'id');
    }

    /**
     * Obtenir l'utilisateur qui a créé la salle.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtenir l'utilisateur qui a mis à jour la salle.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Vérifier si la salle est disponible à une date et heure spécifiques.
     * 
     * @param \DateTime $date La date à vérifier
     * @param \DateTime $startTime L'heure de début
     * @param \DateTime $endTime L'heure de fin
     * @param int|null $excludeSessionId ID de la séance à exclure (pour les mises à jour)
     * @return bool
     */
    public function isAvailable($date, $startTime, $endTime, $excludeSessionId = null)
    {
        if ($this->status !== 'available') {
            return false;
        }
        
        $query = $this->courseSessions()
            ->where('date', $date->format('Y-m-d'))
            ->where(function ($query) use ($startTime, $endTime) {
                // Vérifie si une séance existante chevauche la période demandée
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                });
            });
        
        if ($excludeSessionId) {
            $query->where('id', '!=', $excludeSessionId);
        }
        
        return $query->count() === 0;
    }

    /**
     * Obtenir le taux d'occupation de la salle pour une période donnée.
     * 
     * @param \DateTime $startDate Date de début
     * @param \DateTime $endDate Date de fin
     * @return float Pourcentage d'occupation
     */
    public function getOccupancyRate($startDate, $endDate)
    {
        // Nombre total d'heures dans la période
        $totalHours = $startDate->diffInHours($endDate);
        
        if ($totalHours === 0) {
            return 0;
        }
        
        // Heures occupées par des séances
        $occupiedMinutes = 0;
        
        $sessions = $this->courseSessions()
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();
        
        foreach ($sessions as $session) {
            $occupiedMinutes += $session->getDurationInMinutes();
        }
        
        $occupiedHours = $occupiedMinutes / 60;
        
        return ($occupiedHours / $totalHours) * 100;
    }

    /**
     * Obtenir une description complète de la salle.
     * 
     * @return string
     */
    public function getFullDescription()
    {
        $features = [];
        
        if ($this->has_projector) $features[] = 'Projecteur';
        if ($this->has_computers) $features[] = 'Ordinateurs';
        if ($this->has_whiteboard) $features[] = 'Tableau blanc';
        if ($this->has_blackboard) $features[] = 'Tableau noir';
        if ($this->has_internet) $features[] = 'Internet';
        if ($this->is_accessible) $features[] = 'Accessible PMR';
        
        $featuresStr = !empty($features) ? ' - ' . implode(', ', $features) : '';
        
        return "{$this->name} ({$this->type}) - Bâtiment {$this->building}, Étage {$this->floor}, Salle {$this->room_number} - Capacité: {$this->capacity} places{$featuresStr}";
    }
} 