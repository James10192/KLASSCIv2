<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPAnnonce extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_annonces';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'titre',
        'contenu',
        'type',
        'date_publication',
        'date_expiration',
        'priorite',
        'is_published',
        'created_by',
        'updated_by'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'date_publication' => 'datetime',
        'date_expiration' => 'datetime',
        'priorite' => 'integer',
        'is_published' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relation avec les classes destinataires de cette annonce.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function classes()
    {
        return $this->belongsToMany(ESBTPClasse::class, 'esbtp_annonce_classe', 'annonce_id', 'classe_id')
                    ->withTimestamps();
    }

    /**
     * Relation avec les étudiants destinataires de cette annonce.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function etudiants()
    {
        return $this->belongsToMany(ESBTPEtudiant::class, 'esbtp_annonce_etudiant', 'annonce_id', 'etudiant_id')
                    ->withPivot('is_read', 'read_at')
                    ->withTimestamps();
    }

    /**
     * Relation avec l'utilisateur qui a créé l'annonce.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour l'annonce.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relation avec l'utilisateur qui a créé l'annonce (alias de createdBy).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec les étudiants qui ont lu cette annonce (pour les annonces générales et de classe).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function lectures()
    {
        return $this->belongsToMany(ESBTPEtudiant::class, 'esbtp_annonce_lectures', 'annonce_id', 'etudiant_id')
                    ->withPivot('read_at')
                    ->withTimestamps();
    }

    /**
     * Récupère les annonces actives (publiées et non expirées).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActives($query)
    {
        $now = now();
        return $query->where('is_published', true)
                     ->where('date_publication', '<=', $now)
                     ->where(function ($q) use ($now) {
                         $q->whereNull('date_expiration')
                           ->orWhere('date_expiration', '>=', $now);
                     });
    }

    /**
     * Récupère les annonces destinées à toutes les classes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGlobales($query)
    {
        return $query->where('type', 'globale');
    }

    /**
     * Récupère les annonces destinées à des classes spécifiques.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParClasses($query)
    {
        return $query->where('type', 'classe');
    }

    /**
     * Récupère les annonces destinées à des étudiants spécifiques.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParEtudiants($query)
    {
        return $query->where('type', 'etudiant');
    }

    /**
     * Marque l'annonce comme lue pour l'étudiant spécifié.
     *
     * @param int $etudiantId
     * @return void
     */
    public function marquerCommeLue($etudiantId)
    {
        $this->etudiants()->updateExistingPivot($etudiantId, [
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    /**
     * Détermine si l'annonce est active (publiée et non expirée).
     *
     * @return bool
     */
    public function isActive()
    {
        $now = now();
        return $this->is_published
            && $this->date_publication <= $now
            && ($this->date_expiration === null || $this->date_expiration >= $now);
    }

    /**
     * Détermine si l'annonce est expirée.
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->date_expiration && $this->date_expiration < now();
    }

    /**
     * Détermine si l'annonce peut être modifiée.
     * Une annonce ne peut plus être modifiée si elle est expirée.
     *
     * @return bool
     */
    public function canBeEdited()
    {
        return !$this->isExpired();
    }
}
