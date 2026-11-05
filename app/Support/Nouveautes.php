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
     * Entrées montrées au plus, les plus récentes en tête du fichier. La
     * fenêtre en affichait près de quarante : on ne lit pas un historique
     * dans une fenêtre d'accueil.
     */
    public const LIMITE = 8;

    /**
     * @return array{titre: string, entrees: list<array<string, mixed>>}
     */
    public static function pour(?User $utilisateur, ?array $contenu = null): array
    {
        $contenu ??= require resource_path('data/nouveautes.php');

        $entrees = array_values(array_filter(
            $contenu['entrees'] ?? [],
            function (array $entree) use ($utilisateur): bool {
                if (! self::disponible($entree['si'] ?? null, $utilisateur)) {
                    return false;
                }

                $permissions = (array) ($entree['permissions'] ?? []);
                if ($permissions === []) {
                    return true;
                }

                return $utilisateur !== null && $utilisateur->canAny($permissions);
            }
        ));

        // Filtrer d'abord, limiter ensuite : chacun voit SES huit dernières nouveautés.
        return ['titre' => (string) ($contenu['titre'] ?? ''), 'entrees' => array_slice($entrees, 0, self::LIMITE)];
    }

    /**
     * Une nouveaute qui annonce un ecran desactive dans cette ecole n'est pas
     * montree : on n'annonce pas un bouton absent. La cle `si` nomme la
     * condition ; une condition inconnue masque l'entree plutot que de l'ouvrir.
     */
    private static function disponible(?string $condition, ?User $utilisateur): bool
    {
        return match ($condition) {
            null => true,
            'aide' => app(\App\Domain\Support\Services\DisponibiliteSupport::class)->signalement(),
            'reclamations' => app(\App\Domain\Notes\Reclamations\ReglagesReclamations::class)->actives(),
            // Le bandeau des notes LMD suit la même règle que sa route : pas d'enseignant seul.
            'requalification_examen' => $utilisateur !== null
                && \App\Domain\Notes\RequalificationEnExamen::refusPour($utilisateur) === null,
            default => false,
        };
    }
}
