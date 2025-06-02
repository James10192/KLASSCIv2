<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPParent extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_parents';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'nom',
        'prenoms',
        'sexe',
        'profession',
        'adresse',
        'telephone',
        'telephone_secondaire',
        'email',
        'type_piece_identite',
        'numero_piece_identite',
        'created_by',
        'updated_by'
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * Relation avec l'utilisateur.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation avec les étudiants.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function etudiants()
    {
        return $this->belongsToMany(ESBTPEtudiant::class, 'esbtp_etudiant_parent', 'parent_id', 'etudiant_id')
                    ->withPivot('relation', 'is_tuteur')
                    ->withTimestamps();
    }

    /**
     * Obtenir les étudiants dont ce parent est tuteur.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function pupilles()
    {
        return $this->etudiants()->wherePivot('is_tuteur', true);
    }

    /**
     * Obtenir le nom complet du parent.
     *
     * @return string
     */
    public function getNomCompletAttribute()
    {
        return $this->prenoms . ' ' . $this->nom;
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
     * Générer un username unique basé sur le prénom et le nom.
     *
     * @param string $prenom
     * @param string $nom
     * @return string
     */
    public static function genererUsername($prenom, $nom)
    {
        // Utiliser la méthode du modèle étudiant pour la cohérence
        return ESBTPEtudiant::genererUsername($prenom, $nom);
    }
} 