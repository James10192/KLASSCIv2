<?php

namespace Tests\Feature\API\CLI;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Exporter un hash ou réinitialiser un compte exige un droit spécifique
 * ET le périmètre effectif de gestion de la cible.
 */
final class CliSensitiveAccountScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\CheckInstalled::class);

        foreach ([
            'users.manage', 'personnel.users.credentials.export',
            'personnel.users.password.reset', 'secret.elevated.scope',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function cliActor(array $abilities): User
    {
        $actor = User::factory()->create(['is_active' => true]);
        $actor->givePermissionTo($abilities);
        Sanctum::actingAs($actor, ['cli:admin']);

        return $actor;
    }

    public function test_export_bcrypt_refuse_compte_hors_perimetre_sans_reveler_le_hash(): void
    {
        $this->cliActor(['users.manage', 'personnel.users.credentials.export']);
        $target = User::factory()->create(['is_active' => true]);
        $target->givePermissionTo('secret.elevated.scope');

        $response = $this->getJson("/api/cli/user/{$target->id}/credentials")
            ->assertForbidden();

        $this->assertStringNotContainsString($target->password, $response->getContent());
        $this->assertArrayNotHasKey('password_hash', $response->json('data') ?? []);
    }

    public function test_reset_mot_de_passe_refuse_compte_hors_perimetre_sans_mutation(): void
    {
        $this->cliActor(['users.manage', 'personnel.users.password.reset']);
        $target = User::factory()->create(['is_active' => true, 'must_change_password' => true]);
        $target->givePermissionTo('secret.elevated.scope');
        $original = $target->password;

        $this->postJson("/api/cli/user/{$target->id}/reset-password", [
            'password' => 'Unique2026#Reset!B35',
        ])->assertForbidden();

        $this->assertSame($original, $target->fresh()->password);
        $this->assertTrue($target->fresh()->must_change_password);
    }

    public function test_expiration_mot_de_passe_refuse_compte_hors_perimetre(): void
    {
        $this->cliActor(['users.manage', 'personnel.users.password.reset']);
        $target = User::factory()->create(['is_active' => true, 'must_change_password' => true]);
        $target->givePermissionTo('secret.elevated.scope');

        $this->postJson("/api/cli/user/{$target->id}/reset-password-expiry")
            ->assertForbidden();

        $this->assertTrue($target->fresh()->must_change_password);
    }

    public function test_reset_legitime_audite_atomiquement_sans_hash_en_audit(): void
    {
        $actor = $this->cliActor(['users.manage', 'personnel.users.password.reset']);
        $target = User::factory()->create(['is_active' => true]);
        $newPassword = 'Unique2026#Reset!B35';

        $this->postJson("/api/cli/user/{$target->id}/reset-password", [
            'password' => $newPassword,
        ])->assertOk();

        $this->assertTrue(Hash::check($newPassword, $target->fresh()->password));
        $audit = Audit::query()->where('auditable_type', User::class)
            ->where('auditable_id', $target->id)
            ->where('user_id', $actor->id)
            ->where('tags', 'permissions')
            ->latest('id')->firstOrFail();

        $data = json_encode($audit->new_values, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('cli_password_reset', $data);
        $this->assertStringNotContainsString($target->fresh()->password, $data);
        $this->assertStringNotContainsString($newPassword, $data);
    }
}
