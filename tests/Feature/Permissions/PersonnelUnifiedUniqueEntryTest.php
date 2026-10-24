<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La seule page publique de gestion du personnel est /esbtp/personnel/unified.
 * Les anciennes routes métier ne doivent pas ressusciter un écran parallèle.
 */
class PersonnelUnifiedUniqueEntryTest extends TestCase
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
    }

    private function administrateur(): User
    {
        Role::findOrCreate('superAdmin', 'web');
        foreach (['personnel.manage', 'personnel.view', 'users.manage', 'admin.access'] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('superAdmin');
        $user->givePermissionTo(['personnel.manage', 'personnel.view', 'users.manage', 'admin.access']);

        return $user;
    }

    public function test_anciennes_pages_personnel_et_roles_redirigent_vers_unified(): void
    {
        $this->actingAs($this->administrateur());

        $this->get(route('esbtp.personnel.index'))
            ->assertRedirect(route('esbtp.personnel.unified.index'));

        $this->get(route('esbtp.roles.index'))
            ->assertRedirect(route('esbtp.personnel.unified.index'));

        $this->get(route('esbtp.custom-roles.index'))
            ->assertRedirect(route('esbtp.personnel.unified.index'));
    }

    public function test_page_technique_roles_redirige_vers_la_section_roles_unifiee(): void
    {
        Role::findOrCreate('serviceTechnique', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('serviceTechnique');
        $this->actingAs($user);

        $this->get(route('esbtp.roles-permissions.index'))
            ->assertRedirect(route('esbtp.personnel.unified.index', ['section' => 'roles']));
    }

    public function test_anciens_endpoints_personnel_ne_proposent_plus_de_mutations(): void
    {
        foreach ([
            'esbtp.personnel.store',
            'esbtp.personnel.update',
            'esbtp.personnel.destroy',
            'esbtp.personnel.toggle-status',
            'esbtp.personnel.bulk-action',
        ] as $route) {
            $this->assertFalse(\Illuminate\Support\Facades\Route::has($route), $route);
        }

        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('esbtp.personnel.unified.store')
        );
    }

    public function test_api_de_liste_custom_roles_reste_json_sur_la_page_unifiee(): void
    {
        $this->actingAs($this->administrateur());
        $this->getJson(route('esbtp.custom-roles.index'))
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
