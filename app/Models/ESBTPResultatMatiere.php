<?php

namespace App\Models;

use App\Services\AppreciationScaleService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPResultatMatiere extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_resultats_matieres';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'bulletin_id',
        'matiere_id',
        'moyenne',
        'coefficient',
        'rang',
        'appreciation',
        'created_by',
        'updated_by'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'moyenne' => 'decimal:2',
        'coefficient' => 'integer'
    ];

    /**
     * Indique si les timestamps sont utilisés.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Relation avec le bulletin associé à ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bulletin()
    {
        return $this->belongsTo(ESBTPBulletin::class, 'bulletin_id');
    }

    /**
     * Relation avec la matière associée à ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    /**
     * Relation avec le créateur de ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec le mise à jour de ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtenir la moyenne pondérée (moyenne * coefficient).
     *
     * @return float
     */
    public function getMoyennePondereeAttribute()
    {
        return round($this->moyenne * $this->coefficient, 2);
    }

    /**
     * Obtenir la mention associée à la moyenne.
     *
     * @return string
     */
    public function getMentionAttribute()
    {
        return app(AppreciationScaleService::class)->labelFor($this->moyenne === null ? null : (float) $this->moyenne, 'bts');
    }

    /**
     * Déterminer l'appréciation associée à la moyenne.
     *
     * @return string
     */
    public function determinerAppreciation()
    {
        return app(AppreciationScaleService::class)->labelFor($this->moyenne === null ? null : (float) $this->moyenne, 'bts');
    }
}
