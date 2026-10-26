<?php

namespace Tests\Feature\API\CLI;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class CliUsersReadPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\CheckInstalled::class);
        Role::findOrCreate('enseignant', 'web');
        Role::findOrCreate('coordinateur', 'web');
        Permission::findOrCreate('personnel.view', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_cli_read_ability_ne_suffit_pas_pour_lister_les_comptes(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($actor, ['cli:read']);

        $this->getJson('/api/cli/users')->assertForbidden();
    }

    public function test_la_liste_preserve_role_legacy_et_expose_tous_les_roles(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $actor->givePermissionTo('personnel.view');
        Sanctum::actingAs($actor, ['cli:read']);

        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole(['enseignant', 'coordinateur']);

        $result = $this->getJson('/api/cli/users?search='.urlencode($target->username))
            ->assertOk()
            ->assertJsonCount(1, 'data.users');

        $role = $result->json('data.users.0.role');
        $roles = $result->json('data.users.0.roles');
        $this->assertContains($role, $roles);
        $this->assertEqualsCanonicalizing(['enseignant', 'coordinateur'], $roles);
        $this->assertArrayNotHasKey('password', $result->json('data.users.0'));
    }

    public function test_limite_de_pagination_negative_reste_positive(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $actor->givePermissionTo('personnel.view');
        Sanctum::actingAs($actor, ['cli:read']);

        $this->getJson('/api/cli/users?limit=-10')
            ->assertOk()
            ->assertJsonPath('data.pagination.per_page', 1);
    }
}
