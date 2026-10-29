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
     * Valeurs posees par preparerPourListe() pour une liste de classes : elles
     * evitent les requetes que les accesseurs relanceraient a chaque lecture.
     * Hors liste, elles restent vides et le calcul habituel s'applique.
     */
    protected ?int $nombreEtudiantsConnu = null;

    protected ?ESBTPClasse $parentTroncCommun = null;

    protected bool $parentTroncCommunConnu = false;

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
        if ($this->parentTroncCommunConnu) {
            return $this->parentTroncCommun;
        }

        return static::parentsTroncCommun(collect([$this]))->get($this->id);
    }

    /**
     * Le TC parent de chaque classe d'une liste, en un nombre constant de requetes quel que soit
     * le nombre de classes : la meme regle que classeTroncCommunParent(), qui la
     * delegue ici. Les classes doivent avoir leur filiere chargee.
     *
     * @param  \Illuminate\Support\Collection<int, ESBTPClasse>  $classes
     * @return \Illuminate\Support\Collection<int, ESBTPClasse|null> classe_id => TC parent
     */
    public static function parentsTroncCommun(\Illuminate\Support\Collection $classes): \Illuminate\Support\Collection
    {
        $specialites = $classes->filter(fn (self $c) => ! $c->isTroncCommun() && $c->isSpecialite());
        $parents = $classes->mapWithKeys(fn (self $c) => [$c->id => null]);
        if ($specialites->isEmpty()) {
            return $parents;
        }

        // 1. Override manuel : la classe est target dans un mapping actif, le plus
        // ancien (orderBy id) l'emporte.
        $manuels = \Illuminate\Support\Facades\DB::table('esbtp_classe_orientation_targets')
            ->whereIn('target_classe_id', $specialites->pluck('id'))
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['target_classe_id', 'source_classe_id'])
            ->unique('target_classe_id')
            ->pluck('source_classe_id', 'target_classe_id');
        $sources = $manuels->isEmpty() ? collect()
            : static::whereIn('id', $manuels->unique()->values())->get()->keyBy('id');

        // 2. Fallback hierarchie filiere : la classe TC du meme niveau dans la
        // filiere parent. La premiere trouvee l'emporte, comme le first() d'origine.
        $filieresParent = $specialites->map(fn (self $c) => optional($c->filiere)->parent_id)->filter()->unique();
        $candidats = $filieresParent->isEmpty() ? collect()
            : static::query()
                ->whereIn('filiere_id', $filieresParent->values())
                ->where('is_active', true)
                ->whereHas('filiere', fn ($q) => $q->where('is_tronc_commun', true))
                ->get();

        foreach ($specialites as $classe) {
            $source = $sources->get($manuels->get($classe->id));
            if ($source && $source->isTroncCommun()) {
                $parents[$classe->id] = $source;
                continue;
            }
            $filiereParentId = optional($classe->filiere)->parent_id;
            $parents[$classe->id] = $filiereParentId ? $candidats->first(
                fn (self $c) => (int) $c->filiere_id === (int) $filiereParentId
                    && (int) $c->niveau_etude_id === (int) $classe->niveau_etude_id
            ) : null;
        }

        return $parents;
    }

    /**
     * Pose sur chaque carte d'une liste ses places prises et son TC parent,
     * pour que l'affichage ne lance plus aucune requete par carte.
     *
     * @param  \Illuminate\Support\Collection<int, ESBTPClasse>  $classes
     */
    public static function preparerPourListe(\Illuminate\Support\Collection $classes): void
    {
        if ($classes->isEmpty()) {
            return;
        }
        // Sans annee courante, l'effectif reste a l'accesseur, qui le journalise.
        $places = ESBTPAnneeUniversitaire::where('is_current', true)->exists()
            ? static::placesPrisesParClasse()
            : null;
        $parents = static::parentsTroncCommun($classes);
        foreach ($classes as $classe) {
            if ($places !== null) {
                $classe->nombreEtudiantsConnu = (int) $places->get($classe->id, 0);
            }
            $classe->parentTroncCommun = $parents->get($classe->id);
            $classe->parentTroncCommunConnu = true;
        }
    }

    /**
     * Retourne les 2 semestres autorises pour cette classe LMD.
     * L1 (year=1) → [1,2], L2 (year=2) → [3,4], L3 → [5,6], M1 → [7,8], M2 → [9,10]
     *
     * L'annee du niveau est comptee en continu (Master 1 = annee 4, cf.
     * ESBTPNiveauEtude::ANNEES_PAR_CYCLE_LMD). La formule vit sur le niveau.
     */
    public function getSemestresLMD(): array
    {
        return $this->niveau?->semestres() ?: [1, 2];
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
        if ($this->nombreEtudiantsConnu !== null) {
            return $this->nombreEtudiantsConnu;
        }

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (!$anneeCourante) {
            // Pas d'année courante définie → retourner 0 au lieu de compter toutes les années
            // Ceci évite des incohérences dans le calcul des places disponibles
            \Log::warning("Aucune année universitaire courante définie pour le calcul de nombre_etudiants de la classe {$this->id}");
            return 0;
        }

        $count = $this->inscriptions()->occupeUnePlace($anneeCourante->id)->count();

        // Log pour debugging (à retirer en production)
        if (config('app.debug')) {
            \Log::debug("Classe {$this->id} ({$this->name}): {$count} étudiants actifs pour l'année {$anneeCourante->name}");
        }

        return $count;
    }

    /**
     * Les places prises de chaque classe pour une annee (la courante par
     * defaut), en une requete : la meme regle que nombre_etudiants, pour les
     * ecrans qui listent toutes les classes a la fois. Une classe est
     * universelle : c'est l'annee de l'inscription qui decide des places.
     *
     * @return \Illuminate\Support\Collection<int, int> classe_id => places prises
     */
    public static function placesPrisesParClasse(?int $anneeId = null): \Illuminate\Support\Collection
    {
        $annee = $anneeId ?: ESBTPAnneeUniversitaire::where('is_current', true)->value('id');

        return $annee === null ? collect() : ESBTPInscription::query()->occupeUnePlace((int) $annee)
            ->selectRaw('classe_id, COUNT(*) as n')->groupBy('classe_id')->pluck('n', 'classe_id')
            ->map(fn ($n) => (int) $n);
    }

    /**
     * Places encore disponibles pour une annee donnee (la courante par defaut).
     * Sans capacite reglee, aucune place : l'enregistrement refuse la classe.
     */
    public function placesDisponiblesPour(?int $anneeId = null): int
    {
        $anneeId = $anneeId ?: ESBTPAnneeUniversitaire::where('is_current', true)->value('id');
        // Sans annee courante, rien n'est compte (meme regle que nombre_etudiants), et on le dit.
        if (! $anneeId) {
            \Log::warning("Aucune année universitaire courante définie pour les places de la classe {$this->id}");
        }
        $prises = $anneeId ? $this->inscriptions()->occupeUnePlace((int) $anneeId)->count() : 0;

        return max(0, (int) ($this->places_totales ?? 0) - $prises);
    }

    /**
     * Places encore disponibles dans cette classe.
     *
     * @return int
     */
    public function getPlacesDisponiblesAttribute()
    {
        return $this->placesDisponiblesPour();
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
