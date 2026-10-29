<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ESBTPCategoriePaiement extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_categorie_paiements';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'nom',
        'code',
        'slug',
        'description',
        'icone',
        'couleur',
        'est_actif',
        'est_obligatoire',
        'parent_id',
        'ordre',
    ];

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array
     */
    protected $casts = [
        'est_actif' => 'boolean',
        'est_obligatoire' => 'boolean',
        'ordre' => 'integer',
    ];

    /**
     * Définir les événements du modèle.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->nom);
            }
            if (empty($model->code)) {
                $model->code = Str::upper(Str::substr(Str::slug($model->nom, ''), 0, 10));
            }
        });
    }

    /**
     * Relation avec les paiements.
     */
    public function paiements()
    {
        return $this->hasMany(ESBTPPaiement::class, 'categorie_id');
    }

    /**
     * Relation avec la catégorie parente.
     */
    public function parent()
    {
        return $this->belongsTo(ESBTPCategoriePaiement::class, 'parent_id');
    }

    /**
     * Relation avec les sous-catégories.
     */
    public function enfants()
    {
        return $this->hasMany(ESBTPCategoriePaiement::class, 'parent_id');
    }

    /**
     * Scope pour obtenir uniquement les catégories actives.
     */
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    /**
     * Scope pour obtenir uniquement les catégories parentes (niveau supérieur).
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope pour ordonner les catégories par ordre.
     */
    public function scopeOrdre($query)
    {
        return $query->orderBy('ordre');
    }

    /**
     * Déterminer si la catégorie est une catégorie parente.
     */
    public function estParent()
    {
        return is_null($this->parent_id);
    }

    /**
     * Obtenir le style CSS pour l'icône (couleur).
     */
    public function getStyleIconeAttribute()
    {
        return 'color: ' . $this->couleur . ';';
    }

    /**
     * Obtenir le style CSS pour un badge de la catégorie.
     */
    public function getStyleBadgeAttribute()
    {
        return 'background-color: ' . $this->couleur . '; color: #ffffff;';
    }

    /**
     * Obtenir le style CSS pour un bouton de la catégorie.
     */
    public function getStyleBoutonAttribute()
    {
        return 'background-color: ' . $this->couleur . '; border-color: ' . $this->couleur . '; color: #ffffff;';
    }

    /**
     * Obtenir le chemin URL vers la page de détails de la catégorie.
     */
    public function getUrlAttribute()
    {
        return route('esbtp.paiements.index', ['categorie' => $this->id]);
    }
}
