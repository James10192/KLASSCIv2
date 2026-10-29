<?php

namespace Tests\Feature\API\CLI;

use App\Exceptions\LastActiveSuperAdminException;
use App\Http\Controllers\ESBTPPersonnelController;
use App\Http\Controllers\ESBTPSecretaireController;
use App\Http\Controllers\ESBTPEnseignantController;
use App\Http\Controllers\ESBTPCaissierController;
use App\Models\ESBTPTeacher;
use App\Models\User;
use App\Services\UserLifecycle\SuperAdminLifecycleGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserDeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('users.manage', 'web');
        Role::findOrCreate('superAdmin', 'web')->givePermissionTo('admin.access');
        Role::findOrCreate('secretaire', 'web')->givePermissionTo(['admin.access', 'users.manage']);
        Role::findOrCreate('serviceTechnique', 'web')->givePermissionTo(['admin.access', 'users.manage']);
        Role::findOrCreate('enseignant', 'web');
        Role::findOrCreate('caissier', 'web');
    }

    public function test_privileged_secretary_can_be_deleted_when_only_one_super_admin_exists(): void
    {
        User::factory()->create()->assignRole('superAdmin');
        $secretary = User::factory()->create(['is_active' => true]);
        $secretary->assignRole('secretaire');

        $response = $this->deleteUser($secretary);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSoftDeleted('users', ['id' => $secretary->id]);
        $this->assertDatabaseHas('users', ['id' => $secretary->id, 'is_active' => false]);
    }

    public function test_last_super_admin_remains_protected(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole('superAdmin');
        $actor = User::factory()->create(['is_active' => false]);
        $actor->assignRole('superAdmin');

        $response = $this->deleteUser($superAdmin, $actor);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertNotSoftDeleted('users', ['id' => $superAdmin->id]);
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'is_active' => true]);
    }

    public function test_one_of_two_super_admins_can_be_deleted(): void
    {
        $first = User::factory()->create(['is_active' => true]);
        $second = User::factory()->create(['is_active' => true]);
        $first->assignRole('superAdmin');
        $second->assignRole('superAdmin');

        $response = $this->deleteUser($first, $second);

        $response->assertOk();
        $this->assertSoftDeleted('users', ['id' => $first->id]);
        $this->assertDatabaseHas('users', ['id' => $second->id, 'is_active' => true]);
        $this->assertSame(1, User::role('superAdmin')->count());
    }

    public function test_non_super_admin_cli_actor_cannot_delete_super_admin_target(): void
    {
        $target = User::factory()->create(['is_active' => true]);
        $remaining = User::factory()->create(['is_active' => true]);
        $target->assignRole('superAdmin');
        $remaining->assignRole('superAdmin');

        $response = $this->deleteUser($target);

        $response->assertForbidden();
        $this->assertNotSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_non_super_admin_cli_actor_is_denied_before_last_active_admin_invariant(): void
    {
        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole('superAdmin');

        $response = $this->deleteUser($target);

        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => true]);
    }

    public function test_inactive_super_admin_does_not_satisfy_active_admin_invariant(): void
    {
        $active = User::factory()->create(['is_active' => true]);
        $inactive = User::factory()->create(['is_active' => false]);
        $active->assignRole('superAdmin');
        $inactive->assignRole('superAdmin');

        $response = $this->deleteUser($active, $inactive);

        $response->assertStatus(422);
        $this->assertNotSoftDeleted('users', ['id' => $active->id]);
        $this->assertDatabaseHas('users', ['id' => $active->id, 'is_active' => true]);
    }

    public function test_service_technique_account_remains_protected(): void
    {
        User::factory()->create(['is_active' => true])->assignRole('superAdmin');
        $support = User::factory()->create(['is_active' => true]);
        $support->assignRole('serviceTechnique');

        $this->deleteUser($support)->assertStatus(422);

        $this->assertNotSoftDeleted('users', ['id' => $support->id]);
    }

    public function test_self_deletion_remains_protected(): void
    {
        User::factory()->create(['is_active' => true])->assignRole('superAdmin');
        $actor = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($actor, ['cli:admin']);

        $this->postJson("/api/cli/user/{$actor->id}/delete")->assertStatus(422);

        $this->assertNotSoftDeleted('users', ['id' => $actor->id]);
    }

    public function test_cli_admin_ability_is_required(): void
    {
        User::factory()->create(['is_active' => true])->assignRole('superAdmin');
        $target = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs(User::factory()->create(), ['cli:read']);

        $this->postJson("/api/cli/user/{$target->id}/delete")->assertForbidden();

        $this->assertNotSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_successful_deletion_revokes_target_tokens(): void
    {
        User::factory()->create(['is_active' => true])->assignRole('superAdmin');
        $target = User::factory()->create(['is_active' => true]);
        $tokenId = $target->createToken('e2e-cleanup')->accessToken->id;

        $this->deleteUser($target)->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_bulk_deactivation_cannot_remove_all_active_super_admins(): void
    {
        $first = User::factory()->create(['is_active' => true]);
        $second = User::factory()->create(['is_active' => true]);
        $first->assignRole('superAdmin');
        $second->assignRole('superAdmin');

        try {
            app(SuperAdminLifecycleGuard::class)->deactivateUsers(
                [$first->id, $second->id],
                fn ($users) => $users->each->update(['is_active' => false]),
            );
            $this->fail('The lifecycle guard should reject deactivation of every active superAdmin.');
        } catch (LastActiveSuperAdminException) {
            $this->assertTrue(true);
        }

        $this->assertSame(2, User::role('superAdmin')->where('users.is_active', true)->count());
    }

    public function test_secretary_cannot_update_super_admin_through_generic_personnel_endpoint(): void
    {
        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole('superAdmin');
        $this->actingAsPersonnelManager();
        $request = Request::create('/', 'PUT', [
            'name' => $target->name,
            'email' => $target->email,
            'is_active' => false,
        ]);

        $response = app(ESBTPPersonnelController::class)->update(
            $request,
            $target,
            app(SuperAdminLifecycleGuard::class),
        );

        $this->assertSame(403, $response->getStatusCode());
        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => true]);
    }

    public function test_toggle_uses_locked_state_instead_of_stale_route_model(): void
    {
        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole('superAdmin');
        $this->actingAsTechnicalSupportManager();
        $target->is_active = false;

        $response = app(ESBTPPersonnelController::class)->toggleStatus(
            $target,
            app(SuperAdminLifecycleGuard::class),
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => true]);
    }

    public function test_bulk_personnel_endpoint_cannot_deactivate_all_active_super_admins(): void
    {
        $first = User::factory()->create(['is_active' => true]);
        $second = User::factory()->create(['is_active' => true]);
        $first->assignRole('superAdmin');
        $second->assignRole('superAdmin');
        $this->actingAsTechnicalSupportManager();
        $request = Request::create('/', 'POST', [
            'action' => 'deactivate',
            'ids' => [$first->id, $second->id],
        ]);

        $response = app(ESBTPPersonnelController::class)->bulkAction(
            $request,
            app(SuperAdminLifecycleGuard::class),
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(2, User::role('superAdmin')->where('users.is_active', true)->count());
    }

    public function test_secretary_cannot_delete_service_technique_through_generic_personnel_endpoint(): void
    {
        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole('serviceTechnique');
        $this->actingAsPersonnelManager();

        $response = app(ESBTPPersonnelController::class)->destroy(
            $target,
            app(SuperAdminLifecycleGuard::class),
        );

        $this->assertSame(403, $response->getStatusCode());
        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => true]);
    }

    public function test_manageable_secondary_role_cannot_bypass_protected_target_on_any_personnel_action(): void
    {
        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole(['superAdmin', 'enseignant']);
        $this->actingAsPersonnelManager();
        $lifecycle = app(SuperAdminLifecycleGuard::class);
        $updateRequest = Request::create('/', 'PUT', [
            'name' => $target->name,
            'email' => $target->email,
            'is_active' => true,
        ]);

        $update = app(ESBTPPersonnelController::class)->update($updateRequest, $target, $lifecycle);
        $toggle = app(ESBTPPersonnelController::class)->toggleStatus($target, $lifecycle);
        $destroy = app(ESBTPPersonnelController::class)->destroy($target, $lifecycle);
        $bulk = app(ESBTPPersonnelController::class)->bulkAction(
            Request::create('/', 'POST', ['action' => 'deactivate', 'ids' => [$target->id]]),
            $lifecycle,
        );

        $this->assertSame(403, $update->getStatusCode());
        $this->assertSame(403, $toggle->getStatusCode());
        $this->assertSame(403, $destroy->getStatusCode());
        $this->assertSame(403, $bulk->getStatusCode());
        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => true]);
    }

    public function test_legacy_secretary_toggle_cannot_deactivate_mixed_super_admin_target(): void
    {
        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole(['superAdmin', 'secretaire']);
        $this->actingAsPersonnelManager();

        $response = app(ESBTPSecretaireController::class)->toggleStatus(
            Request::create('/', 'POST'),
            $target->id,
            app(SuperAdminLifecycleGuard::class),
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertNotNull(session('error'));
        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => true]);
    }

    public function test_legacy_teacher_destroy_and_password_reset_reject_mixed_super_admin_target(): void
    {
        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole(['superAdmin', 'enseignant']);
        $teacher = ESBTPTeacher::create([
            'user_id' => $target->id,
            'matricule' => 'ENS-SECURITY-'.uniqid(),
            'status' => 'active',
        ]);
        $passwordHash = $target->password;
        $this->actingAsPersonnelManager();
        $controller = app(ESBTPEnseignantController::class);

        $reset = $controller->resetPassword(Request::create('/', 'POST'), $teacher);
        $toggle = $controller->toggleStatus(
            Request::create('/', 'POST'),
            $teacher,
            app(SuperAdminLifecycleGuard::class),
        );
        $destroy = $controller->destroy($teacher, app(SuperAdminLifecycleGuard::class));

        $this->assertSame(302, $reset->getStatusCode());
        $this->assertSame(302, $toggle->getStatusCode());
        $this->assertSame(302, $destroy->getStatusCode());
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => true,
            'password' => $passwordHash,
        ]);
        $this->assertDatabaseHas('esbtp_teachers', ['id' => $teacher->id, 'status' => 'active']);
        $this->assertTrue($target->fresh()->hasAllRoles(['superAdmin', 'enseignant']));
    }

    public function test_cli_password_expiry_reset_rejects_privileged_targets_for_non_super_admin(): void
    {
        $superAdmin = User::factory()->create(['must_change_password' => true]);
        $support = User::factory()->create(['must_change_password' => true]);
        $superAdmin->assignRole('superAdmin');
        $support->assignRole('serviceTechnique');
        Sanctum::actingAs(User::factory()->create(), ['cli:admin']);

        $this->postJson("/api/cli/user/{$superAdmin->id}/reset-password-expiry")->assertForbidden();
        $this->postJson("/api/cli/user/{$support->id}/reset-password-expiry")->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'must_change_password' => true]);
        $this->assertDatabaseHas('users', ['id' => $support->id, 'must_change_password' => true]);
    }

    public function test_caissier_actions_reject_mixed_super_admin_target_for_secretary(): void
    {
        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole(['superAdmin', 'caissier']);
        $passwordHash = $target->password;
        $this->actingAsPersonnelManager();
        $controller = app(ESBTPCaissierController::class);
        $lifecycle = app(SuperAdminLifecycleGuard::class);
        $updateRequest = Request::create('/', 'PUT', [
            'name' => $target->name,
            'email' => $target->email,
            'is_active' => false,
        ]);

        $update = $controller->update($updateRequest, $target, $lifecycle);
        $toggle = $controller->toggleStatus($target, $lifecycle);
        $destroy = $controller->destroy($target, $lifecycle);
        try {
            $controller->resetPassword($target);
            $this->fail('The target-aware policy should reject password reset.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        $this->assertSame(302, $update->getStatusCode());
        $this->assertSame(302, $toggle->getStatusCode());
        $this->assertSame(302, $destroy->getStatusCode());
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => true,
            'password' => $passwordHash,
        ]);
        $this->assertTrue($target->fresh()->hasAllRoles(['superAdmin', 'caissier']));
    }

    private function actingAsPersonnelManager(): User
    {
        $actor = User::factory()->create(['is_active' => true]);
        $actor->assignRole('secretaire');
        $this->actingAs($actor);

        return $actor;
    }

    private function actingAsTechnicalSupportManager(): User
    {
        $actor = User::factory()->create(['is_active' => true]);
        $actor->assignRole('serviceTechnique');
        $this->actingAs($actor);

        return $actor;
    }

    private function deleteUser(User $target, ?User $actor = null): \Illuminate\Testing\TestResponse
    {
        Sanctum::actingAs($actor ?? User::factory()->create(), ['cli:admin']);

        return $this->postJson("/api/cli/user/{$target->id}/delete");
    }
}
