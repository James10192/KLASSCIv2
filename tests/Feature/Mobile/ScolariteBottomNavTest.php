<?php

namespace Tests\Feature\Mobile;

use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Profil mobile « scolarite » : secretariat, scolarite, agent d'inscription,
 * coordination. Ils recevaient la mise en page de bureau sur leur telephone.
 * La barre du bas suit leurs permissions : un onglet qu'on ne peut pas ouvrir
 * n'est pas affiche, et qui ne voit pas les paiements a les classes a la place.
 */
class ScolariteBottomNavTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        MobileProfileResolver::oublier();
        foreach (['inscriptions.view', 'inscriptions.create', 'students.view', 'classes.view', 'paiements.view', 'bulletins.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    protected function tearDown(): void
    {
        MobileProfileResolver::oublier();
        parent::tearDown();
    }

    public function test_les_onglets_suivent_les_permissions(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['inscriptions.view', 'inscriptions.create', 'students.view', 'paiements.view', 'classes.view']);

        $html = $this->rendre($user);

        $this->assertStringContainsString('class="m-bottomnav"', $html);
        $this->assertStringContainsString('aria-label="Inscriptions"', $html);
        $this->assertStringContainsString('aria-label="Étudiants"', $html);
        $this->assertStringContainsString('aria-label="Paiements"', $html);
        $this->assertStringNotContainsString('aria-label="Classes"', $html);
        // Les classes passent dans la feuille « Plus », avec la nouvelle inscription.
        $this->assertStringContainsString(route('esbtp.classes.index'), $html);
        $this->assertStringContainsString('Nouvelle inscription', $html);
        // Pas de lien vers un écran refusé.
        $this->assertStringNotContainsString(route('esbtp.bulletins.index'), $html);
    }

    public function test_sans_paiements_les_classes_prennent_l_onglet(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['students.view', 'classes.view']);

        $html = $this->rendre($user);

        $this->assertStringNotContainsString('aria-label="Paiements"', $html);
        $this->assertStringNotContainsString('aria-label="Inscriptions"', $html);
        $this->assertStringContainsString('aria-label="Classes"', $html);
        $this->assertSame(1, substr_count($html, 'href="' . route('esbtp.classes.index') . '"') / 2);
    }

    private function rendre(User $user): string
    {
        $this->actingAs($user);

        return view('layouts.partials.mobile.bottom-nav', [
            'mobileProfile' => MobileProfileResolver::SCOLARITE,
            'mobileShellEnabled' => true,
        ])->render();
    }
}
