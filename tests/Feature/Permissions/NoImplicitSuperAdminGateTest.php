<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NoImplicitSuperAdminGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['users.manage', 'system.manage', 'dashboard.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
    }

    public function test_un_nom_superadmin_ne_contourne_pas_une_permission_refusee(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('superAdmin');
        // Aucun droit system.manage assigné au rôle ; c'est la révocation
        // explicite simulée sur une instance scolaire.
        $this->assertFalse(Gate::forUser($actor)->allows('system.manage'));
        $this->assertFalse(Gate::forUser($actor)->allows('users.manage'));
    }

    public function test_permission_directe_autorise_independamment_de_tout_role(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo('users.manage');

        $this->assertTrue(Gate::forUser($actor)->allows('users.manage'));
        $this->assertFalse(Gate::forUser($actor)->allows('system.manage'));
    }

    public function test_retrait_de_permission_sur_role_superadmin_redevient_un_refus(): void
    {
        $role = Role::findByName('superAdmin', 'web');
        $role->givePermissionTo('users.manage');
        $actor = User::factory()->create();
        $actor->assignRole($role);

        $this->assertTrue(Gate::forUser($actor)->allows('users.manage'));
        $role->revokePermissionTo('users.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $actor->unsetRelation('roles');
        $actor->unsetRelation('permissions');

        $this->assertFalse(Gate::forUser($actor)->allows('users.manage'));
    }
}
