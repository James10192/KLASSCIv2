<?php

namespace App\Support;

use App\Models\User;

/**
 * Lit resources/data/nouveautes.php et ne garde que les entrées qui
 * s'adressent au compte connecté.
 */
class Nouveautes
{
    /**
     * @return array{titre: string, entrees: list<array<string, mixed>>}
     */
    public static function pour(?User $utilisateur, ?array $contenu = null): array
    {
        $contenu ??= require resource_path('data/nouveautes.php');

        $entrees = array_values(array_filter(
            $contenu['entrees'] ?? [],
            function (array $entree) use ($utilisateur): bool {
                $permissions = (array) ($entree['permissions'] ?? []);
                if ($permissions === []) {
                    return true;
                }

                return $utilisateur !== null && $utilisateur->canAny($permissions);
            }
        ));

        return ['titre' => (string) ($contenu['titre'] ?? ''), 'entrees' => $entrees];
    }
}
