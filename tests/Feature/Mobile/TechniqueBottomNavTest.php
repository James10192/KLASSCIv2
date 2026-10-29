<?php

namespace Tests\Feature\Mobile;

use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Profil mobile « technique » : le service technique porte toutes les
 * permissions, la cascade le rangeait en caisse (« Encaisser », « Ma caisse »).
 * Il recoit desormais ses propres pages, chacune gardee par sa vraie route.
 */
class TechniqueBottomNavTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        MobileProfileResolver::oublier();
    }

    protected function tearDown(): void
    {
        MobileProfileResolver::oublier();
        parent::tearDown();
    }

    public function test_le_service_technique_a_ses_pages_et_pas_la_caisse(): void
    {
        $permissions = ['module.caisse.access', 'paiements.create', 'cash_session.manage', 'paywall.manage'];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $role = Role::findOrCreate('serviceTechnique', 'web');
        $role->givePermissionTo($permissions);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertSame(MobileProfileResolver::TECHNIQUE, app(MobileProfileResolver::class)->resolve($user));

        $html = $this->rendre($user);

        $this->assertStringContainsString('aria-label="Abonnement"', $html);
        $this->assertStringContainsString('aria-label="Matricules"', $html);
        $this->assertStringContainsString('aria-label="Rôles"', $html);
        $this->assertStringContainsString(route('esbtp.bulletin-style.index'), $html);
        $this->assertStringNotContainsString('aria-label="Encaisser"', $html);
        $this->assertStringNotContainsString('aria-label="Ma caisse"', $html);
    }

    public function test_le_superadmin_ne_voit_pas_technique_dans_sa_bascule(): void
    {
        Role::findOrCreate('superAdmin', 'web');
        $user = User::factory()->create();
        $user->assignRole('superAdmin');
        $this->actingAs($user);

        $html = view('layouts.partials.mobile.bottom-nav', [
            'mobileProfile' => MobileProfileResolver::COMPTABLE,
            'mobileShellEnabled' => true,
        ])->render();

        $this->assertStringContainsString('name="profil" value="scolarite"', $html);
        $this->assertStringNotContainsString('value="technique"', $html);
    }

    private function rendre(User $user): string
    {
        $this->actingAs($user);

        return view('layouts.partials.mobile.bottom-nav', [
            'mobileProfile' => MobileProfileResolver::TECHNIQUE,
            'mobileShellEnabled' => true,
        ])->render();
    }
}
