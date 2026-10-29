<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Le numéro d'anonymat d'un étudiant pour un examen. */
class ESBTPExamenAnonymat extends Model
{
    protected $table = 'esbtp_examen_anonymats';

    protected $fillable = ['examen_planifie_id', 'etudiant_id', 'numero'];

    public function examen(): BelongsTo
    {
        return $this->belongsTo(ESBTPExamenPlanifie::class, 'examen_planifie_id');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }
}
