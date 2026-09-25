<?php

namespace App\Casts;

use App\Support\TexteRiche;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Nettoie le HTML de l'éditeur riche à l'ENREGISTREMENT, quel que soit le
 * chemin d'écriture (formulaire, API CLI, import) : aucun contrôleur n'a à
 * y penser. La lecture rend la valeur stockée telle quelle ; l'affichage passe
 * par TexteRiche::afficher(), qui renettoie les valeurs antérieures au cast.
 */
class TexteRicheCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        return $value;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return TexteRiche::nettoyer($value === null ? null : (string) $value);
    }
}
