<?php

namespace Tests\Feature\BtsTroncCommun;

use App\Models\ESBTPInscriptionPhase;
use Tests\TestCase;

/**
 * Tests Feature pour Alt C — fix BTS Tronc Commun (2 bugs + workflow officiel) :
 *  - Routes admin orientation-targets existent + permission gate
 *  - Routes specialisation officielles (GET/POST + classes AJAX)
 *  - Vue show inscription inclut le bouton "Orienter"
 *  - Vue edit inscription contient la soupape "correction d'erreur"
 *  - Vue specialisation v2 premium (namespace spc-*)
 *  - Modèle ESBTPInscriptionPhase est Auditable
 *  - Permissions registry contient bts_tronc_commun.*
 *
 * Pas de DB nécessaire — TestCase pur.
 */
class OrientationFlowsTest extends TestCase
{
    /* ════════════ ROUTES ════════════ */

    public function test_specialisation_get_route_exists(): void
    {
        $route = \Route::getRoutes()->getByName('esbtp.inscriptions.specialisation');
        $this->assertNotNull($route, 'esbtp.inscriptions.specialisation doit exister');
        $this->assertContains('GET', $route->methods());
    }

    public function test_specialisation_post_route_exists(): void
    {
        $route = \Route::getRoutes()->getByName('esbtp.inscriptions.specialisation.store');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
    }

    public function test_orientation_targets_admin_routes_exist(): void
    {
        $routes = [
            'esbtp.admin.orientation-targets.index',
            'esbtp.admin.orientation-targets.store',
            'esbtp.admin.orientation-targets.update',
            'esbtp.admin.orientation-targets.destroy',
        ];
        foreach ($routes as $name) {
            $this->assertNotNull(
                \Route::getRoutes()->getByName($name),
                "Route '{$name}' doit exister"
            );
        }
    }

    public function test_orientation_targets_routes_require_auth(): void
    {
        $response = $this->get('/esbtp/admin/orientation-targets');
        $this->assertContains($response->status(), [302, 401, 403, 419]);
    }

    /* ════════════ PERMISSIONS REGISTRY ════════════ */

    public function test_bts_tronc_commun_permissions_in_registry(): void
    {
        $perms = config('permissions.permissions');
        $this->assertArrayHasKey('bts_tronc_commun.orient', $perms);
        $this->assertArrayHasKey('bts_tronc_commun.manage_targets', $perms);
        $this->assertArrayHasKey('bts_tronc_commun.view_history', $perms);
        $this->assertSame('Orienter un étudiant BTS tronc commun', $perms['bts_tronc_commun.orient']['label']);
        $this->assertSame('Configurer les sorties BTS tronc commun', $perms['bts_tronc_commun.manage_targets']['label']);
    }

    /* ════════════ MODÈLE Auditable ════════════ */

    public function test_inscription_phase_is_auditable(): void
    {
        $phase = new ESBTPInscriptionPhase();
        $this->assertInstanceOf(\OwenIt\Auditing\Contracts\Auditable::class, $phase);
    }

    public function test_inscription_phase_audit_include_covers_orientation_fields(): void
    {
        $phase = new ESBTPInscriptionPhase();
        $reflection = new \ReflectionClass($phase);
        $prop = $reflection->getProperty('auditInclude');
        $prop->setAccessible(true);
        $include = $prop->getValue($phase);

        $expected = ['inscription_id', 'type_phase', 'classe_id', 'filiere_id', 'is_active', 'orientation_target_id'];
        foreach ($expected as $field) {
            $this->assertContains($field, $include, "Champ {$field} doit être audité (conformité UEMOA)");
        }
    }

    /* ════════════ VUES ════════════ */

    public function test_bts_journey_partial_includes_orient_button_logic(): void
    {
        $content = file_get_contents(resource_path('views/esbtp/partials/bts-journey.blade.php'));
        $this->assertStringContainsString('Orienter vers une spécialité', $content);
        $this->assertStringContainsString("bts_tronc_commun.orient", $content);
        $this->assertStringContainsString('orientationTargetsCount', $content);
        $this->assertStringContainsString("legacy_dual_inscription", $content, 'Skip legacy mode');
    }

    public function test_edit_form_has_correction_saisie_checkbox(): void
    {
        $content = file_get_contents(resource_path('views/esbtp/inscriptions/partials/edit-form.blade.php'));
        $this->assertStringContainsString('correction_saisie', $content);
        $this->assertStringContainsString('correction_motif', $content);
        $this->assertStringContainsString('Tronc Commun', $content);
        $this->assertStringContainsString('workflow officiel UEMOA', $content);
    }

    public function test_specialisation_view_uses_premium_namespace(): void
    {
        $content = file_get_contents(resource_path('views/esbtp/inscriptions/specialisation.blade.php'));
        $this->assertStringContainsString('spc-hero', $content, 'Namespace premium spc-*');
        $this->assertStringContainsString('spc-card', $content);
        $this->assertStringContainsString('spc-stepper', $content, 'Stepper visuel 3 étapes');
        $this->assertStringContainsString('x-data="specialisation()"', $content, 'Alpine state');
        $this->assertStringContainsString('linear-gradient(135deg, #0a3d8f', $content, 'Hero gradient KLASSCI');
    }

    public function test_admin_orientation_targets_view_exists(): void
    {
        $this->assertTrue(view()->exists('esbtp.admin.orientation-targets.index'));
    }

    /* ════════════ CONTROLLER LOGIC ════════════ */

    public function test_inscription_controller_imports_bts_orientation_service(): void
    {
        $content = file_get_contents(app_path('Http/Controllers/ESBTPInscriptionController.php'));
        $this->assertStringContainsString('use App\Domain\BtsTroncCommun\BtsOrientationService;', $content);
        $this->assertStringContainsString('syncAfterClassChange', $content, 'Délégation à syncAfterClassChange dans update()');
        $this->assertStringContainsString('correction_saisie', $content, 'Soupape correction d\'erreur backend');
        $this->assertStringContainsString('isInTroncCommunActif', $content, 'Détection phase TC active');
    }

    public function test_orientation_target_controller_has_permission_middleware(): void
    {
        $content = file_get_contents(app_path('Http/Controllers/Admin/BtsOrientationTargetController.php'));
        $this->assertStringContainsString("permission:bts_tronc_commun.manage_targets", $content);
    }

    /* ════════════ SEEDER ════════════ */

    public function test_orientation_targets_seeder_exists(): void
    {
        $this->assertFileExists(database_path('seeders/EsbtpClasseOrientationTargetSeeder.php'));
        $content = file_get_contents(database_path('seeders/EsbtpClasseOrientationTargetSeeder.php'));
        $this->assertStringContainsString('updateOrCreate', $content, 'Idempotent');
        $this->assertStringContainsString('NAME_OVERRIDES', $content, 'Overrides manuels supportés');
    }
}
