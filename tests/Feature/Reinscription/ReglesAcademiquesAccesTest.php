<?php

namespace Tests\Feature\Reinscription;

use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReglesAcademiquesAccesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Les permissions du routeur restent actives : ne jamais utiliser
        // withoutMiddleware() sur tous les middlewares pour ce test.
        $this->withoutMiddleware([
            CheckInstalled::class,
            EnsureInstalled::class,
            PaywallMiddleware::class,
        ]);
    }

    public function test_un_personnel_sans_habilitation_ne_peut_ni_lire_ni_modifier_les_regles(): void
    {
        $this->actingAs(User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]));

        $this->get(route('esbtp.reinscription.regles.index'))->assertForbidden();
        $this->postJson(route('esbtp.reinscription.regles.store'), [])->assertForbidden();
    }

    public function test_un_personnel_avec_permission_effective_de_scolarite_peut_ouvrir_les_regles(): void
    {
        $agent = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $agent->givePermissionTo(Permission::findOrCreate('identity.registrar', 'web'));

        $this->actingAs($agent)
            ->get(route('esbtp.reinscription.regles.index'))
            ->assertOk()
            ->assertSee('Règles académiques');
    }
}
