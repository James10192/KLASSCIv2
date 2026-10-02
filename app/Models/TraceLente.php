<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Une action qui a dépassé son seuil, ou qui a échoué. Écrite par
 * {@see \App\Domain\Exploitation\TracesLentes\EnregistreurDeTraces}, lue par
 * GET /api/cli/traces/lentes, purgée à trente jours (traces:purger).
 */
class TraceLente extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'traces_lentes';

    protected $fillable = [
        'type', 'nom', 'duree_ms', 'requetes_sql', 'temps_sql_ms', 'memoire_mo', 'code', 'user_id', 'details',
    ];

    protected $casts = [
        'details' => 'array',
        'duree_ms' => 'integer',
        'requetes_sql' => 'integer',
        'temps_sql_ms' => 'integer',
        'memoire_mo' => 'integer',
        'code' => 'integer',
    ];
}
