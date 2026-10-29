<?php

namespace App\Domain\Support\Models;

use Illuminate\Database\Eloquent\Model;

/** Le dernier etat vu d'une demande KLASSCI Care, pour n'avertir qu'une fois. */
class DemandeSuivie extends Model
{
    protected $table = 'support_demandes_suivies';

    protected $fillable = ['reference', 'user_id', 'statut_code', 'derniere_reponse_support_le', 'mis_a_jour_le', 'averti_le'];

    protected $casts = [
        'derniere_reponse_support_le' => 'datetime',
        'mis_a_jour_le' => 'datetime',
        'averti_le' => 'datetime',
    ];
}
