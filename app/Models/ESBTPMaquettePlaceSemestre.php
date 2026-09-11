<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * La place d'une matiere sur le bulletin d'UN semestre donne.
 *
 * Ne sert que lorsqu'une matiere est enseignee aux deux semestres et n'y
 * occupe pas le meme rang. Le reste du temps, la place du pivot
 * `esbtp_matiere_filiere_niveau` suffit et cette table reste vide.
 *
 * @see \App\Domain\BtsTroncCommun\BulletinSubjectOrder
 */
class ESBTPMaquettePlaceSemestre extends Model
{
    protected $table = 'esbtp_maquette_places_semestre';

    protected $fillable = [
        'matiere_id',
        'filiere_id',
        'niveau_etude_id',
        'semestre',
        'ordre_bulletin',
    ];

    protected $casts = [
        'matiere_id' => 'integer',
        'filiere_id' => 'integer',
        'niveau_etude_id' => 'integer',
        'semestre' => 'integer',
        'ordre_bulletin' => 'integer',
    ];

    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }
}
