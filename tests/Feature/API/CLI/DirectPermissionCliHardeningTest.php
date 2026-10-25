<?php

namespace Tests\Feature\API\CLI;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class DirectPermissionCliHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\CheckInstalled::class);
        Role::findOrCreate('superAdmin', 'web');
        foreach (['personnel.manage', 'permissions.individuels.permanents.manage', 'notes.edit'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }

    private function acteur(bool $permanent = true): User
    {
        $acteur = User::factory()->create(['is_active' => true]);
        $permissions = ['personnel.manage', 'notes.edit'];
        if ($permanent) {
            $permissions[] = 'permissions.individuels.permanents.manage';
        }
        $acteur->givePermissionTo($permissions);
        Sanctum::actingAs($acteur, ['cli:admin']);

        return $acteur;
    }

    public function test_token_cli_sans_permission_nominative_ne_peut_pas_accorder(): void
    {
        $this->acteur(false);
        $cible = User::factory()->create(['is_active' => true]);

        $this->postJson("/api/cli/user/{$cible->id}/permissions", [
            'permissions' => ['notes.edit'],
        ])->assertForbidden();
        $this->assertFalse($cible->fresh()->hasDirectPermission('notes.edit'));
    }

    public function test_delegation_directe_exige_auteur_habilite_et_audit_dans_la_transaction(): void
    {
        $auteur = $this->acteur();
        $cible = User::factory()->create(['is_active' => true]);

        $this->postJson("/api/cli/user/{$cible->id}/permissions", [
            'permissions' => ['notes.edit'], 'mode' => 'grant',
        ])->assertOk();
        $this->assertTrue($cible->fresh()->hasDirectPermission('notes.edit'));
        $this->assertDatabaseHas('audits', [
            'auditable_type' => User::class, 'auditable_id' => $cible->id,
            'user_id' => $auteur->id, 'tags' => 'permissions',
        ]);

        $this->postJson("/api/cli/user/{$cible->id}/permissions", [
            'permissions' => ['notes.edit'], 'mode' => 'revoke',
        ])->assertOk();
        $this->assertFalse($cible->fresh()->hasDirectPermission('notes.edit'));
    }

    public function test_permissions_directes_ne_modifient_pas_un_compte_superadmin_multirole(): void
    {
        $this->acteur();
        $autre = Role::findOrCreate('coordinateur', 'web');
        $cible = User::factory()->create(['is_active' => true]);
        $cible->assignRole(['superAdmin', $autre->name]);

        $this->postJson("/api/cli/user/{$cible->id}/permissions", [
            'permissions' => ['notes.edit'],
        ])->assertForbidden();
        $this->assertFalse($cible->fresh()->hasDirectPermission('notes.edit'));
    }

    public function test_token_cli_seul_ne_peut_pas_se_donner_de_nouveaux_droits(): void
    {
        $acteur = $this->acteur();
        $this->postJson("/api/cli/user/{$acteur->id}/permissions", [
            'permissions' => ['notes.edit'],
        ])->assertForbidden();
    }
}
