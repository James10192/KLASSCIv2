<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use App\Services\ExtensionsDeRole;
use App\Services\PermissionSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Lot 1 : les trois points d'entrée d'affectation doivent partager les mêmes
 * gardes. Les rôles standards personnalisés doivent survivre au déploiement.
 */
class CustomRoleManagementHardeningTest extends TestCase
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

        config()->set('permissions.superadmin_gate_before', true);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function acteur(string $role, bool $gererPersonnel = true): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        if ($gererPersonnel) {
            Permission::findOrCreate('personnel.manage', 'web');
            $user->givePermissionTo('personnel.manage');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function rolePersonnalise(string $name): Role
    {
        $role = Role::findOrCreate($name, 'web');
        $role->is_custom = true;
        $role->save();

        return $role;
    }

    public function test_sans_perimetre_un_envoi_vide_ne_peut_pas_detacher_les_autres(): void
    {
        $actor = $this->acteur('agentInscription');
        $role = $this->rolePersonnalise('roleTestAcces');
        $target = $this->acteur('enseignant', false);
        $target->assignRole($role);

        $this->actingAs($actor)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), ['user_ids' => []])
            ->assertForbidden();

        $this->assertTrue($target->fresh()->hasRole($role->name));
    }

    public function test_un_second_role_protege_interdit_l_affectation_et_le_detachement(): void
    {
        $actor = $this->acteur('coordinateur');
        $role = $this->rolePersonnalise('roleTestInspection');
        $target = $this->acteur('enseignant', false);
        Role::findOrCreate('superAdmin', 'web');
        $target->assignRole(['superAdmin', $role->name]);

        $this->actingAs($actor)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [$target->id],
            ])
            ->assertForbidden();

        $this->actingAs($actor)
            ->deleteJson(route('esbtp.custom-roles.detach-user', [$role->name, $target->id]))
            ->assertForbidden();

        $this->assertTrue($target->fresh()->hasRole($role->name));
    }

    public function test_attribution_d_un_role_plus_privilegie_que_l_acteur_est_refusee(): void
    {
        $actor = $this->acteur('coordinateur');
        $role = $this->rolePersonnalise('roleTestPrivilege');
        Permission::findOrCreate('security.audit.view', 'web');
        $role->givePermissionTo('security.audit.view');

        $target = $this->acteur('enseignant', false);

        $this->actingAs($actor)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [$target->id],
            ])
            ->assertForbidden();

        $this->assertFalse($target->fresh()->hasRole($role->name));
    }

    public function test_le_superadmin_peut_affecter_un_role_personnalise_autorise(): void
    {
        $actor = $this->acteur('superAdmin', false);
        $role = $this->rolePersonnalise('roleTestSurveillant');
        $target = $this->acteur('enseignant', false);

        $this->actingAs($actor)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [$target->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue($target->fresh()->hasRole($role->name));
    }

    public function test_superadmin_peut_attribuer_un_role_a_un_compte_actif_sans_role(): void
    {
        $actor = $this->acteur('superAdmin', false);
        $role = $this->rolePersonnalise('roleTestInitialisation');
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($actor)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [$target->id],
            ])
            ->assertOk();

        $this->assertTrue($target->fresh()->hasRole($role->name));
    }

    public function test_compte_inactif_deja_attribue_reste_visible_et_pas_detache_automatiquement(): void
    {
        $actor = $this->acteur('superAdmin', false);
        $role = $this->rolePersonnalise('roleTestInactif');
        $target = $this->acteur('enseignant', false);
        $target->assignRole($role);
        $target->is_active = false;
        $target->save();

        $this->actingAs($actor)
            ->get(route('esbtp.custom-roles.assign-users.form', $role->name))
            ->assertOk()
            ->assertSee($target->email)
            ->assertSee('Compte désactivé');

        $this->actingAs($actor)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [$target->id],
            ])
            ->assertOk();

        $this->assertTrue($target->fresh()->hasRole($role->name));

        $targetSansRole = User::factory()->create(['is_active' => false]);
        $this->actingAs($actor)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [$target->id, $targetSansRole->id],
            ])
            ->assertForbidden();

        $this->assertFalse($targetSansRole->fresh()->hasRole($role->name));
    }

    public function test_un_gestionnaire_ne_peut_pas_modifier_un_role_standard_hors_de_sa_matrice(): void
    {
        $actor = $this->acteur('coordinateur');
        Role::findOrCreate('comptable', 'web');

        $this->actingAs($actor)
            ->putJson(route('esbtp.custom-roles.standard.update', 'comptable'), [
                'label_fr' => 'Comptabilité',
                'permissions' => [],
            ])
            ->assertForbidden();

        $this->actingAs($actor)
            ->get(route('esbtp.custom-roles.standard.edit', 'comptable'))
            ->assertForbidden();
    }

    public function test_un_gestionnaire_ne_peut_pas_retirer_un_droit_de_role_standard_qu_il_ne_possede_pas(): void
    {
        $actor = $this->acteur('coordinateur');
        $role = Role::findOrCreate('enseignant', 'web');
        $permission = Permission::findOrCreate('security.audit.view', 'web');
        $role->givePermissionTo($permission);

        $this->actingAs($actor)
            ->putJson(route('esbtp.custom-roles.standard.update', 'enseignant'), [
                'label_fr' => 'Enseignant',
                'permissions' => [],
            ])
            ->assertForbidden();

        $this->assertTrue($role->fresh()->hasPermissionTo($permission));
    }

    public function test_un_gestionnaire_ne_peut_pas_editer_vider_ou_supprimer_un_role_custom_plus_privilegie(): void
    {
        $acteur = $this->acteur('coordinateur');
        $role = $this->rolePersonnalise('roleTestCustomHorsPerimetre');
        $permission = Permission::findOrCreate('security.audit.view', 'web');
        $role->givePermissionTo($permission);

        $this->actingAs($acteur)
            ->get(route('esbtp.custom-roles.edit', $role->name))
            ->assertForbidden();

        $this->actingAs($acteur)
            ->putJson(route('esbtp.custom-roles.update', $role->name), [
                'label_fr' => 'Effacement interdit',
                'permissions' => [],
            ])->assertForbidden();

        $this->actingAs($acteur)
            ->get(route('esbtp.custom-roles.assign-users.form', $role->name))
            ->assertForbidden();

        $this->actingAs($acteur)
            ->deleteJson(route('esbtp.custom-roles.destroy', $role->name))
            ->assertForbidden();

        $this->assertTrue($role->fresh()->hasPermissionTo($permission));
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => $role->name]);
    }

    public function test_ancien_controleur_roles_ne_peut_ni_creer_ni_renommer_ni_supprimer_un_role_standard(): void
    {
        $actor = $this->acteur('superAdmin', false);
        $role = Role::findOrCreate('directeurEtudes', 'web');
        $this->actingAs($actor);

        $this->get(route('esbtp.roles.edit', $role->id))
            ->assertRedirect(route('esbtp.personnel.unified.index'));

        $this->post(route('esbtp.roles.store'), [
            'name' => 'nouveauRole',
            'permissions' => [],
        ])->assertForbidden();

        $this->put(route('esbtp.roles.update', $role->id), [
            'name' => 'ancienNomDangereux',
            'permissions' => [],
        ])->assertForbidden();

        $this->delete(route('esbtp.roles.destroy', $role->id))
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'directeurEtudes']);
        $this->assertDatabaseMissing('roles', ['name' => 'nouveauRole']);
    }

    public function test_audit_des_roles_persiste_les_differences_de_permissions_et_les_assignations(): void
    {
        $actor = $this->acteur('superAdmin', false);
        $role = $this->rolePersonnalise('roleTestAuditPivot');
        Permission::findOrCreate('inscriptions.validate', 'web');
        $cible = User::factory()->create(['is_active' => true]);

        $this->actingAs($actor)
            ->putJson(route('esbtp.custom-roles.update', $role->name), [
                'label_fr' => 'Rôle audité',
                'permissions' => ['inscriptions.validate'],
            ])->assertOk();

        $this->actingAs($actor)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [$cible->id],
            ])->assertOk();

        $audits = \OwenIt\Auditing\Models\Audit::query()
            ->where('auditable_type', Role::class)
            ->where('auditable_id', $role->id)
            ->where('tags', 'permissions')
            ->where('user_id', $actor->id)
            ->get();

        $valeurs = static fn ($audit) => is_array($audit->new_values)
            ? json_encode($audit->new_values)
            : (string) $audit->new_values;

        $this->assertTrue($audits->contains(fn ($audit) => str_contains(
            $valeurs($audit), 'inscriptions.validate'
        )));
        $this->assertTrue($audits->contains(fn ($audit) => str_contains(
            $valeurs($audit), 'user_ids'
        )));
    }

    public function test_extension_d_un_role_standard_saisie_via_personnel_survit_a_la_sync(): void
    {
        $actor = $this->acteur('superAdmin', false);
        $role = Role::findOrCreate('directeurEtudes', 'web');
        Permission::findOrCreate('inscriptions.validate', 'web');

        $this->actingAs($actor)
            ->putJson(route('esbtp.custom-roles.standard.update', $role->name), [
                'label_fr' => 'Direction des études',
                'icon' => 'fa-user-tie',
                'description' => 'Pilotage pédagogique',
                'permissions' => ['inscriptions.validate'],
            ])
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('role.name', $role->name);

        $this->assertContains(
            'inscriptions.validate',
            app(ExtensionsDeRole::class)->pour($role->name)
        );

        app(PermissionSyncService::class)->run();

        $this->assertTrue(
            Role::findByName($role->name, 'web')->hasPermissionTo('inscriptions.validate')
        );
    }
}
