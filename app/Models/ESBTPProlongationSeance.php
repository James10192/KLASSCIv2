<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Une demande de prolongation d'UNE occurrence de séance (séance + date).
 *
 * L'enseignant demande, une personne habilitée (`emargement.prolongation.decide`)
 * accorde ou refuse après contrôle des conflits sur le créneau ajouté.
 */
class ESBTPProlongationSeance extends Model
{
    protected $table = 'esbtp_prolongations_seance';

    public const EN_ATTENTE = 'en_attente';
    public const ACCORDEE = 'accordee';
    public const REFUSEE = 'refusee';
    public const ANNULEE = 'annulee';

    public const LIBELLES = [
        self::EN_ATTENTE => 'En attente',
        self::ACCORDEE => 'Accordée',
        self::REFUSEE => 'Refusée',
        self::ANNULEE => 'Annulée',
    ];

    protected $fillable = [
        'seance_cours_id', 'date', 'heure_fin_initiale', 'heure_fin_demandee', 'minutes',
        'motif', 'statut', 'demandee_par', 'decidee_par', 'decidee_le', 'motif_decision', 'conflits',
    ];

    protected $casts = [
        'date' => 'date',
        'decidee_le' => 'datetime',
        'conflits' => 'array',
        'minutes' => 'integer',
    ];

    public function seance()
    {
        return $this->belongsTo(ESBTPSeanceCours::class, 'seance_cours_id');
    }

    public function demandeur()
    {
        return $this->belongsTo(User::class, 'demandee_par');
    }

    public function decideur()
    {
        return $this->belongsTo(User::class, 'decidee_par');
    }

    public function libelleStatut(): string
    {
        return self::LIBELLES[$this->statut] ?? $this->statut;
    }
}
