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

    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    public function niveauEtude()
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_etude_id');
    }

    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    public function scopeForCombo($query, $filiereId, $niveauId)
    {
        return $query->where('filiere_id', $filiereId)->where('niveau_etude_id', $niveauId);
    }

    public static function matiereIdsForCombo($filiereId, $niveauId)
    {
        return static::forCombo($filiereId, $niveauId)->pluck('matiere_id');
    }

    public static function activeMatiereCountForCombo($filiereId, $niveauId)
    {
        return static::forCombo($filiereId, $niveauId)
            ->whereHas('matiere', fn($q) => $q->where('is_active', true))
            ->count();
    }
}
