<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ESBTPMatiereCoefficient extends Model
{
    protected $table = 'esbtp_matiere_coefficients';

    protected $fillable = [
        'matiere_id',
        'filiere_id',
        'niveau_etude_id',
        'annee_universitaire_id',
        'periode',
        'coefficient',
        'created_by',
        'updated_by',
    ];

    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    public function niveauEtude()
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_etude_id');
    }

    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }
}
