<?php

namespace Tests\Unit\Routes;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Les routes d'ecriture des classes doivent etre ouvertes a la meme audience que la
 * lecture, l'autorisation reelle etant portee par `classes.create|edit|delete`.
 *
 * Historique : create/store/edit/update/destroy vivaient derriere le portail grossier
 * `permission:admin.access`, que les profils de scolarite (serviceScolarite,
 * responsableScolarite) n'ont pas. Le bouton « Nouvelle classe » s'affichait via
 * `classes.create` mais le formulaire AJAX repondait 403, sans rien afficher.
 */
class ClassesEcritureMemeAudienceQueLectureTest extends TestCase
{
    /** Les portails « grossiers » (audience) d'une route, hors permissions fines. */
    private function portailAudience(string $nomRoute): string
    {
        $route = Route::getRoutes()->getByName($nomRoute);
        $this->assertNotNull($route, "Route introuvable : {$nomRoute}");

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            // L'alias (`permission:...`) ou la classe resolue selon le contexte.
            if (! str_starts_with($middleware, 'permission:') && ! str_contains($middleware, 'PermissionMiddleware:')) {
                continue;
            }

            $arguments = explode(':', $middleware)[1] ?? '';

            // Le portail d'audience est celui qui liste des identites / admin.access ;
            // les permissions fines (classes.*) ne sont pas des portails d'audience.
            if (str_contains($arguments, 'admin.access')) {
                return $arguments;
            }
        }

        $this->fail("Aucun portail d'audience trouve sur la route {$nomRoute}");
    }

    public function test_les_routes_d_ecriture_partagent_l_audience_de_la_lecture(): void
    {
        $audienceLecture = $this->portailAudience('esbtp.classes.index');

        foreach ([
            'esbtp.classes.create',
            'esbtp.classes.store',
            'esbtp.classes.edit',
            'esbtp.classes.update',
            'esbtp.classes.destroy',
        ] as $nomRoute) {
            $this->assertSame(
                $audienceLecture,
                $this->portailAudience($nomRoute),
                "La route {$nomRoute} n'a pas la meme audience que esbtp.classes.index : "
                .'un utilisateur pourrait voir la page et le bouton sans pouvoir agir.'
            );
        }
    }

    public function test_l_autorisation_reelle_reste_portee_par_les_permissions_fines(): void
    {
        $attendu = [
            'esbtp.classes.create' => 'classes.create',
            'esbtp.classes.store' => 'classes.create',
            'esbtp.classes.edit' => 'classes.edit',
            'esbtp.classes.update' => 'classes.edit',
            'esbtp.classes.destroy' => 'classes.delete',
        ];

        foreach ($attendu as $nomRoute => $permission) {
            $route = Route::getRoutes()->getByName($nomRoute);
            $this->assertNotNull($route, "Route introuvable : {$nomRoute}");

            $this->assertContains(
                $permission,
                array_map(
                    fn ($middleware) => is_string($middleware) && str_contains($middleware, ':')
                        ? explode(':', $middleware, 2)[1]
                        : $middleware,
                    $route->gatherMiddleware()
                ),
                "La route {$nomRoute} doit exiger la permission {$permission}."
            );
        }
    }
}
