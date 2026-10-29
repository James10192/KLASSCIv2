<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Providers\AuthServiceProvider;
use App\Services\EnrollmentAmountVisibility;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * La porte `finances.etudiants.voir`, et le service qui la relaie.
 *
 * Le cas qui a motive la porte : un profil pedagogique (directeur des etudes,
 * enseignant) portant `admin.access` mais aucune permission financiere lisait
 * les soldes des etudiants.
 */
class EnrollmentAmountVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $noms = array_merge(AuthServiceProvider::PERMISSIONS_FINANCES_ETUDIANTS, [
            'admin.access', 'students.view', 'inscriptions.view', 'identity.enrollment_officer', 'frais.view',
        ]);
        foreach ($noms as $nom) {
            Permission::findOrCreate($nom, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function utilisateurAvec(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user->fresh();
    }

    public function test_un_profil_pedagogique_avec_admin_access_ne_voit_pas_les_montants(): void
    {
        $user = $this->utilisateurAvec(['admin.access', 'students.view', 'inscriptions.view', 'frais.view']);

        $this->assertFalse($user->can('finances.etudiants.voir'));
        $this->assertTrue(app(EnrollmentAmountVisibility::class)->hideAmounts($user));
    }

    public function test_un_agent_d_inscription_ne_voit_pas_les_montants(): void
    {
        $user = $this->utilisateurAvec(['inscriptions.view', 'identity.enrollment_officer']);

        $this->assertTrue(app(EnrollmentAmountVisibility::class)->hideAmounts($user));
    }

    public function test_chaque_permission_financiere_ouvre_la_porte(): void
    {
        foreach (AuthServiceProvider::PERMISSIONS_FINANCES_ETUDIANTS as $permission) {
            $user = $this->utilisateurAvec([$permission]);

            $this->assertTrue($user->can('finances.etudiants.voir'), $permission);
            $this->assertFalse(app(EnrollmentAmountVisibility::class)->hideAmounts($user), $permission);
        }
    }

    public function test_sans_utilisateur_les_montants_sont_masques(): void
    {
        $this->assertTrue(app(EnrollmentAmountVisibility::class)->hideAmounts(null));
    }
}
