<?php

namespace App\Models;

use App\Services\AppreciationScaleService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPBulletinDetail extends Model
{
    use HasFactory;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_bulletin_details';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'bulletin_id',
        'matiere_id',
        'note_cc', 
        'note_examen',
        'moyenne',
        'moyenne_classe',
        'coefficient',
        'credits',
        'credits_valides',
        'rang',
        'effectif',
        'appreciation',
        'observations'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'note_cc' => 'float',
        'note_examen' => 'float',
        'moyenne' => 'float',
        'moyenne_classe' => 'float',
        'coefficient' => 'float',
        'credits' => 'integer',
        'credits_valides' => 'integer',
        'rang' => 'integer',
        'effectif' => 'integer'
    ];

    /**
     * Relation avec le bulletin associé à ce détail.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bulletin()
    {
        return $this->belongsTo(ESBTPBulletin::class, 'bulletin_id');
    }

    /**
     * Relation avec la matière associée à ce détail.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
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
     * Vérifier si la matière est validée.
     *
     * @return bool
     */
    public function getIsValideeAttribute()
    {
        return $this->moyenne >= 10;
    }

    /**
     * Calculer le nombre de points (moyenne * coefficient).
     *
     * @return float
     */
    public function getPointsAttribute()
    {
        return round($this->moyenne * $this->coefficient, 2);
    }
} 
