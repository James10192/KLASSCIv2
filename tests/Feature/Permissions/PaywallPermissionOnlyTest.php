<?php

namespace Tests\Feature\Permissions;

use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Models\User;
use App\Services\PermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class PaywallPermissionOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([CheckInstalled::class, EnsureInstalled::class]);
        Permission::findOrCreate('paywall.manage', 'web');
        Role::findOrCreate('serviceTechnique', 'web');
        Role::findOrCreate('superAdmin', 'web');

        // L'accès paywall est décidé en amont du contrôleur métier.
        Route::middleware(['web', 'auth', 'paywall'])
            ->get('/__test/permission-only-paywall', fn () => response('autorise'))
            ->name('esbtp.paywall-config.test-access');
    }

    public function test_role_technique_sans_permission_refuse(): void
    {
        $user = User::factory()->create();
        $user->assignRole('serviceTechnique');

        $this->actingAs($user)->get('/__test/permission-only-paywall')
            ->assertRedirect(route('esbtp.paywall-config.blocked'));
    }

    public function test_permission_directe_autorise_sans_role_technique(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('paywall.manage');

        $this->actingAs($user)->get('/__test/permission-only-paywall')
            ->assertOk()->assertSee('autorise');
    }

    public function test_superadmin_sans_permission_ne_contourne_pas_le_paywall(): void
    {
        $user = User::factory()->create();
        $user->assignRole('superAdmin');

        $this->actingAs($user)->get('/__test/permission-only-paywall')
            ->assertRedirect(route('esbtp.paywall-config.blocked'));
    }

    public function test_defaults_techniques_ne_proviennent_pas_du_joker_superadmin(): void
    {
        $registry = app(PermissionRegistry::class);
        foreach (['paywall.manage', 'system.technical_access', 'superadmin.accounts.create'] as $permission) {
            $this->assertContains($permission, $registry->defaultPermissionsFor('serviceTechnique'));
            $this->assertNotContains($permission, $registry->defaultPermissionsFor('superAdmin'));
        }
    }
}
