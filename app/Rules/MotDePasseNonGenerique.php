<?php

namespace App\Rules;

use App\Services\UserService;
use Illuminate\Contracts\Validation\InvokableRule;

/**
 * Refuse, comme nouveau mot de passe, le mot de passe generique pose a la
 * creation et a la reinitialisation d'un compte.
 *
 * Sans cette regle, l'ecran de premiere connexion acceptait que l'on
 * ressaisisse le mot de passe par defaut : le garde-fou « changement
 * obligatoire » etait franchi sans que rien ne change, et le compte restait
 * ouvert avec une valeur connue de toute la clientele.
 *
 * Deux verifications, pour deux raisons :
 * - l'egalite avec `UserService::defaultPassword()`, seule source de la
 *   valeur courante — si une ecole la configure autrement, la regle suit ;
 * - le motif « Bonjour@<annee> », qui rattrape les comptes reinitialises avant
 *   l'unification de cette valeur, quand chaque reset ecrivait une annee
 *   revolue.
 *
 * `InvokableRule` et non `ValidationRule` : ce dernier n'existe qu'a partir de
 * Laravel 10, le projet tourne en 9.52.
 */
class MotDePasseNonGenerique implements InvokableRule
{
    private const MOTIF_HISTORIQUE = '/^bonjour@\d{4}$/i';

    /**
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function __invoke($attribute, $value, $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $valeur = trim($value);

        if (strcasecmp($valeur, UserService::defaultPassword()) === 0 || preg_match(self::MOTIF_HISTORIQUE, $valeur) === 1) {
            $fail('Ce mot de passe est celui attribué par défaut : choisissez-en un qui vous soit propre.');
        }
    }
}
