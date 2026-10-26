<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class McpAndMobilePermissionOnlyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['mcp.connections.connect', 'mcp.connections.approve', 'mobile.profile.preview'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        config()->set('mcp.pairing.enabled', true);
        config()->set('mcp.bridge.enabled', true);
        config()->set('mcp.read_only.enabled', true);
        config()->set('mcp.bridge.organization', 'tenant-test-mcp');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_un_nom_superadmin_ne_suffit_plus_pour_les_approbations_mcp(): void
    {
        Role::findOrCreate('superAdmin', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('superAdmin');

        $this->actingAs($user)->get(route('nanan.mcp.review'))->assertForbidden();
        $this->actingAs($user)->postJson(route('nanan.mcp.connections.approve'), [])->assertForbidden();

        $user->givePermissionTo('mcp.connections.approve');
        $this->actingAs($user->fresh())->get(route('nanan.mcp.review'))->assertOk();
        $user->revokePermissionTo('mcp.connections.approve');
        $this->actingAs($user->fresh())->get(route('nanan.mcp.review'))->assertForbidden();
    }

    public function test_compte_custom_peut_se_connecter_avec_droit_explicite_sans_pouvoir_approuver(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->get(route('nanan.mcp.connect'))->assertForbidden();
        $user->givePermissionTo('mcp.connections.connect');
        $this->actingAs($user->fresh())->get(route('nanan.mcp.connect'))->assertOk();
        $this->actingAs($user)->get(route('nanan.mcp.review'))->assertForbidden();

        $user->revokePermissionTo('mcp.connections.connect');
        $this->actingAs($user->fresh())->get(route('nanan.mcp.connect'))->assertForbidden();
    }

    public function test_apercu_mobile_avec_permission_et_revocation(): void
    {
        Role::findOrCreate('superAdmin', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('superAdmin');

        $this->actingAs($user)->postJson(route('mobile.profil'), ['profil' => 'enseignant'])->assertForbidden();
        $user->givePermissionTo('mobile.profile.preview');
        $this->actingAs($user->fresh())->postJson(route('mobile.profil'), ['profil' => 'enseignant'])
            ->assertOk()->assertJsonPath('profil', 'enseignant');
        $user->revokePermissionTo('mobile.profile.preview');
        $this->actingAs($user->fresh())->postJson(route('mobile.profil'), ['profil' => 'comptable'])->assertForbidden();
    }

    public function test_mcp_permissions_sont_distinctes_de_users_manage(): void
    {
        $this->assertArrayHasKey('mcp.connections.approve', config('permissions.permissions'));
        $this->assertArrayHasKey('mcp.connections.connect', config('permissions.permissions'));
        $this->assertNotContains('mcp.connections.approve', config('permissions.role_defaults.enseignant'));
    }
}
