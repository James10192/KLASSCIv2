<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPComptabiliteConfiguration extends Model
{
    use HasFactory;
    
    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_comptabilite_configurations';
    
    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'cle',
        'valeur',
        'description',
        'type',
    ];
    
    /**
     * Méthode pour récupérer une configuration par sa clé.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getConfig($key, $default = null)
    {
        $config = self::where('cle', $key)->first();
        
        if (!$config) {
            return $default;
        }
        
        return $config->valeur;
    }
    
    /**
     * Méthode pour définir une configuration.
     *
     * @param string $key
     * @param mixed $value
     * @param string $description
     * @param string $type
     * @return ESBTPComptabiliteConfiguration
     */
    public static function setConfig($key, $value, $description = null, $type = 'string')
    {
        $config = self::firstOrNew(['cle' => $key]);
        $config->valeur = $value;
        
        if ($description) {
            $config->description = $description;
        }
        
        $config->type = $type;
        $config->save();
        
        return $config;
    }
}
