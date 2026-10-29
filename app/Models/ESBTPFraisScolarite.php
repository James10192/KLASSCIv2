<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPFraisScolarite extends Model
{
    use SoftDeletes;

    protected $table = 'esbtp_frais_scolarite';

    protected $guarded = ['id'];

    protected $casts = [
        'montant_total' => 'decimal:2',
        'frais_inscription' => 'decimal:2',
        'nombre_tranches' => 'integer',
    ];

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_etude_id');
    }

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }
}
