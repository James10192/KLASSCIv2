<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ESBTPEnseignantPresence extends Model
{
    protected $table = 'esbtp_enseignant_presence';

    protected $fillable = [
        'enseignant_id',
        'matiere_id',
        'date',
        'heure_arrivee',
        'heure_depart',
        'statut',
        'remarques',
        'adresse_ip',
        'info_appareil'
    ];

    protected $casts = [
        'date' => 'date',
        'heure_arrivee' => 'datetime',
        'heure_depart' => 'datetime'
    ];

    public function enseignant()
    {
        return $this->belongsTo(User::class, 'enseignant_id');
    }

    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }
}
