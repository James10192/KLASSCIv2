<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ESBTPMatiereFilierNiveau extends Model
{
    protected $table = 'esbtp_matiere_filiere_niveau';

    protected $fillable = [
        'matiere_id',
        'filiere_id',
        'niveau_etude_id',
    ];
}
