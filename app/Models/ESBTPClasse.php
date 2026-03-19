<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPClasse extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_classes';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'filiere_id',
        'niveau_etude_id',
        'annee_universitaire_id',
        'places_totales', // Renommé de capacity
        'places_occupees',
        'description',
        'is_active',
        'systeme_academique',   // BTS|LMD
        'parcours_id',          // FK vers esbtp_lmd_parcours (si LMD)
        'created_by',
        'updated_by',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'places_totales' => 'integer',
        'places_occupees' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Les relations qui doivent toujours être chargées.
     *
     * @var array
     */
    protected $with = ['filiere', 'niveau', 'annee'];

    /**
     * Relation avec la filière.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    /**
     * Relation avec le niveau d'études.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function niveau()
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_etude_id');
    }

    /**
     * Relation avec l'année universitaire.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function annee()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    /**
     * Relation avec l'année universitaire (alias).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeUniversitaire()
    {
        return $this->annee();
    }

    /**
     * Parcours LMD associe (si systeme_academique = LMD).
     */
    public function parcours()
    {
        return $this->belongsTo(ESBTPLMDParcours::class, 'parcours_id');
    }

    /**
     * Verifie si la classe utilise le systeme LMD.
     */
    public function isLMD(): bool
    {
        return $this->systeme_academique === 'LMD';
    }

    /**
     * Verifie si la classe utilise le systeme BTS.
     */
    public function isBTS(): bool
    {
        return $this->systeme_academique !== 'LMD';
    }

    public function scopeLmd($query)
    {
        return $query->where('systeme_academique', 'LMD');
    }

    /**
     * Relation avec les inscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inscriptions()
    {
        return $this->hasMany(ESBTPInscription::class, 'classe_id');
    }

    /**
     * Relation avec les emplois du temps.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function emploisDuTemps()
    {
        return $this->hasMany(ESBTPEmploiTemps::class, 'classe_id');
    }

    /**
     * Alias pour la relation emploisDuTemps (au singulier)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function emploiTemps()
    {
        return $this->emploisDuTemps();
    }

    /**
     * Relation avec les évaluations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function evaluations()
    {
        return $this->hasMany(ESBTPEvaluation::class, 'classe_id');
    }

    /**
     * Relation avec les matières associées à cette classe.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function matieres()
    {
        return $this->belongsToMany(ESBTPMatiere::class, 'esbtp_classe_matiere', 'classe_id', 'matiere_id')
                    ->withPivot('coefficient', 'total_heures', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Récupérer les étudiants inscrits dans cette classe.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function etudiants()
    {
        return $this->hasManyThrough(
            ESBTPEtudiant::class,
            ESBTPInscription::class,
            'classe_id', // Clé étrangère sur la table inscriptions
            'id', // Clé primaire sur la table etudiants
            'id', // Clé primaire sur la table classes
            'etudiant_id' // Clé étrangère sur la table inscriptions
        );
    }

    /**
     * Nombre d'étudiants actuellement inscrits dans cette classe pour l'année courante.
     *
     * @return int
     */
    public function getNombreEtudiantsAttribute()
    {
        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (!$anneeCourante) {
            // Pas d'année courante définie → retourner 0 au lieu de compter toutes les années
            // Ceci évite des incohérences dans le calcul des places disponibles
            \Log::warning("Aucune année universitaire courante définie pour le calcul de nombre_etudiants de la classe {$this->id}");
            return 0;
        }

        $count = $this->inscriptions()
                    ->where('status', 'active')
                    ->where('annee_universitaire_id', $anneeCourante->id)
                    ->count();

        // Log pour debugging (à retirer en production)
        if (config('app.debug')) {
            \Log::debug("Classe {$this->id} ({$this->name}): {$count} étudiants actifs pour l'année {$anneeCourante->name}");
        }

        return $count;
    }

    /**
     * Places encore disponibles dans cette classe.
     *
     * @return int
     */
    public function getPlacesDisponiblesAttribute()
    {
        $nombreEtudiants = $this->nombre_etudiants;
        $placesTotales = $this->places_totales ?? 0;
        $placesDisponibles = max(0, $placesTotales - $nombreEtudiants);

        // Log pour debugging (à retirer en production)
        if (config('app.debug')) {
            \Log::debug("Classe {$this->id} ({$this->name}): Capacité={$placesTotales}, Inscrits={$nombreEtudiants}, Disponibles={$placesDisponibles}");
        }

        return $placesDisponibles;
    }

    /**
     * Nom complet de la classe (exemple: "GC-BAT BTS1 2023-2024").
     *
     * @return string
     */
    public function getNomCompletAttribute()
    {
        $filiere = $this->filiere ? $this->filiere->code : '';
        $niveau = $this->niveau ? $this->niveau->code : '';
        $annee = $this->annee ? $this->annee->name : '';

        return "{$filiere} {$niveau} {$annee}";
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

    /**
     * Alias pour la relation niveau d'études pour assurer la compatibilité avec le code existant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function niveauEtude()
    {
        return $this->niveau();
    }

    /**
     * Mettre à jour le nombre de places occupées basé sur les inscriptions actives.
     *
     * @return void
     */
    public function updatePlacesOccupees(): void
    {
        $placesOccupees = $this->inscriptions()->where('status', 'active')->count();
        $this->update(['places_occupees' => $placesOccupees]);
    }

    /**
     * Vérifier s'il y a encore des places disponibles.
     *
     * @return bool
     */
    public function hasPlacesDisponibles(): bool
    {
        return $this->places_disponibles > 0;
    }

    /**
     * Obtenir le pourcentage d'occupation de la classe.
     *
     * @return float
     */
    public function getTauxOccupationAttribute(): float
    {
        if ($this->places_totales === 0) {
            return 0;
        }
        
        return round(($this->places_occupees / $this->places_totales) * 100, 2);
    }

    /**
     * Scope pour les classes avec des places disponibles.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvecPlacesDisponibles($query)
    {
        return $query->whereRaw('places_occupees < places_totales');
    }

    /**
     * Scope pour les classes pleines.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePleines($query)
    {
        return $query->whereRaw('places_occupees >= places_totales');
    }
}
