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

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('dashboard.view', 'web');
        Permission::findOrCreate('inscriptions.validate', 'web');
        Role::findOrCreate('superAdmin', 'web');
        Role::findOrCreate('agentInscription', 'web');
    }

    public function test_cli_can_create_custom_role_and_assign_it(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('superAdmin');
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
        ])->assertOk()
            ->assertJsonPath('data.roles_after.0', 'informaticien');

        $this->assertTrue($target->fresh()->hasRole('informaticien'));
        $this->assertFalse($target->fresh()->hasRole('agentInscription'));
    }

    public function test_cli_rejects_unknown_role_assignment(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('superAdmin');
        Sanctum::actingAs($actor, ['cli:admin']);

        $target = User::factory()->create();

        $this->postJson("/api/cli/user/{$target->id}/role", [
            'role' => 'does_not_exist',
        ])->assertStatus(422);
    }

    public function test_cli_rejects_reserved_canonical_role_name(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('superAdmin');
        Sanctum::actingAs($actor, ['cli:admin']);

        $this->postJson('/api/cli/roles', [
            'name' => 'coordinateur',
            'label_fr' => 'Fake',
        ])->assertStatus(422);
    }
}
