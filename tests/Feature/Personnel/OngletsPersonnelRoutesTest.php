<?php

namespace Tests\Feature\Personnel;

use App\Http\Controllers\ESBTPPersonnelUnifiedController;
use App\Support\PorteDeRoute;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Les boutons de la page Personnel lisent la garde de leur route : il faut
 * donc que chaque route nommee dans la matrice existe, et qu'elle se lise.
 *
 * Une faute de frappe dans un nom rendrait la porte « inconnue », et le bouton
 * disparaitrait pour tout le monde, superAdmin compris. C'est bruyant, mais
 * c'est un test qui doit le dire, pas une ecole.
 *
 * Sans base : on presente un porteur qui possede tout, et on verifie que la
 * lecture aboutit a un « oui » — donc que rien d'illisible ne s'est glisse
 * dans la chaine de middlewares, pas meme depuis le constructeur d'un
 * controleur.
 */
class OngletsPersonnelRoutesTest extends TestCase
{
    /**
     * Les adresses que la vue appelle sous le drapeau « edit », en plus de la
     * route de mise a jour que la matrice declare : le bouton actif/inactif.
     * Celle des secretaires ne vit pas sous /esbtp, ce qui a valu un 404.
     */
    private const BASCULES_DE_STATUT = [
        'esbtp.directeurs-etudes.toggle-status',
        'esbtp.coordinateurs.toggle-status',
        'esbtp.enseignants.toggleStatus',
        'secretaires.toggle-status',
        'esbtp.responsables-scolarite.toggle-status',
        'esbtp.services-scolarite.toggle-status',
        'esbtp.agents-inscription.toggle-status',
        'esbtp.comptables.toggle-status',
        'esbtp.caissiers.toggle-status',
    ];

    private function porteurQuiPeutTout(): Authorizable
    {
        return new class implements Authorizable
        {
            public function can($abilities, $arguments = [])
            {
                return true;
            }

            public function cant($abilities, $arguments = [])
            {
                return false;
            }

            public function cannot($abilities, $arguments = [])
            {
                return false;
            }

            public function hasRole($role): bool
            {
                return true;
            }
        };
    }

    private function routeExisteEtSeLit(string $nom, string $contexte, Authorizable $tout): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName($nom), "La route {$nom} ({$contexte}) n'existe pas.");
        $this->assertTrue(PorteDeRoute::verdict($nom, $tout), "La route {$nom} ({$contexte}) ne se lit pas : un bouton disparaitrait pour tout le monde.");
    }

    public function test_chaque_action_de_chaque_onglet_pointe_vers_une_route_existante_et_lisible(): void
    {
        $tout = $this->porteurQuiPeutTout();
        $verifiees = 0;

        foreach (ESBTPPersonnelUnifiedController::TAB_PERMISSIONS as $onglet => $definition) {
            $this->assertArrayHasKey('routes', $definition, "L'onglet {$onglet} ne nomme aucune route.");

            foreach (['create', 'edit'] as $action) {
                $nom = $definition['routes'][$action] ?? null;

                $this->assertNotNull($nom, "L'onglet {$onglet} ne nomme pas de route pour {$action}.");
                $this->routeExisteEtSeLit($nom, "{$onglet}/{$action}", $tout);
                $verifiees++;
            }
        }

        $this->assertSame(18, $verifiees);
    }

    public function test_les_bascules_de_statut_appelees_par_la_vue_existent_et_se_lisent(): void
    {
        $tout = $this->porteurQuiPeutTout();

        foreach (self::BASCULES_DE_STATUT as $nom) {
            $this->routeExisteEtSeLit($nom, 'bascule de statut', $tout);
        }

        $this->assertCount(count(ESBTPPersonnelUnifiedController::TAB_PERMISSIONS), self::BASCULES_DE_STATUT);
    }
}
