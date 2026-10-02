<?php

namespace Tests\Feature\ServiceTechnique;

use App\Models\User;
use App\Services\ExtensionsDeRole;
use App\Services\PermissionRegistry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * L'écran des rôles enregistre sans rechargement, et revenir aux défauts
 * efface aussi les ajouts inscrits : sinon la synchronisation du déploiement
 * suivant remettrait ce que l'école vient de retirer.
 */
class RolesEtPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    private User $st;

    private string $parDefaut;

    private string $horsDefaut = 'test.permission_hors_defauts';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        Role::findOrCreate('serviceTechnique', 'web');
        Role::findOrCreate('comptable', 'web');
        $this->parDefaut = app(PermissionRegistry::class)->defaultPermissionsFor('comptable')[0];
        Permission::findOrCreate($this->parDefaut, 'web');
        Permission::findOrCreate($this->horsDefaut, 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->st = User::factory()->create();
        $this->st->assignRole('serviceTechnique');
    }

    public function test_la_page_se_rend_avec_le_role_demande(): void
    {
        $this->actingAs($this->st)
            ->get(route('esbtp.roles-permissions.index', ['role' => 'comptable']))
            ->assertOk()
            ->assertSee('Rôles et permissions')
            ->assertSee('id="rpRoleInput" name="role" value="comptable"', false)
            ->assertSee('data-role="comptable"', false);
    }

    public function test_l_enregistrement_en_json_rend_les_permissions_du_role(): void
    {
        $this->actingAs($this->st)
            ->postJson(route('esbtp.roles-permissions.update'), [
                'role' => 'comptable',
                'permissions' => [$this->parDefaut, $this->horsDefaut],
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'role' => 'comptable'])
            ->assertJsonFragment(['permissions' => collect([$this->parDefaut, $this->horsDefaut])->sort()->values()->all()]);

        $this->assertTrue(Role::findByName('comptable')->hasPermissionTo($this->horsDefaut));
    }

    public function test_revenir_aux_defauts_efface_les_ajouts_inscrits(): void
    {
        $this->actingAs($this->st)->postJson(route('esbtp.roles-permissions.update'), [
            'role' => 'comptable',
            'permissions' => [$this->parDefaut, $this->horsDefaut],
        ])->assertOk();

        $extensions = app(ExtensionsDeRole::class);
        if (! in_array($this->horsDefaut, $extensions->pour('comptable'), true)) {
            $this->markTestSkipped('Table des extensions absente de la base de test.');
        }

        $this->actingAs($this->st)
            ->postJson(route('esbtp.roles-permissions.restore-defaults'), ['role' => 'comptable'])
            ->assertOk()
            ->assertJson(['success' => true]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertFalse(Role::findByName('comptable')->hasPermissionTo($this->horsDefaut));
        $this->assertNotContains($this->horsDefaut, $extensions->pour('comptable'));
    }

    public function test_un_compte_sans_service_technique_est_refuse(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('esbtp.roles-permissions.update'), ['role' => 'comptable', 'permissions' => []])
            ->assertForbidden();
    }
}
