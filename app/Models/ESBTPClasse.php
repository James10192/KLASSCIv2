<?php

namespace App\Models;

use App\Services\ClasseManagementService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPClasse extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    /**
     * Colonnes auditées (whitelist).
     *
     * @var array
     */
    protected $auditInclude = [
        'name',
        'code',
        'filiere_id',
        'niveau_etude_id',
        'annee_universitaire_id',
        'places_totales',
        'places_occupees',
        'description',
        'is_active',
        'systeme_academique',
        'parcours_id',
    ];

    /**
     * Événements à auditer.
     *
     * @var array
     */
    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $classe) {
            // Auto-determiner systeme_academique depuis le niveau d'etudes
            if ($classe->isDirty('niveau_etude_id') || !$classe->systeme_academique) {
                $niveau = $classe->relationLoaded('niveau')
                    ? $classe->niveau
                    : ESBTPNiveauEtude::find($classe->niveau_etude_id);

                if ($niveau) {
                    $classe->systeme_academique = ClasseManagementService::determinerSystemeAcademique($niveau->type ?? '');
                }
            }
        });
    }

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

    /**
     * Vérifie si cette classe appartient à une filière marquée tronc commun.
     * Utilisé pour afficher le badge « Tronc commun » sur classes.index et la
     * section « Sorties spécialités » sur classes.show.
     */
    public function isTroncCommun(): bool
    {
        return optional($this->filiere)->isTroncCommun() ?? false;
    }

    /**
     * Vérifie si cette classe est une spécialité (sa filière est fille d'un TC).
     * Utilisé pour afficher le badge « Spécialité » + provenance sur classes.index.
     */
    public function isSpecialite(): bool
    {
        return optional($this->filiere)->isFilleDeTC() ?? false;
    }

    /**
     * Retourne la classe TC parent dont cette classe est une spécialité.
     *
     * Priorité d'override (Marcel, juin 2026) :
     *  1. Si la classe apparaît comme `target_classe_id` dans un
     *     `esbtp_classe_orientation_targets` actif, la TC source de ce mapping
     *     manuel est la priorité (override config admin).
     *  2. Sinon, fallback automatique via la hiérarchie filière (parent_id) :
     *     on cherche la classe TC du même niveau d'études dans la filière
     *     `parent` de la classe courante.
     *
     * Renvoie null si non applicable (classe TC, classe sans parent_id filière,
     * pas de classe TC trouvée au même niveau).
     *
     * Note : les classes KLASSCI sont universelles
     * (cf rule classes-universelles-pas-annee.md), donc on ne filtre PAS par
     * annee_universitaire_id.
     */
    public function classeTroncCommunParent(): ?ESBTPClasse
    {
        if ($this->isTroncCommun() || !$this->isSpecialite()) {
            return null;
        }

        // 1. Override manuel : la classe est target dans un mapping actif → la source est le TC parent
        $manual = \Illuminate\Support\Facades\DB::table('esbtp_classe_orientation_targets')
            ->where('target_classe_id', $this->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('source_classe_id');

        if ($manual) {
            $source = static::find($manual);
            if ($source && $source->isTroncCommun()) {
                return $source;
            }
        }

        // 2. Fallback hiérarchie filière : on cherche la classe TC du même niveau
        // dans la filière parent (la filière TC mère de la spécialité courante).
        $filiereParentId = optional($this->filiere)->parent_id;
        if (!$filiereParentId) {
            return null;
        }

        return static::query()
            ->where('filiere_id', $filiereParentId)
            ->where('niveau_etude_id', $this->niveau_etude_id)
            ->where('is_active', true)
            ->whereHas('filiere', fn ($q) => $q->where('is_tronc_commun', true))
            ->first();
    }

    /**
     * Retourne les 2 semestres autorises pour cette classe LMD.
     * L1 (year=1) → [1,2], L2 (year=2) → [3,4], L3 → [5,6], M1 → [7,8], M2 → [9,10]
     */
    public function getSemestresLMD(): array
    {
        $year = $this->niveau->year ?? 1;
        $s1 = ($year - 1) * 2 + 1;
        $s2 = $s1 + 1;
        return [$s1, $s2];
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

    public function orientationTargets()
    {
        return $this->hasMany(ESBTPClasseOrientationTarget::class, 'source_classe_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function orientationSources()
    {
        return $this->hasMany(ESBTPClasseOrientationTarget::class, 'target_classe_id');
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
                    ->where('workflow_step', 'etudiant_cree')
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
