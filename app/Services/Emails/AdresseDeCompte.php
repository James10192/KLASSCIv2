<?php

namespace App\Services\Emails;

use App\Models\User;

/**
 * L'adresse a poser sur un compte utilisateur cree automatiquement.
 *
 * Jusqu'ici KLASSCI fabriquait `<identifiant>@esbtp.edu` pour chaque compte :
 * un domaine qui n'existe pas, sur lequel partaient ensuite notifications et
 * convocations, perdues sans un mot. Desormais : l'adresse personnelle si elle
 * est joignable et libre, sinon rien. `users.email` est nullable, la connexion
 * se fait par identifiant.
 *
 * Pas de verification DNS ici : on est au milieu d'une inscription, et la
 * saisie a deja franchi la regle EmailJoignable.
 */
class AdresseDeCompte
{
    public function __construct(private readonly AnalyseurEmail $analyseur) {}

    public function pour(?string $emailPersonnel): ?string
    {
        $email = trim((string) $emailPersonnel);
        if ($email === '' || ! $this->analyseur->analyser($email)->joignable()) {
            return null;
        }

        return User::withTrashed()->where('email', $email)->exists() ? null : $email;
    }
}
