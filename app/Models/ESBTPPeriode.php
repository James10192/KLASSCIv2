<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class ESBTPPeriode
 *
 * Modèle pour les périodes académiques génériques (semestres, trimestres, etc.)
 *
 * Relations:
 * - belongsTo: ESBTPAnneeUniversitaire
 * - hasMany: ESBTPPlanificationAcademique, ESBTPEmploiTemps, ESBTPEvaluation, ESBTPNote,
 *            ESBTPResultat, ESBTPBulletin, ESBTPConfigMatiere, ESBTPConfigMatiereTypeFormation
 *
 * @property int $id
 * @property int $annee_universitaire_id
 * @property string $nom
 * @property int $ordre
 * @property \Carbon\Carbon $date_debut
 * @property \Carbon\Carbon $date_fin
 * @property int $poids
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read ESBTPAnneeUniversitaire $anneeUniversitaire
 */
class ESBTPPeriode extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'esbtp_periodes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'annee_universitaire_id',
        'nom',
        'ordre',
        'date_debut',
        'date_fin',
        'poids',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'poids' => 'integer',
        'is_active' => 'boolean',
        'ordre' => 'integer',
    ];

    /**
     * Get the année universitaire that owns the période.
     *
     * @return BelongsTo
     */
    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    /**
     * Get the planifications académiques for the période.
     *
     * @return HasMany
     */
    public function planificationsAcademiques(): HasMany
    {
        return $this->hasMany(ESBTPPlanificationAcademique::class, 'periode_id');
    }

    /**
     * Get the emplois du temps for the période.
     *
     * @return HasMany
     */
    public function emploisTemps(): HasMany
    {
        return $this->hasMany(ESBTPEmploiTemps::class, 'periode_id');
    }

    /**
     * Get the évaluations for the période.
     *
     * @return HasMany
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(ESBTPEvaluation::class, 'periode_id');
    }

    /**
     * Get the notes for the période.
     *
     * @return HasMany
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ESBTPNote::class, 'periode_id');
    }

    /**
     * Get the résultats for the période.
     *
     * @return HasMany
     */
    public function resultats(): HasMany
    {
        return $this->hasMany(ESBTPResultat::class, 'periode_id');
    }

    /**
     * Get the bulletins for the période.
     *
     * @return HasMany
     */
    public function bulletins(): HasMany
    {
        return $this->hasMany(ESBTPBulletin::class, 'periode_id');
    }

    /**
     * Get the config matières for the période.
     *
     * @return HasMany
     */
    public function configMatieres(): HasMany
    {
        return $this->hasMany(ESBTPConfigMatiere::class, 'periode_id');
    }

    /**
     * Get the config matière type formations for the période.
     *
     * @return HasMany
     */
    public function configMatiereTypeFormations(): HasMany
    {
        return $this->hasMany(ESBTPConfigMatiereTypeFormation::class, 'periode_id');
    }

    /**
     * Scope a query to only include active périodes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order périodes by ordre.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre', 'asc');
    }

    /**
     * Scope a query to only include périodes for a specific année universitaire.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $anneeUniversitaireId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAnneeUniversitaire($query, $anneeUniversitaireId)
    {
        return $query->where('annee_universitaire_id', $anneeUniversitaireId);
    }

    /**
     * Get the durée (durée en jours) de la période.
     *
     * @return int
     */
    public function getDureeAttribute(): int
    {
        return $this->date_debut->diffInDays($this->date_fin);
    }

    /**
     * Check if the période est en cours (date du jour entre date_debut et date_fin).
     *
     * @return bool
     */
    public function isEnCours(): bool
    {
        $today = today();
        return $today->between($this->date_debut, $this->date_fin);
    }

    /**
     * Check if the période est passée (date_fin < aujourd'hui).
     *
     * @return bool
     */
    public function isPassee(): bool
    {
        return $this->date_fin->isPast();
    }

    /**
     * Check if the période est à venir (date_debut > aujourd'hui).
     *
     * @return bool
     */
    public function isAVenir(): bool
    {
        return $this->date_debut->isFuture();
    }
}
