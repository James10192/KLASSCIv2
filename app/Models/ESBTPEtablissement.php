<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPEtablissement extends Model
{
    use HasFactory;

    protected $table = 'esbtp_etablissements';

    protected $fillable = [
        'code',
        'nom',
        'ville',
        'code_court',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Configurations matricules liées à cet établissement
     */
    public function matriculeConfigs()
    {
        return $this->hasMany(ESBTPMatriculeConfig::class, 'etablissement_id');
    }

    /**
     * Scope pour les établissements actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Obtenir l'établissement par défaut/actuel
     */
    public static function getCurrentEtablissement()
    {
        $currentId = ESBTPSystemSetting::getValue('current_etablissement_id', 1);
        return self::find($currentId);
    }

    /**
     * Définir l'établissement actuel
     */
    public static function setCurrentEtablissement($etablissementId)
    {
        ESBTPSystemSetting::setValue('current_etablissement_id', $etablissementId);
    }
}