<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ce qui a ete recu, pour UNE inscription.
 *
 * L'etat vit sur l'inscription : l'ecole reprend un exemplaire de chaque piece
 * a chaque annee pour les ministeres, donc trois annees d'etudes donnent trois
 * lignes « extrait de naissance ».
 */
class ESBTPInscriptionPiece extends Model
{
    use HasFactory;

    protected $table = 'esbtp_inscription_pieces';

    protected $fillable = [
        'inscription_id',
        'piece_code',
        'piece_id',
        'est_fournie',
        'fournie_le',
        'marquee_par',
        'observation',
    ];

    protected $casts = [
        'est_fournie' => 'boolean',
        'fournie_le' => 'date',
    ];

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_id');
    }

    public function piece(): BelongsTo
    {
        return $this->belongsTo(ESBTPPieceDossier::class, 'piece_id');
    }

    public function marqueePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marquee_par');
    }
}
