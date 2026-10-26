<?php

namespace Tests\Feature\Permissions;

use App\Helpers\RoleHelper;
use App\Models\User;
use App\Services\Personnel\RoleAssignmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Les rôles de départ passent tous par RoleAssignmentService (audités, jamais
 * sensibles), et les accès LMS / caissiers se décident par permission seule.
 */
final class RoleProvisioningAndPermissionOnlyTest extends TestCase
{
    use RefreshDatabase;

    private RoleAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['identity.teach', 'identity.student', 'identity.coordinate', 'system.manage', 'caissiers.view', 'caissiers.edit', 'users.manage'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        foreach (['etudiant', 'enseignant', 'coordinateur', 'caissier', 'comptable', 'superAdmin', 'serviceTechnique'] as $r) {
            Role::findOrCreate($r, 'web');
        }
        Role::findByName('enseignant', 'web')->givePermissionTo('identity.teach');
        Role::findByName('etudiant', 'web')->givePermissionTo('identity.student');
        Role::findByName('coordinateur', 'web')->givePermissionTo('identity.coordinate');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->service = app(RoleAssignmentService::class);
    }

    public function test_un_compte_neuf_recoit_son_role_de_depart_avec_une_trace_d_audit(): void
    {
        $compte = User::factory()->create();

        $this->assertTrue($this->service->provisionnerNouveauCompte($compte, 'etudiant', null, 'Création à l\'inscription'));

        $this->assertTrue($compte->fresh()->hasRole('etudiant'));
        $this->assertDatabaseHas('audits', [
            'auditable_type' => User::class,
            'auditable_id' => $compte->id,
        ]);
    }

    public function test_un_role_sensible_ne_s_attribue_jamais_a_la_creation(): void
    {
        foreach (RoleAssignmentService::ROLES_SENSIBLES as $role) {
            $compte = User::factory()->create();
            try {
                $this->service->provisionnerNouveauCompte($compte, $role, null, 'Tentative');
                $this->fail("Le rôle {$role} n'aurait pas dû être posé.");
            } catch (AuthorizationException) {
                $this->assertFalse($compte->fresh()->hasRole($role));
            }
        }
    }

    public function test_un_compte_existant_ne_change_pas_de_role_par_la_voie_de_creation(): void
    {
        $compte = User::factory()->create();
        $compte->assignRole('enseignant');

        $this->expectException(AuthorizationException::class);
        try {
            $this->service->provisionnerNouveauCompte($compte, 'coordinateur', null, 'Contournement');
        } finally {
            $this->assertSame(['enseignant'], $compte->fresh()->getRoleNames()->all());
        }
    }

    public function test_un_role_absent_de_l_etablissement_ne_fait_pas_d_erreur_500(): void
    {
        $compte = User::factory()->create();

        $this->assertFalse($this->service->provisionnerNouveauCompte($compte, 'role_inexistant', null, 'Import'));
        $this->assertSame([], $compte->fresh()->getRoleNames()->all());
    }

    public function test_le_retrait_pendant_desactivation_ne_retire_que_ce_role(): void
    {
        $compte = User::factory()->create();
        $compte->assignRole(['caissier', 'enseignant']);

        DB::transaction(fn () => $this->service->retirerPendantDesactivation($compte, 'caissier', null, 'Désactivation'));

        $this->assertSame(['enseignant'], $compte->fresh()->getRoleNames()->all());
    }

    public function test_le_lms_suit_les_permissions_pas_le_premier_role(): void
    {
        // Multi-fonctions dont le premier rôle n'ouvre pas le LMS.
        $multi = User::factory()->create();
        $multi->assignRole(['comptable', 'enseignant']);
        $this->assertTrue(RoleHelper::peutAccederAuLms($multi));

        // Permission directe, sans aucun rôle LMS.
        $direct = User::factory()->create();
        $direct->givePermissionTo('identity.coordinate');
        $this->assertTrue(RoleHelper::peutAccederAuLms($direct));

        // Nom de rôle puissant mais aucune permission : refus.
        $nomSeul = User::factory()->create();
        $nomSeul->assignRole('superAdmin');
        $this->assertFalse(RoleHelper::peutAccederAuLms($nomSeul));
    }

    public function test_la_gestion_des_caissiers_depend_de_la_permission_pas_du_role(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        $caissier = User::factory()->create();
        $caissier->assignRole('caissier');

        // Le groupe de routes exige system.manage ; le contrôleur exige en
        // plus caissiers.view (ou users.manage). Le nom « comptable » n'ouvre rien.
        $sansDroitCaissier = User::factory()->create();
        $sansDroitCaissier->assignRole('comptable');
        $sansDroitCaissier->givePermissionTo('system.manage');
        $this->actingAs($sansDroitCaissier)
            ->get(route('esbtp.caissiers.show', $caissier))
            ->assertForbidden();

        $autorise = User::factory()->create();
        $autorise->givePermissionTo(['system.manage', 'caissiers.view']);
        $status = $this->actingAs($autorise)->get(route('esbtp.caissiers.show', $caissier))->getStatusCode();
        $this->assertNotSame(403, $status);
        $this->assertLessThan(500, $status);
    }
}
