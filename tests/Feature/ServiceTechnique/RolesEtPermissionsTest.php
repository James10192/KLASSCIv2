<?php

namespace Tests\Feature\ServiceTechnique;

use App\Models\User;
use App\Services\ExtensionsDeRole;
use App\Services\PermissionRegistry;
use App\Services\PermissionSyncService;
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

    public function test_ancienne_page_roles_redirige_vers_la_page_unifiee(): void
    {
        $this->actingAs($this->st)
            ->get(route('esbtp.roles-permissions.index', ['role' => 'comptable']))
            ->assertRedirect(route('esbtp.personnel.unified.index', ['section' => 'roles']));
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
        $this->assertContains($this->horsDefaut, $extensions->pour('comptable'));

        $this->actingAs($this->st)
            ->postJson(route('esbtp.roles-permissions.restore-defaults'), ['role' => 'comptable'])
            ->assertOk()
            ->assertJson(['success' => true]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertFalse(Role::findByName('comptable')->hasPermissionTo($this->horsDefaut));
        $this->assertNotContains($this->horsDefaut, $extensions->pour('comptable'));
    }

    public function test_enregistrer_les_noms_affiches_garde_les_anciens_noms(): void
    {
        $registry = app(PermissionRegistry::class);
        $canonique = collect($registry->defaultPermissionsFor('comptable'))->first(fn ($p) => $registry->aliasesOf($p) !== []);
        $this->assertNotNull($canonique, 'Le comptable doit avoir une permission par défaut avec un ancien nom.');
        $alias = $registry->aliasesOf($canonique)[0];
        Permission::findOrCreate($canonique, 'web');
        Permission::findOrCreate($alias, 'web');
        Role::findByName('comptable')->syncPermissions([$canonique, $alias]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Anciens noms masqués : l'écran n'envoie que le nom canonique.
        $this->actingAs($this->st)
            ->postJson(route('esbtp.roles-permissions.update'), ['role' => 'comptable', 'permissions' => [$canonique]])
            ->assertOk()
            ->assertJsonFragment(['permissions' => collect([$canonique, $alias])->sort()->values()->all()]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertTrue(Role::findByName('comptable')->hasPermissionTo($alias));
    }

    public function test_un_droit_par_defaut_explicitement_revoque_ne_revient_pas_au_deploiement(): void
    {
        // paiements.correct_mode figure dans le rattrapage newFeaturePermissions.
        // Auparavant il était systématiquement réaccordé au prochain sync.
        $role = Role::findOrCreate('caissier', 'web');
        Permission::findOrCreate('paiements.correct_mode', 'web');
        Permission::findOrCreate('paiements.view_own', 'web');
        $role->syncPermissions(['paiements.correct_mode', 'paiements.view_own']);

        $this->actingAs($this->st)
            ->postJson(route('esbtp.roles-permissions.update'), [
                'role' => 'caissier',
                'permissions' => ['paiements.view_own'],
            ])
            ->assertOk();

        $this->assertContains(
            'paiements.correct_mode',
            app(ExtensionsDeRole::class)->revoqueesPour('caissier')
        );

        app(PermissionSyncService::class)->run();

        $this->assertFalse(Role::findByName('caissier', 'web')
            ->hasPermissionTo('paiements.correct_mode'));

        // Restaurer les paramètres usine supprime la décision de retrait.
        $this->actingAs($this->st)
            ->postJson(route('esbtp.roles-permissions.restore-defaults'), [
                'role' => 'caissier',
            ])->assertOk();

        $this->assertNotContains(
            'paiements.correct_mode',
            app(ExtensionsDeRole::class)->revoqueesPour('caissier')
        );

        app(PermissionSyncService::class)->run();
        $this->assertTrue(Role::findByName('caissier', 'web')
            ->hasPermissionTo('paiements.correct_mode'));
    }

    public function test_roles_de_secours_immuables_sur_les_endpoints_techniques(): void
    {
        foreach (['superAdmin', 'serviceTechnique'] as $roleName) {
            Role::findOrCreate($roleName, 'web');

            $this->actingAs($this->st)
                ->postJson(route('esbtp.roles-permissions.update'), [
                    'role' => $roleName,
                    'permissions' => [],
                ])->assertForbidden();

            $this->actingAs($this->st)
                ->postJson(route('esbtp.roles-permissions.restore-defaults'), [
                    'role' => $roleName,
                ])->assertForbidden();
        }
    }

    public function test_un_compte_sans_service_technique_est_refuse(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('esbtp.roles-permissions.update'), ['role' => 'comptable', 'permissions' => []])
            ->assertForbidden();
    }
}
