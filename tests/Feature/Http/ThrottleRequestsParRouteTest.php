<?php

namespace Tests\Feature\Http;

use App\Http\Middleware\ThrottleRequestsParRoute;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Un `throttle:N,1` sans nom comptait sur l'utilisateur seul : toutes les routes
 * limitées partageaient un seau. Mesuré sur presentation : ouvrir 32 grilles de
 * notes suffisait à faire refuser l'enregistrement d'une note.
 */
class ThrottleRequestsParRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Le contrôle d'installation global redirige une base de test vide ;
        // il n'a rien à voir avec la limitation mesurée ici.
        $this->withoutMiddleware(\App\Http\Middleware\CheckInstalled::class);

        Route::get('/_test/throttle/lecture', fn () => 'ok')
            ->middleware('throttle:3,1')->name('test.throttle.lecture');
        Route::get('/_test/throttle/ecriture', fn () => 'ok')
            ->middleware('throttle:2,1')->name('test.throttle.ecriture');
    }

    private function assertThrottled(string $uri): void
    {
        // La page 429 de l'application lit ses réglages en base ; on vérifie
        // la limitation elle-même, pas son habillage.
        $this->withoutExceptionHandling();
        try {
            $this->get($uri);
            $this->fail("{$uri} aurait dû être limitée.");
        } catch (ThrottleRequestsException $e) {
            $this->assertSame(429, $e->getStatusCode());
        } finally {
            $this->withExceptionHandling();
        }
    }

    public function test_une_route_ne_consomme_pas_le_quota_d_une_autre(): void
    {
        $this->actingAs(new GenericUser(['id' => 424242]));

        for ($i = 0; $i < 3; $i++) {
            $this->get('/_test/throttle/lecture')->assertOk();
        }
        $this->assertThrottled('/_test/throttle/lecture');

        // Avant correction : 429 immédiat, le seau de la lecture était plein.
        $this->get('/_test/throttle/ecriture')->assertOk();
        $this->get('/_test/throttle/ecriture')->assertOk();
        $this->assertThrottled('/_test/throttle/ecriture');
    }

    public function test_deux_utilisateurs_gardent_des_quotas_distincts(): void
    {
        $this->actingAs(new GenericUser(['id' => 1001]));
        $this->get('/_test/throttle/ecriture')->assertOk();
        $this->get('/_test/throttle/ecriture')->assertOk();
        $this->assertThrottled('/_test/throttle/ecriture');

        $this->actingAs(new GenericUser(['id' => 1002]));
        $this->get('/_test/throttle/ecriture')->assertOk();
    }

    public function test_la_limitation_garde_sa_place_apres_l_authentification(): void
    {
        $priority = $this->app->make('router')->middlewarePriority;

        $parent = array_search(ThrottleRequests::class, $priority, true);
        $sousClasse = array_search(ThrottleRequestsParRoute::class, $priority, true);

        $this->assertNotFalse($sousClasse, 'La sous-classe doit figurer dans la priorité.');
        $this->assertSame($parent + 1, $sousClasse);
        $this->assertGreaterThan(
            array_search(\Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class, $priority, true),
            $sousClasse
        );
    }
}
