<?php

namespace Tests\Feature\Mobile;

use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Profil mobile « scolarite » : secretariat, scolarite, agent d'inscription,
 * coordination. Chaque onglet passe par la garde reelle de sa route : un lien
 * qu'on ne peut pas ouvrir n'est jamais affiche. Les trois premieres pages
 * ouvertes deviennent des onglets, dans un ordre qui suit le metier.
 */
class ScolariteBottomNavTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = [
        'admin.access', 'identity.enrollment_officer', 'identity.coordinate', 'identity.direct_studies',
        'inscriptions.view', 'inscriptions.create', 'inscriptions.validate', 'students.view', 'classes.view',
        'paiements.view', 'timetables.view', 'notes.view', 'module.notes_evaluations.access', 'annonces.view',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        MobileProfileResolver::oublier();
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    protected function tearDown(): void
    {
        MobileProfileResolver::oublier();
        parent::tearDown();
    }

    public function test_un_agent_d_inscription_a_les_dossiers_en_onglets(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['identity.enrollment_officer', 'inscriptions.view', 'inscriptions.create', 'students.view', 'classes.view']);

        $html = $this->rendre($user);

        $this->assertStringContainsString('class="m-bottomnav"', $html);
        $this->assertStringContainsString('aria-label="Inscriptions"', $html);
        $this->assertStringContainsString('aria-label="Étudiants"', $html);
        $this->assertStringContainsString('aria-label="Classes"', $html);
        $this->assertStringContainsString('Nouvelle inscription', $html);
        // Sans permission, pas de lien — ni onglet, ni entrée de la feuille.
        $this->assertStringNotContainsString(route('esbtp.paiements.index'), $html);
        // Emplois du temps : la route n'admet pas l'agent d'inscription.
        $this->assertStringNotContainsString(route('esbtp.emploi-temps.index'), $html);
    }

    public function test_la_coordination_pedagogique_a_classes_edt_et_notes(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['admin.access', 'identity.direct_studies', 'identity.coordinate', 'inscriptions.view', 'students.view', 'classes.view', 'timetables.view', 'notes.view', 'module.notes_evaluations.access']);

        $html = $this->rendre($user);

        $this->assertStringContainsString('aria-label="Classes"', $html);
        $this->assertStringContainsString('aria-label="EDT"', $html);
        $this->assertStringContainsString('aria-label="Notes"', $html);
        $this->assertStringNotContainsString('aria-label="Inscriptions"', $html);
        // Les inscriptions restent joignables par « Plus ».
        $this->assertStringContainsString(route('esbtp.inscriptions.index'), $html);
    }

    public function test_qui_valide_des_inscriptions_garde_les_dossiers_en_tete(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['admin.access', 'identity.coordinate', 'inscriptions.view', 'inscriptions.validate', 'students.view', 'paiements.view', 'classes.view']);

        $html = $this->rendre($user);

        $this->assertStringContainsString('aria-label="Inscriptions"', $html);
        $this->assertStringContainsString('aria-label="Paiements"', $html);
        $this->assertStringNotContainsString('aria-label="Classes"', $html);
        $this->assertStringContainsString(route('esbtp.classes.index'), $html);
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
