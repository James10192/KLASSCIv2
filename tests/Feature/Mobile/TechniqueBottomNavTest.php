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

    public function test_sans_le_role_les_pages_du_service_technique_ne_s_affichent_pas(): void
    {
        // Un role custom peut declarer le profil : les routes, gardees par le
        // role serviceTechnique, ne lui sont pas ouvertes, donc aucun onglet mort.
        $role = Role::findOrCreate('Support interne', 'web');
        $role->forceFill(['mobile_profile' => MobileProfileResolver::TECHNIQUE])->save();
        $user = User::factory()->create();
        $user->assignRole($role);

        $html = $this->rendre($user);

        $this->assertStringNotContainsString(route('esbtp.paywall-config.index'), $html);
        $this->assertStringNotContainsString(route('esbtp.matricule-config.index'), $html);
        $this->assertStringContainsString('aria-label="Accueil"', $html);
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
