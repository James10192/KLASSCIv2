<?php

namespace Tests\Feature\Personnel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La section « Rôles standards » de /esbtp/personnel/unified ne doit jamais
 * disparaître en silence.
 *
 * Elle est la SEULE porte vers les permissions des rôles système. Tant qu'elle
 * était rendue sous condition que la liste soit non vide, l'unique effet de ce
 * garde était de la faire disparaître au moment précis où quelque chose
 * clochait sur l'instance — une école entière pouvait croire que KLASSCI ne
 * propose pas la fonctionnalité. C'est ce qui s'est produit sur USAT.
 */
class RolesStandardsVisiblesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        Cache::flush();

        // superAdmin : Gate::before lui accorde tout, donc l'accès à la page ne
        // depend pas de l'etat du registre de permissions — ce que ces tests ne
        // cherchent justement pas a mesurer.
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::findOrCreate('superAdmin', 'web'));

        $this->actingAs($this->admin);
    }

    public function test_la_section_reste_visible_quand_aucun_role_standard_n_existe(): void
    {
        // Etat d'une instance incomplete : aucun des roles systeme en base.
        $reponse = $this->get(route('esbtp.personnel.unified.index'));

        $reponse->assertOk()
            ->assertSee('Rôles standards', false)
            // Et elle DIT pourquoi elle est vide, plutot que de laisser croire
            // que la fonctionnalite n'existe pas.
            ->assertSee('Aucun rôle standard trouvé', false);
    }

    public function test_les_roles_standards_presents_sont_listes(): void
    {
        foreach (['secretaire', 'comptable', 'caissier'] as $nom) {
            Role::findOrCreate($nom, 'web');
        }

        $reponse = $this->get(route('esbtp.personnel.unified.index'));

        $reponse->assertOk()
            ->assertSee('Rôles standards', false)
            ->assertSee('3 rôles', false)
            ->assertDontSee('Aucun rôle standard trouvé', false);
    }

    public function test_le_role_superadmin_n_est_pas_propose_a_l_edition(): void
    {
        Role::findOrCreate('secretaire', 'web');

        $reponse = $this->get(route('esbtp.personnel.unified.index'));

        // superAdmin et serviceTechnique restent hors de portee de cette page :
        // la whitelist EDITABLE_STANDARD_ROLES est ce qui les en tient a l'ecart.
        $reponse->assertOk()
            ->assertDontSee('data-cr-edit-standard="'.route('esbtp.custom-roles.standard.edit', 'superAdmin').'"', false);
    }
}
