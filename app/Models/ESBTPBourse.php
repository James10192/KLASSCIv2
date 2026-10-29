<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPBourse extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_bourses';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'etudiant_id',
        'annee_universitaire_id',
        'type_bourse',
        'montant',
        'pourcentage',
        'date_debut',
        'date_fin',
        'statut',
        'organisme_financeur',
        'conditions',
        'commentaires',
        'createur_id',
    ];

    /**
     * Relation avec l'étudiant bénéficiaire de la bourse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    /**
     * Relation avec l'année universitaire.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    /**
     * Relation avec l'utilisateur qui a créé la bourse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createur()
    {
        return $this->belongsTo(User::class, 'createur_id');
    }
}
