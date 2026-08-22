<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class ESBTPAnneeUniversitaire extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * L'année universitaire en cours.
     *
     * `is_active` ne désigne pas l'année courante mais toute année ouverte :
     * l'ESBTP Yamoussoukro en compte dix-sept, jusqu'à 2040-2041. Plusieurs
     * écrans faisaient `where('is_active', true)->first()`, sans tri : la base
     * rendait la plus ancienne, soit 2024-2025, et une page de résultats
     * affichait « aucune note exploitable » pour un étudiant qui en avait
     * dix-sept sur l'année en cours.
     *
     * `is_current` est le drapeau qui désigne l'année courante — c'est déjà ce
     * que font les commandes du projet. On retombe sur la plus récente des
     * années ouvertes si aucune n'est marquée, plutôt que sur la plus ancienne.
     */
    public static function anneeCourante(): ?self
    {
        return static::query()->where('is_current', true)->first()
            ?? static::query()->where('is_active', true)->orderByDesc('annee_debut')->first();
    }

    /** Clé de cache pour l'année courante (TTL court : tenants stables). */
    public const CURRENT_CACHE_KEY = 'esbtp:annee_universitaire:current';

    /** Scope : filtre l'année universitaire active (`is_current = true`). */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    /**
     * Récupère l'année universitaire en cours, avec cache 10 min.
     * Utilisé partout où on faisait `Annee::where('is_current', true)->first()`.
     */
    public static function getCurrent(): ?self
    {
        return Cache::remember(self::CURRENT_CACHE_KEY, 600, fn () => static::query()->current()->first());
    }

    /** À appeler après un changement d'année courante (admin only). */
    public static function flushCurrentCache(): void
    {
        Cache::forget(self::CURRENT_CACHE_KEY);
    }

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_annee_universitaires';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_current',
        'is_active',
        'description',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Alias d'accesseurs : du code legacy lit `$annee->date_debut` et `$annee->date_fin`
     * alors que les colonnes DB sont `start_date` / `end_date`. Ces accesseurs évitent
     * de propager le rename à travers BulletinService + ESBTPAbsenceService + plusieurs
     * vues. Spam de logs "Dates de l'année universitaire non définies" éliminé.
     */
    public function getDateDebutAttribute()
    {
        return $this->start_date;
    }

    public function getDateFinAttribute()
    {
        return $this->end_date;
    }

    public function getDisplayNameAttribute(): string
    {
        $name = trim((string) $this->name);
        if ($name !== '' && $name !== '-') {
            return $name;
        }

        $libelle = trim((string) ($this->attributes['libelle'] ?? null));
        if ($libelle !== '' && $libelle !== '-') {
            return $libelle;
        }

        $startYear = $this->normalizeYearPart($this->getRawOriginal('annee_debut'))
            ?? $this->normalizeYearPart($this->attributes['start_date'] ?? null);
        $endYear = $this->normalizeYearPart($this->getRawOriginal('annee_fin'))
            ?? $this->normalizeYearPart($this->attributes['end_date'] ?? null);

        if ($startYear && $endYear) {
            return "{$startYear}-{$endYear}";
        }

        if ($startYear) {
            return (string) $startYear;
        }

        return 'Année #'.$this->id;
    }

    private function normalizeYearPart(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y');
        }

        $text = trim((string) $value);
        if ($text === '' || $text === '-') {
            return null;
        }

        if (preg_match('/^\d{4}/', $text, $matches)) {
            return $matches[0];
        }

        return $text;
    }

    /**
     * Obtenir les inscriptions associées à cette année universitaire.
     *
     * Une année universitaire peut avoir plusieurs inscriptions.
     * Par exemple, l'année 2024-2025 peut avoir plusieurs étudiants inscrits.
     */
    public function inscriptions()
    {
        return $this->hasMany(ESBTPInscription::class, 'annee_universitaire_id');
    }

    /**
     * Définir cette année universitaire comme l'année en cours.
     * Cette méthode désactive également toutes les autres années universitaires.
     * 
     * @return bool
     */
    public function setAsCurrent()
    {
        return \DB::transaction(function () {
            // Désactiver toutes les autres années universitaires
            self::where('id', '!=', $this->id)
                ->update(['is_current' => false]);

            // Définir cette année comme l'année en cours
            $result = self::where('id', $this->id)->update(['is_current' => true]);
            
            // Effacer le cache si nécessaire
            \Cache::flush();
            
            return $result > 0;
        });
    }
}
