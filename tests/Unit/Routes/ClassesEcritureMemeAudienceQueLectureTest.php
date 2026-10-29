<?php

namespace Tests\Unit\Routes;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Les routes des classes doivent respecter uniquement les permissions métier :
 * classes.view pour consulter, classes.create|edit|delete pour modifier.
 *
 * Historique : des routes d'écriture exigeaient `admin.access` même lorsque
 * l'utilisateur avait le droit précis `classes.create` ou `classes.edit`.
 * L'accès individuel permet aussi de consulter les classes sans ces droits
 * d'écriture : restaurer ce portail casserait cette séparation.
 */
class ClassesEcritureMemeAudienceQueLectureTest extends TestCase
{
    /** @return list<string> */
    private function middlewares(string $nomRoute): array
    {
        $route = Route::getRoutes()->getByName($nomRoute);
        $this->assertNotNull($route, "Route introuvable : {$nomRoute}");

        return array_values(array_filter($route->gatherMiddleware(), 'is_string'));
    }

    public function test_les_routes_de_classes_n_exigent_pas_une_identite_administrative(): void
    {
        foreach ([
            'esbtp.classes.index',
            'esbtp.classes.show',
            'esbtp.classes.create',
            'esbtp.classes.store',
            'esbtp.classes.edit',
            'esbtp.classes.update',
            'esbtp.classes.destroy',
        ] as $nomRoute) {
            foreach ($this->middlewares($nomRoute) as $middleware) {
                $this->assertStringNotContainsString(
                    'admin.access',
                    $middleware,
                    "La route {$nomRoute} impose un portail administratif en plus de la permission ciblée."
                );
                $this->assertStringNotContainsString(
                    'identity.',
                    $middleware,
                    "La route {$nomRoute} impose une identité au-delà de la permission ciblée."
                );
            }
        }

        $this->assertContains('permission:classes.view', $this->middlewares('esbtp.classes.index'));
        $this->assertContains('permission:classes.view', $this->middlewares('esbtp.classes.show'));
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
