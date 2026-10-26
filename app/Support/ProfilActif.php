<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Le profil actif est une préférence d'affichage, PAS une autorisation.
 * Ni Spatie ni Gate ne doivent accorder un droit à partir de ce choix.
 */
final class ProfilActif
{
    public const ROLE = 'klassci.profil_actif.role';
    public const UTILISATEUR = 'klassci.profil_actif.utilisateur';

    /** @return list<string> */
    public static function disponibles(User $user): array
    {
        return $user->getRoleNames()->unique()->sort()->values()->all();
    }

    public static function doitChoisir(User $user): bool
    {
        return count(self::disponibles($user)) > 1;
    }

    public static function courant(Request $request): ?string
    {
        // Le composer mobile peut s'exécuter pendant le rendu d'une 404
        // AVANT StartSession (route inexistante). Ne jamais transformer
        // une erreur 404 légitime en 500 par un accès prématuré à la session.
        if (! $request->hasSession()) {
            return null;
        }

        $user = $request->user();
        if (! $user || (int) $request->session()->get(self::UTILISATEUR, 0) !== (int) $user->id) {
            return null;
        }

        $role = $request->session()->get(self::ROLE);

        return is_string($role) && in_array($role, self::disponibles($user), true) ? $role : null;
    }

    public static function oublier(Request $request): void
    {
        $request->session()->forget([self::ROLE, self::UTILISATEUR]);
    }

    public static function choisir(Request $request, string $role): void
    {
        $user = $request->user();
        if (! $user || ! in_array($role, self::disponibles($user), true)) {
            abort(403, 'Ce profil ne vous est pas attribué.');
        }

        $request->session()->put(self::ROLE, $role);
        $request->session()->put(self::UTILISATEUR, $user->id);
    }
}
