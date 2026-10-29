<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPSystemSetting extends Model
{
    use HasFactory;

    protected $table = 'esbtp_system_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'description'
    ];

    /**
     * Obtenir une valeur de configuration
     */
    public static function getValue($key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Définir une valeur de configuration
     */
    public static function setValue($key, $value, $type = null)
    {
        if ($type === null) {
            $type = self::guessType($value);
        }

        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) || is_object($value) ? json_encode($value) : $value,
                'type' => $type
            ]
        );
    }

    /**
     * Convertir la valeur selon le type
     */
    protected static function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
            case 'integer':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Deviner le type d'une valeur
     */
    protected static function guessType($value)
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'float';
        }
        if (is_array($value) || is_object($value)) {
            return 'json';
        }
        return 'string';
    }

    /**
     * Vérifier si le mode matricule est automatique
     */
    public static function isMatriculeAutomatic()
    {
        return self::getValue('matricule_mode', 'automatique') === 'automatique';
    }

    /**
     * Obtenir l'établissement actuel
     */
    public static function getCurrentEtablissementId()
    {
        return self::getValue('current_etablissement_id', 1);
    }
}