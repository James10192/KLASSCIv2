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

        // Les scénarios historiques d'administrateur doivent explicitement
        // détenir leurs capacités : le libellé du rôle n'autorise plus ces actions.
        if ($role === 'superAdmin') {
            foreach (['personnel.manage', 'personnel.roles.manage_all', 'personnel.users.initialize', 'personnel.roles.assign'] as $permission) {
                Permission::findOrCreate($permission, 'web');
                $user->givePermissionTo($permission);
            }
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

    public function test_compte_sans_role_avec_permissions_directes_peut_affecter_un_role_vide(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        foreach (['personnel.manage', 'personnel.roles.assign'] as $name) {
            $actor->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $role = $this->rolePersonnalise('roleTestDirectPrivilege');
        $cible = $this->acteur('enseignant', false);

        $this->actingAs($actor)->postJson(
            route('esbtp.custom-roles.assign-users', $role->name),
            ['user_ids' => [$cible->id]]
        )->assertOk();

        $this->assertTrue($cible->fresh()->hasRole('enseignant'));
        $this->assertTrue($cible->fresh()->hasRole($role->name));
    }

    public function test_role_de_coordinateur_sans_permission_assign_ne_peut_pas_modifier_les_membres(): void
    {
        $actor = $this->acteur('coordinateur');
        $role = $this->rolePersonnalise('roleTestSansAssign');
        $cible = $this->acteur('enseignant', false);

        $this->actingAs($actor)->postJson(
            route('esbtp.custom-roles.assign-users', $role->name),
            ['user_ids' => [$cible->id]]
        )->assertForbidden();

        $this->assertFalse($cible->fresh()->hasRole($role->name));
    }

    public function test_creer_un_role_custom_exige_permission_create_specifique(): void
    {
        $actor = $this->acteur('coordinateur');
        $this->actingAs($actor)
            ->get(route('esbtp.custom-roles.create'))
            ->assertForbidden();
        $this->actingAs($actor)
            ->postJson(route('esbtp.custom-roles.store'), [
                'name' => 'role_test_creation_interdite',
                'label_fr' => 'Rôle sans droit de création',
                'permissions' => [],
            ])->assertForbidden();
        $this->assertDatabaseMissing('roles', ['name' => 'role_test_creation_interdite']);

        $actor->givePermissionTo(Permission::findOrCreate('personnel.roles.create', 'web'));
        $this->actingAs($actor->fresh())
            ->get(route('esbtp.custom-roles.create'))
            ->assertOk();
        $this->actingAs($actor->fresh())
            ->postJson(route('esbtp.custom-roles.store'), [
                'name' => 'role_test_creation_autorisee',
                'label_fr' => 'Rôle créé avec habilitation',
                'permissions' => [],
            ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('roles', ['name' => 'role_test_creation_autorisee', 'is_custom' => true]);
    }

    public function test_affectation_personnalisee_ne_permet_pas_de_s_attribuer_un_role(): void
    {
        $acteur = User::factory()->create(['is_active' => true]);
        foreach (['personnel.manage', 'personnel.roles.assign', 'personnel.users.initialize'] as $nom) {
            $acteur->givePermissionTo(Permission::findOrCreate($nom, 'web'));
        }
        $role = $this->rolePersonnalise('roleTestInterditAutoAttribution');

        $this->actingAs($acteur)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [$acteur->id],
            ])->assertForbidden();

        $this->assertFalse($acteur->fresh()->hasRole($role->name));
    }

    public function test_formulaire_html_ne_permet_pas_auto_attribution_avec_identifiant_string(): void
    {
        $acteur = User::factory()->create(['is_active' => true]);
        foreach (['personnel.manage', 'personnel.roles.assign', 'personnel.users.initialize'] as $permission) {
            $acteur->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $role = $this->rolePersonnalise('roleTestAutoAttributionFormulaire');

        $this->actingAs($acteur)
            ->post(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [(string) $acteur->id],
            ])->assertForbidden();
        $this->assertFalse($acteur->fresh()->hasRole($role->name));
    }

    public function test_retrait_personnalise_refuse_la_suppression_de_son_propre_role(): void
    {
        $acteur = $this->acteur('superAdmin', false);
        $acteur->givePermissionTo(Permission::findOrCreate('personnel.roles.remove', 'web'));
        $role = $this->rolePersonnalise('roleTestInterditAutoRetrait');
        $acteur->assignRole($role);

        $this->actingAs($acteur)
            ->deleteJson(route('esbtp.custom-roles.detach-user', [$role->name, $acteur->id]))
            ->assertForbidden();

        $this->actingAs($acteur)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [],
            ])->assertForbidden();

        $this->assertTrue($acteur->fresh()->hasRole($role->name));
    }

    public function test_remove_sans_assign_permet_uniquement_le_retrait_d_un_role_autorise(): void
    {
        $acteur = User::factory()->create(['is_active' => true]);
        foreach (['personnel.manage', 'personnel.roles.remove'] as $nom) {
            $acteur->givePermissionTo(Permission::findOrCreate($nom, 'web'));
        }
        $role = $this->rolePersonnalise('roleTestRemoveOnly');
        $cible = User::factory()->create(['is_active' => true]);
        $cible->assignRole($role);

        $this->actingAs($acteur)
            ->deleteJson(route('esbtp.custom-roles.detach-user', [$role->name, $cible->id]))
            ->assertOk()->assertJsonPath('success', true);
        $this->assertFalse($cible->fresh()->hasRole($role->name));

        $this->actingAs($acteur)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [$cible->id],
            ])->assertForbidden();
        $this->assertFalse($cible->fresh()->hasRole($role->name));
    }

    public function test_manage_all_sans_assign_ne_peut_pas_ajouter_un_role_custom(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        foreach (['personnel.manage', 'personnel.roles.manage_all', 'personnel.users.initialize'] as $name) {
            $actor->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $role = $this->rolePersonnalise('roleTestManageAllSansAssign');
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($actor)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [$target->id],
            ])->assertForbidden();

        $this->assertFalse($target->fresh()->hasRole($role->name));
    }

    public function test_assign_sans_remove_ne_peut_pas_detacher_un_role_custom(): void
    {
        $actor = $this->acteur('superAdmin', false);
        $role = $this->rolePersonnalise('roleTestSansRemove');
        $target = $this->acteur('enseignant', false);
        $target->assignRole($role);

        $this->actingAs($actor)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [],
            ])->assertForbidden();

        $this->actingAs($actor)
            ->deleteJson(route('esbtp.custom-roles.detach-user', [$role->name, $target->id]))
            ->assertForbidden();
        $this->assertTrue($target->fresh()->hasRole($role->name));
    }

    public function test_manage_all_et_assign_ne_permettent_pas_de_muter_un_compte_superadmin(): void
    {
        $actor = $this->acteur('superAdmin', false);
        $role = $this->rolePersonnalise('roleTestCompteProtege');
        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole('superAdmin');

        $this->actingAs($actor)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [$target->id],
            ])->assertForbidden();
        $this->assertFalse($target->fresh()->hasRole($role->name));
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

    public function test_permissions_directes_sans_role_privilegie_autorisent_initialisation_personnel(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        foreach (['personnel.manage', 'personnel.roles.manage_all', 'personnel.users.initialize', 'personnel.roles.assign'] as $name) {
            $actor->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        $role = $this->rolePersonnalise('roleTestPermissionDirecte');
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($actor)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [$target->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue($target->fresh()->hasRole($role->name));
    }

    public function test_role_prestigieux_sans_permissions_effectives_ne_permet_pas_affectation(): void
    {
        config()->set('permissions.superadmin_gate_before', false);
        $actor = User::factory()->create(['is_active' => true]);
        Role::findOrCreate('superAdmin', 'web');
        $actor->assignRole('superAdmin');
        $actor->givePermissionTo(Permission::findOrCreate('personnel.manage', 'web'));

        $role = $this->rolePersonnalise('roleTestSansPermission');
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($actor)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [$target->id],
            ])
            ->assertForbidden();

        $this->assertFalse($target->fresh()->hasRole($role->name));
    }

    public function test_permission_d_initialisation_absente_ne_provoque_pas_500(): void
    {
        $actor = $this->acteur('coordinateur');
        // Sur un tenant non synchronisé, le droit récemment créé est absent :
        // la vérification ne doit jamais déclencher PermissionDoesNotExist.
        Permission::where('name', 'personnel.users.initialize')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = $this->rolePersonnalise('roleTestPermAbsente');
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($actor)
            ->postJson(route('esbtp.custom-roles.assign-users', $role->name), [
                'user_ids' => [$target->id],
            ])
            ->assertForbidden();

        $this->assertFalse($target->fresh()->hasRole($role->name));
    }

    public function test_edition_des_permissions_custom_est_fermee_si_superadmin_detient_ce_role(): void
    {
        $acteur = $this->acteur('superAdmin', false);
        $role = $this->rolePersonnalise('roleTestMutationIndirecteSuperAdmin');
        $cible = User::factory()->create(['is_active' => true]);
        $cible->assignRole(['superAdmin', $role->name]);
        $acteur->givePermissionTo(Permission::findOrCreate('inscriptions.validate', 'web'));

        $this->actingAs($acteur)
            ->get(route('esbtp.custom-roles.edit', $role->name))
            ->assertForbidden();
        $this->actingAs($acteur)
            ->putJson(route('esbtp.custom-roles.update', $role->name), [
                'label_fr' => 'Rôle protégé',
                'permissions' => ['inscriptions.validate'],
            ])->assertForbidden();

        $this->assertFalse($role->fresh()->hasPermissionTo('inscriptions.validate'));
    }

    public function test_edition_du_role_standard_refuse_une_mutation_indirecte_du_service_technique(): void
    {
        $acteur = $this->acteur('superAdmin', false);
        $role = Role::findOrCreate('coordinateur', 'web');
        $avant = $role->permissions()->pluck('name')->sort()->values()->all();
        Role::findOrCreate('serviceTechnique', 'web');
        $cible = User::factory()->create(['is_active' => true]);
        $cible->assignRole(['serviceTechnique', 'coordinateur']);
        $acteur->givePermissionTo(Permission::findOrCreate('inscriptions.validate', 'web'));

        $this->actingAs($acteur)
            ->get(route('esbtp.custom-roles.standard.edit', $role->name))
            ->assertForbidden();
        $this->actingAs($acteur)
            ->putJson(route('esbtp.custom-roles.standard.update', $role->name), [
                'label_fr' => 'Coordinateur protégé',
                'permissions' => ['inscriptions.validate'],
            ])->assertForbidden();

        $this->assertSame($avant, $role->fresh()->permissions()->pluck('name')->sort()->values()->all());
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

    public function test_suppression_role_custom_avec_membre_refuse_sans_retrait_ni_audit_de_suppression(): void
    {
        $acteur = $this->acteur('superAdmin', false);
        $role = $this->rolePersonnalise('roleTestConserveMembres');
        $cible = User::factory()->create(['is_active' => true]);
        $cible->assignRole($role);

        $this->actingAs($acteur)
            ->deleteJson(route('esbtp.custom-roles.destroy', $role->name))
            ->assertStatus(422);

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => $role->name]);
        $this->assertTrue($cible->fresh()->hasRole($role->name));
    }

    public function test_suppression_role_custom_vide_genere_une_decision_auditee(): void
    {
        $acteur = $this->acteur('superAdmin', false);
        $role = $this->rolePersonnalise('roleTestSuppressionAuditee');
        $roleId = $role->id;

        $this->actingAs($acteur)
            ->deleteJson(route('esbtp.custom-roles.destroy', $role->name))
            ->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseMissing('roles', ['id' => $roleId]);
        $this->assertDatabaseHas('audits', [
            'auditable_type' => Role::class,
            'auditable_id' => $roleId,
            'user_id' => $acteur->id,
            'tags' => 'permissions',
        ]);
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

        // L'ancien écran technique exige system.technical_access ;
        // superAdmin sans cette permission ne peut pas emprunter cette URL.
        $this->get(route('esbtp.roles.edit', $role->id))
            ->assertForbidden();

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
        $actor->givePermissionTo('inscriptions.validate');
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

    public function test_la_sync_ne_distribue_pas_creation_superadmin_au_role_superadmin(): void
    {
        $super = Role::findOrCreate('superAdmin', 'web');
        $service = Role::findOrCreate('serviceTechnique', 'web');
        $permission = Permission::findOrCreate('superadmin.accounts.create', 'web');
        // Etat hérité : le joker du rôle superAdmin a déjà attribué ce droit.
        $super->givePermissionTo($permission);

        app(PermissionSyncService::class)->run();

        $this->assertFalse($super->fresh()->hasPermissionTo($permission));
        $this->assertTrue($service->fresh()->hasPermissionTo($permission));
    }

    public function test_sync_retire_les_droits_de_maintenance_herites_du_joker_superadmin(): void
    {
        $super = Role::findOrCreate('superAdmin', 'web');
        $service = Role::findOrCreate('serviceTechnique', 'web');
        $permissions = ['paywall.manage', 'system.technical_access'];
        foreach ($permissions as $name) {
            $super->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        app(PermissionSyncService::class)->run();

        foreach ($permissions as $name) {
            $this->assertFalse($super->fresh()->hasPermissionTo($name), $name);
            $this->assertTrue($service->fresh()->hasPermissionTo($name), $name);
        }
    }

    public function test_extension_d_un_role_standard_saisie_via_personnel_survit_a_la_sync(): void
    {
        $actor = $this->acteur('superAdmin', false);
        $role = Role::findOrCreate('directeurEtudes', 'web');
        Permission::findOrCreate('inscriptions.validate', 'web');
        $actor->givePermissionTo('inscriptions.validate');

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
