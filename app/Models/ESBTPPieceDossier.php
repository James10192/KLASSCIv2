<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une ligne du catalogue des pieces attendues au dossier d'inscription.
 *
 * Une ligne sans filiere ni niveau est le defaut de l'etablissement ; une ligne
 * portant une filiere et/ou un niveau prime sur ce defaut pour ce perimetre.
 */
class ESBTPPieceDossier extends Model
{
    use HasFactory;

    protected $table = 'esbtp_pieces_dossier';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'est_obligatoire',
        'filiere_id',
        'niveau_id',
        'ordre',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'est_obligatoire' => 'boolean',
        'is_active' => 'boolean',
        'ordre' => 'integer',
    ];

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_id');
    }

    /**
     * Poids de la portee, du plus general au plus precis.
     *
     * Sert a departager deux lignes de meme code : la plus precise gagne. On
     * classe explicitement filiere avant niveau parce qu'une filiere decrit un
     * dossier (un transfert, une formation continue) la ou le niveau ne decrit
     * qu'une annee d'etude.
     */
    public function precisionPortee(): int
    {
        return ($this->filiere_id ? 2 : 0) + ($this->niveau_id ? 1 : 0);
    }
}
