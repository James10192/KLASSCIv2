<?php

namespace Tests\Feature\Messages;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Page /messages : elle s'ouvre, propose d'écrire tout de suite, et ne montre
 * plus de texte destiné aux développeurs.
 */
class MessagesPageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        Permission::findOrCreate('messages.send', 'web');
    }

    public function test_la_page_s_ouvre_et_propose_d_ecrire(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo('messages.send');

        $this->actingAs($user)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertSee('data-message-hub-v2', false)
            ->assertSee('Écrire à une personne')
            ->assertDontSee('Aucun bouton factice', false);
    }

    public function test_sans_droit_d_envoi_la_page_est_refusee(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('chat.index'))->assertForbidden();
    }
}
