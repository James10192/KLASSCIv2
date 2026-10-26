<?php

namespace Tests\Feature\API\CLI;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomRoleCliTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ce lot éprouve des droits individuels sur des comptes sans rôle.
        // CheckInstalled redirige l'ensemble des API vers /install lorsqu'aucun
        // compte superAdmin n'a été créé dans la DB de test. Cela produit des
        // 302 sans atteindre l'autorisation CLI testée ici. L'installation est
        // couverte séparément ; elle ne doit pas masquer les refus 403/422.
        $this->withoutMiddleware(\App\Http\Middleware\CheckInstalled::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('dashboard.view', 'web');
        Permission::findOrCreate('inscriptions.validate', 'web');
        foreach (['users.manage', 'personnel.roles.assign', 'personnel.roles.remove',
            'personnel.roles.replace', 'personnel.roles.create', 'superadmin.accounts.create'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        Role::findOrCreate('agentInscription', 'web');
    }

    private function grantCliPermissions(User $actor): void
    {
        foreach (['users.manage', 'personnel.roles.assign', 'personnel.roles.remove',
            'personnel.roles.replace', 'personnel.roles.create',
            'dashboard.view', 'inscriptions.validate'] as $name) {
            $actor->givePermissionTo($name);
        }
    }

    public function test_cli_role_mutations_refusent_un_operateur_desactive_meme_avec_ability(): void
    {
        Permission::findOrCreate('personnel.manage', 'web');
        $actor = User::factory()->create(['is_active' => false]);
        $this->grantCliPermissions($actor);
        $actor->givePermissionTo('personnel.manage');
        Sanctum::actingAs($actor, ['cli:admin']);

        $role = Role::findOrCreate('inactive_actor_cli_role', 'web');
        $role->givePermissionTo('dashboard.view');

        $this->postJson('/api/cli/roles', [
            'name' => 'inactive_actor_new_role',
            'label_fr' => 'Rôle refusé',
            'permissions' => ['dashboard.view'],
        ])->assertForbidden();

        $this->postJson('/api/cli/roles/inactive_actor_cli_role/grant', [
            'permissions' => ['inscriptions.validate'],
        ])->assertForbidden();

        $this->postJson('/api/cli/roles/inactive_actor_cli_role/revoke', [
            'permissions' => ['dashboard.view'],
        ])->assertForbidden();

        $this->assertDatabaseMissing('roles', ['name' => 'inactive_actor_new_role', 'guard_name' => 'web']);
        $this->assertTrue($role->fresh()->hasPermissionTo('dashboard.view'));
        $this->assertFalse($role->fresh()->hasPermissionTo('inscriptions.validate'));
    }

    public function test_cli_can_create_custom_role_and_assign_it(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('superAdmin');
        $this->grantCliPermissions($actor);
        Sanctum::actingAs($actor, ['cli:admin']);

        $this->postJson('/api/cli/roles', [
            'name' => 'informaticien',
            'label_fr' => 'Informaticien',
            'permissions' => ['dashboard.view', 'inscriptions.validate', 'missing.perm'],
        ])->assertOk()
            ->assertJsonPath('data.role', 'informaticien')
            ->assertJsonPath('data.granted', ['dashboard.view', 'inscriptions.validate'])
            ->assertJsonPath('data.missing_in_db', ['missing.perm']);

        $target = User::factory()->create();
        $target->assignRole('agentInscription');

        $this->postJson("/api/cli/user/{$target->id}/role", [
            'role' => 'informaticien',
            'mode' => 'replace',
            'old_role' => 'agentInscription',
            'expected_roles' => ['agentInscription'],
            'motif' => 'Remplacement ciblé validé',
        ])->assertOk()
            ->assertJsonPath('data.roles_after.0', 'informaticien');

        $this->assertTrue($target->fresh()->hasRole('informaticien'));
        $this->assertFalse($target->fresh()->hasRole('agentInscription'));

        $auditsCompte = \OwenIt\Auditing\Models\Audit::query()
            ->where('auditable_type', User::class)->where('auditable_id', $target->id)
            ->where('user_id', $actor->id)->where('tags', 'permissions')->get();
        $this->assertTrue($auditsCompte->contains(fn ($audit) => str_contains(
            json_encode($audit->new_values), 'informaticien'
        )));
        $role = Role::findByName('informaticien', 'web');
        $auditsRole = \OwenIt\Auditing\Models\Audit::query()
            ->where('auditable_type', Role::class)->where('auditable_id', $role->id)
            ->where('user_id', $actor->id)->where('tags', 'permissions')->get();
        $this->assertTrue($auditsRole->contains(fn ($audit) => str_contains(
            json_encode($audit->new_values), 'inscriptions.validate'
        )));
    }

    public function test_cli_edition_second_role_superadmin_et_service_technique_est_interdite(): void
    {
        $actor = User::factory()->create();
        $this->grantCliPermissions($actor);
        Permission::findOrCreate('personnel.manage', 'web');
        $actor->givePermissionTo(['users.manage', 'personnel.manage']);
        Permission::findOrCreate('security.audit.view', 'web');
        $actor->givePermissionTo('security.audit.view');
        Sanctum::actingAs($actor, ['cli:admin']);

        $role = Role::findOrCreate('role_test_shared_sensitive_cli', 'web');
        foreach (['superAdmin', 'serviceTechnique'] as $sensible) {
            Role::findOrCreate($sensible, 'web');
            $target = User::factory()->create();
            $target->assignRole([$sensible, $role->name]);

            $this->postJson("/api/cli/roles/{$role->name}/grant", [
                'permissions' => ['security.audit.view'],
            ])->assertForbidden();
            $this->postJson("/api/cli/roles/{$role->name}/revoke", [
                'permissions' => ['security.audit.view'],
            ])->assertForbidden();

            $this->assertFalse($role->fresh()->hasPermissionTo('security.audit.view'));
        }
    }

    public function test_cli_rejects_unknown_role_assignment(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('superAdmin');
        $this->grantCliPermissions($actor);
        Sanctum::actingAs($actor, ['cli:admin']);

        $target = User::factory()->create();

        $this->postJson("/api/cli/user/{$target->id}/role", [
            'role' => 'does_not_exist',
            'motif' => 'Vérification de rôle inconnu',
        ])->assertStatus(422);
    }

    public function test_cli_ne_permet_pas_de_remplacer_le_dernier_superadmin_par_service_technique(): void
    {
        Role::findOrCreate('serviceTechnique', 'web');
        $actor = User::factory()->create(['is_active' => true]);
        $actor->assignRole('superAdmin');
        $this->grantCliPermissions($actor);
        Sanctum::actingAs($actor, ['cli:admin']);

        $this->postJson("/api/cli/user/{$actor->id}/role", [
            'role' => 'serviceTechnique',
            'mode' => 'replace',
            'old_role' => 'superAdmin',
            'expected_roles' => ['superAdmin'],
            'motif' => 'Tentative refusée',
        ])->assertForbidden();

        $this->assertTrue($actor->fresh()->hasRole('superAdmin'));
        $this->assertFalse($actor->fresh()->hasRole('serviceTechnique'));
    }

    public function test_cli_rejects_reserved_canonical_role_name(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('superAdmin');
        $this->grantCliPermissions($actor);
        Sanctum::actingAs($actor, ['cli:admin']);

        $this->postJson('/api/cli/roles', [
            'name' => 'coordinateur',
            'label_fr' => 'Fake',
        ])->assertStatus(422);
    }
    public function test_cli_remplacement_ne_supprime_pas_un_autre_profil(): void
    {
        $actor = User::factory()->create();
        $this->grantCliPermissions($actor);
        Sanctum::actingAs($actor, ['cli:admin']);
        foreach (['enseignant', 'coordinateur', 'secretaire'] as $name) {
            Role::findOrCreate($name, 'web');
        }
        $target = User::factory()->create();
        $target->assignRole(['enseignant', 'coordinateur']);

        $this->postJson("/api/cli/user/{$target->id}/role", [
            'role' => 'secretaire', 'mode' => 'replace', 'old_role' => 'coordinateur',
            'expected_roles' => ['coordinateur', 'enseignant'],
            'motif' => 'Remplacement ciblé contrôlé',
        ])->assertOk();
        $this->assertEqualsCanonicalizing(['enseignant', 'secretaire'], $target->fresh()->getRoleNames()->all());

        $this->postJson("/api/cli/user/{$target->id}/role", [
            'role' => 'coordinateur', 'motif' => 'Deuxième fonction',
        ])->assertOk();
        $this->postJson("/api/cli/user/{$target->id}/role", [
            'role' => 'secretaire', 'mode' => 'remove', 'motif' => 'Fin de fonction',
        ])->assertOk();
        $this->assertEqualsCanonicalizing(['coordinateur', 'enseignant'], $target->fresh()->getRoleNames()->all());
    }

    public function test_cli_remplacement_exige_le_ciblage_et_un_etat_a_jour(): void
    {
        $actor = User::factory()->create();
        $this->grantCliPermissions($actor);
        Sanctum::actingAs($actor, ['cli:admin']);
        Role::findOrCreate('enseignant', 'web');
        Role::findOrCreate('coordinateur', 'web');
        $target = User::factory()->create();
        $target->assignRole('enseignant');

        $this->postJson("/api/cli/user/{$target->id}/role", [
            'role' => 'coordinateur', 'mode' => 'replace', 'motif' => 'Sans état',
        ])->assertStatus(422);
        $this->postJson("/api/cli/user/{$target->id}/role", [
            'role' => 'coordinateur', 'mode' => 'replace', 'old_role' => 'enseignant',
            'expected_roles' => ['enseignant', 'secretaire'], 'motif' => 'État périmé',
        ])->assertStatus(422);
        $this->assertEquals(['enseignant'], $target->fresh()->getRoleNames()->all());
    }

    public function test_cli_interdit_de_modifier_un_superadmin_existant(): void
    {
        $actor = User::factory()->create();
        $this->grantCliPermissions($actor);
        Sanctum::actingAs($actor, ['cli:admin']);
        Role::findOrCreate('enseignant', 'web');
        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole('superAdmin');

        $this->postJson("/api/cli/user/{$target->id}/role", [
            'role' => 'enseignant', 'mode' => 'add', 'motif' => 'Sans double approbation',
        ])->assertForbidden();
        $this->assertTrue($target->fresh()->hasRole('superAdmin'));
    }

    public function test_cli_token_seul_ne_confere_pas_de_permission_de_roles(): void
    {
        $actor = User::factory()->create();
        Sanctum::actingAs($actor, ['cli:admin']);
        Role::findOrCreate('enseignant', 'web');
        $target = User::factory()->create();

        $this->postJson("/api/cli/user/{$target->id}/role", [
            'role' => 'enseignant', 'mode' => 'add', 'motif' => 'Non habilité',
        ])->assertForbidden();
        $this->assertEmpty($target->fresh()->getRoleNames()->all());
    }

    public function test_cli_ne_permet_pas_de_promouvoir_superadmin_sans_permission_specifique(): void
    {
        $actor = User::factory()->create();
        $this->grantCliPermissions($actor);
        Sanctum::actingAs($actor, ['cli:admin']);
        $target = User::factory()->create();

        $this->postJson("/api/cli/user/{$target->id}/role", [
            'role' => 'superAdmin', 'mode' => 'add', 'motif' => 'Promotion non habilitée',
        ])->assertForbidden();
        $this->assertFalse($target->fresh()->hasRole('superAdmin'));
    }



    public function test_cli_refuse_de_retirer_un_role_plus_privilegie_que_l_acteur(): void
    {
        $actor = User::factory()->create();
        $this->grantCliPermissions($actor);
        Sanctum::actingAs($actor, ['cli:admin']);
        $special = Role::findOrCreate('role_test_sensitive', 'web');
        $permission = Permission::findOrCreate('security.audit.view', 'web');
        $special->givePermissionTo($permission);
        $target = User::factory()->create();
        $target->assignRole($special);

        $this->postJson("/api/cli/user/{$target->id}/role", [
            'role' => $special->name,
            'mode' => 'remove',
            'motif' => 'Tentative de retrait sans la permission détenue',
        ])->assertForbidden();

        $this->assertTrue($target->fresh()->hasRole($special->name));
    }



    public function test_synchro_permissions_refusee_si_token_admin_sans_droit_de_maintenance(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($actor, ['cli:admin']);

        $beforeRoles = Role::query()->count();
        $beforePermissions = Permission::query()->count();

        $this->postJson('/api/cli/permissions/sync')->assertForbidden();
        $this->postJson('/api/cli/permissions/fix')->assertForbidden();

        $this->assertSame($beforeRoles, Role::query()->count());
        $this->assertSame($beforePermissions, Permission::query()->count());
    }

    public function test_droit_technique_effectif_autorise_la_synchro_et_sa_revocation_la_bloque(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        Permission::findOrCreate('system.technical_access', 'web');
        $actor->givePermissionTo('system.technical_access');
        Sanctum::actingAs($actor, ['cli:admin']);

        $this->postJson('/api/cli/permissions/sync')
            ->assertOk()
            ->assertJsonPath('success', true);

        $actor->revokePermissionTo('system.technical_access');
        $this->postJson('/api/cli/permissions/sync')->assertForbidden();
    }

    public function test_erreur_synchro_ne_divulgue_jamais_message_sql_ou_stack(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        Permission::findOrCreate('system.technical_access', 'web');
        $actor->givePermissionTo('system.technical_access');
        Sanctum::actingAs($actor, ['cli:admin']);

        $this->mock(\App\Services\PermissionSyncService::class)
            ->shouldReceive('run')
            ->once()
            ->andThrow(new \RuntimeException('SQLSTATE credentials=DB_PRIVATE_SECRET'));

        $response = $this->postJson('/api/cli/permissions/sync')
            ->assertStatus(500);
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
        $this->assertStringNotContainsString('DB_PRIVATE_SECRET', $response->getContent());
    }
}
