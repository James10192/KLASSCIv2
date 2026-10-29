<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Une réponse retenue pendant une page de lecture ne survit pas à cette page.
 */
class MemoireDesDroitsApresRequeteTest extends TestCase
{
    use DatabaseTransactions;

    public function test_un_droit_donne_apres_la_page_est_vu(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Permission::findOrCreate('sonde.perm', 'web');
        Route::get('/sonde-memoire-droits', fn () => auth()->user()->can('sonde.perm') ? 'oui' : 'non');

        $personne = User::factory()->create();

        $this->actingAs($personne)->get('/sonde-memoire-droits')->assertSeeText('non');

        $personne->givePermissionTo('sonde.perm');

        $this->assertTrue($personne->fresh()->can('sonde.perm'));
        $this->actingAs($personne->fresh())->get('/sonde-memoire-droits')->assertSeeText('oui');
    }
}
