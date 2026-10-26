<?php

namespace Tests\Feature\API\CLI;

use App\Listeners\AuditPermissionChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class SuperAdminInitialCreationCliTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\CheckInstalled::class);
        Role::findOrCreate('superAdmin', 'web');
        foreach (['users.manage', 'personnel.roles.assign', 'superadmin.accounts.create'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function actor(bool $canCreateSuperAdmin = true): User
    {
        $user = User::factory()->create(['is_active' => true]);
        foreach (['users.manage', 'personnel.roles.assign'] as $name) {
            $user->givePermissionTo($name);
        }
        if ($canCreateSuperAdmin) {
            $user->givePermissionTo('superadmin.accounts.create');
        }
        Sanctum::actingAs($user, ['cli:admin']);
        return $user;
    }

    private function payload(string $username): array
    {
        return [
            'name' => 'Compte Superadmin initial',
            'email' => $username.'@example.test',
            'username' => $username,
            'password' => 'Klassci-8F7!SecureNew#2026',
            'role' => 'superAdmin',
            'motif' => 'Création initiale autorisée par la gouvernance',
        ];
    }

    public function test_creation_initiale_habilitee_est_atomique_et_auditee(): void
    {
        $actor = $this->actor();

        $response = $this->postJson('/api/cli/user/create', $this->payload('new_initial_superadmin'))
            ->assertOk()
            ->assertJsonPath('data.role', 'superAdmin');

        $target = User::findOrFail($response->json('data.user_id'));
        $this->assertTrue($target->hasRole('superAdmin'));
        $this->assertSame($actor->id, $target->created_by);

        $this->assertDatabaseHas('audits', [
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
            'user_id' => $actor->id,
            'tags' => 'permissions',
        ]);
    }

    public function test_permission_specifique_absente_refuse_sans_compte_orphelin(): void
    {
        $this->actor(false);
        $this->postJson('/api/cli/user/create', $this->payload('denied_initial_superadmin'))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['username' => 'denied_initial_superadmin']);
    }

    public function test_promotion_directe_d_un_compte_existant_reste_interdite(): void
    {
        $this->actor();
        $target = User::factory()->create(['is_active' => true]);

        $this->postJson("/api/cli/user/{$target->id}/role", [
            'role' => 'superAdmin',
            'mode' => 'add',
            'motif' => 'Tentative de promotion sans double approbation',
        ])->assertForbidden();

        $this->assertFalse($target->fresh()->hasRole('superAdmin'));
    }

    public function test_echec_de_l_audit_annule_compte_et_attribution(): void
    {
        $this->actor();
        $audit = Mockery::mock(AuditPermissionChange::class);
        $audit->shouldReceive('enregistrerDecisionDeCompte')
            ->once()->andThrow(new \RuntimeException('Audit de test indisponible'));
        $this->app->instance(AuditPermissionChange::class, $audit);

        $this->postJson('/api/cli/user/create', $this->payload('rollback_initial_superadmin'))
            ->assertStatus(500);

        $this->assertDatabaseMissing('users', ['username' => 'rollback_initial_superadmin']);
    }
}
