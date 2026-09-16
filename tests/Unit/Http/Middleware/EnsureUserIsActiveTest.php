<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class EnsureUserIsActiveTest extends TestCase
{
    public function test_un_visiteur_passe(): void
    {
        $middleware = new EnsureUserIsActive();
        $request = Request::create('/esbtp/settings', 'GET');
        $reponse = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $reponse->getContent());
    }

    public function test_un_compte_inactif_est_deconnecte(): void
    {
        $user = new User(['is_active' => false, 'name' => 'Awa']);
        $this->actingAs($user);

        $middleware = new EnsureUserIsActive();
        $request = Request::create('/esbtp/settings', 'GET');
        $request->setLaravelSession($this->app['session']->driver());
        $reponse = $middleware->handle($request, fn () => response('ok'));

        $this->assertTrue($reponse->isRedirection());
        $this->assertGuest();
    }
}
