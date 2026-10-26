<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use App\Policies\UserManagementPolicy;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class UserManagementPermissionOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            'personnel.manage', 'personnel.view', 'personnel.roles.assign',
            'users.manage', 'notes.view', 'system.manage',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('enseignant', 'web')->givePermissionTo('notes.view');
        Role::findOrCreate('superAdmin', 'web');
    }

    public function test_compte_sans_role_peut_gerer_un_compte_dont_il_couvre_les_permissions(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(['personnel.manage', 'notes.view']);
        $target = User::factory()->create();
        $target->assignRole('enseignant');

        $this->assertTrue(app(UserManagementService::class)->canManage($actor, $target));
    }

    public function test_nom_superadmin_ne_donne_pas_acces_sans_permission(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('superAdmin');
        $target = User::factory()->create();
        $target->assignRole('enseignant');

        $this->assertFalse(app(UserManagementService::class)->canManage($actor, $target));
    }

    public function test_meme_avec_le_droit_un_compte_superadmin_reste_protege(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(['personnel.manage', 'notes.view', 'system.manage']);
        $target = User::factory()->create();
        $target->assignRole('superAdmin');

        $this->assertFalse(app(UserManagementService::class)->canManage($actor, $target));
    }

    public function test_permission_consulter_ne_donne_pas_le_droit_modifier_ou_supprimer(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo('personnel.view');
        $target = User::factory()->create();
        $policy = app(UserManagementPolicy::class);

        $this->assertTrue($policy->view($actor, $target));
        $this->assertFalse($policy->update($actor, $target));
        $this->assertFalse($policy->delete($actor, $target));
    }

    public function test_attribuer_role_exige_la_permission_et_les_droits_contenus_dans_le_role(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(['personnel.roles.assign', 'notes.view']);
        $svc = app(UserManagementService::class);

        $this->assertTrue($svc->canAssignRole($actor, 'enseignant'));
        $actor->revokePermissionTo('notes.view');
        $this->assertFalse($svc->canAssignRole($actor, 'enseignant'));
    }
}
