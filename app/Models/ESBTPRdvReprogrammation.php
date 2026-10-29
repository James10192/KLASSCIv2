<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un rendez-vous deplace au guichet. Journal en ajout seul : rien ne le
 * modifie apres coup, pour que le jour quitte garde la trace de la famille.
 */
class ESBTPRdvReprogrammation extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'esbtp_rdv_reprogrammations';

    protected $fillable = ['reservation_id', 'creneau_quitte_id', 'creneau_nouveau_id', 'non_venue', 'par'];

    protected $casts = ['non_venue' => 'boolean'];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(ESBTPRdvReservation::class, 'reservation_id');
    }

    public function creneauQuitte(): BelongsTo
    {
        return $this->belongsTo(ESBTPRdvCreneau::class, 'creneau_quitte_id');
    }
}
