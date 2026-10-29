<?php

namespace App\Models\ESBTP;

use Illuminate\Database\Eloquent\Model;

class ESBTPNiveauEtude extends Model
{
    protected $table = 'esbtp_niveau_etudes';
    protected $fillable = ['name'];
}
