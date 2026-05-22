<?php

namespace Tests\Feature\LMD;

use App\Models\ESBTPExamenPlanifie;
use Tests\TestCase;

/**
 * Tests Feature pour le chantier multi-classes UEMOA :
 *  - routes (options, ecues-by-parcours, resolve-scope-classes)
 *  - migration pivot esbtp_examen_classes appliquée
 *  - modèle expose relations classes() / uniteEnseignement()
 *  - vue modal contient la cascade Parcours → UE → ECUE
 *  - constantes SCOPE_TYPES disponibles
 *
 * Pas de DB nécessaire — TestCase pur.
 */
class ExamensMultiClassesTest extends TestCase
{
    public function test_ecues_by_parcours_route_exists(): void
    {
        $route = \Route::getRoutes()->getByName('esbtp.examens.ecues-by-parcours');
        $this->assertNotNull($route, 'Route ecues-by-parcours doit exister');
        $this->assertContains('GET', $route->methods());
    }

    public function test_resolve_scope_classes_route_exists(): void
    {
        $route = \Route::getRoutes()->getByName('esbtp.examens.resolve-scope-classes');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
    }

    public function test_ecues_by_parcours_has_permission(): void
    {
        $route = \Route::getRoutes()->getByName('esbtp.examens.ecues-by-parcours');
        $mw = $route->gatherMiddleware();
        $this->assertTrue(collect($mw)->contains(fn ($m) => str_contains((string) $m, 'lmd.examens.manage')));
    }

    public function test_resolve_scope_classes_has_permission_and_throttle(): void
    {
        $route = \Route::getRoutes()->getByName('esbtp.examens.resolve-scope-classes');
        $mw = $route->gatherMiddleware();
        $this->assertTrue(collect($mw)->contains(fn ($m) => str_contains((string) $m, 'lmd.examens.manage')));
        $this->assertTrue(collect($mw)->contains(fn ($m) => str_contains((string) $m, 'throttle')));
    }

    public function test_scope_types_constant_exposes_4_values(): void
    {
        $types = ESBTPExamenPlanifie::SCOPE_TYPES;
        $this->assertCount(4, $types);
        $this->assertContains('classe', $types);
        $this->assertContains('parcours', $types);
        $this->assertContains('mention', $types);
        $this->assertContains('domaine', $types);
    }

    public function test_model_has_classes_belongs_to_many_relation(): void
    {
        // Vérifie que la relation est définie (sans toucher la DB)
        $model = new ESBTPExamenPlanifie();
        $this->assertTrue(method_exists($model, 'classes'));
        $this->assertTrue(method_exists($model, 'classesAvecExclues'));
        $this->assertTrue(method_exists($model, 'uniteEnseignement'));
    }

    public function test_model_fillable_includes_scope_columns(): void
    {
        $model = new ESBTPExamenPlanifie();
        $fillable = $model->getFillable();
        $this->assertContains('scope_type', $fillable);
        $this->assertContains('scope_id', $fillable);
        $this->assertContains('unite_enseignement_id', $fillable);
        $this->assertContains('parcours_ids', $fillable);
    }

    public function test_modal_view_contains_cohorte_cascade(): void
    {
        $content = file_get_contents(resource_path('views/esbtp/examens/index.blade.php'));
        $this->assertStringContainsString('Cohorte académique (UEMOA)', $content, 'Section cohorte doit être présente');
        $this->assertStringContainsString('ECUE *', $content, 'Label ECUE (pas Matière) en mode cohorte');
        $this->assertStringContainsString("Mode cohorte UEMOA", $content);
        $this->assertStringContainsString("Mode classe unique", $content);
        $this->assertStringContainsString("scope-banner", $content, 'Banner scope auto-détecté doit être stylisé');
        $this->assertStringContainsString("inter-parcours", $content, 'Toggle inter-parcours doit être présent');
    }

    public function test_show_view_displays_classes_concernees(): void
    {
        $content = file_get_contents(resource_path('views/esbtp/examens/show.blade.php'));
        $this->assertStringContainsString('Classes concernées', $content);
        $this->assertStringContainsString('$allClasses', $content);
    }

    public function test_index_view_shows_classes_count_indicator(): void
    {
        $content = file_get_contents(resource_path('views/esbtp/examens/index.blade.php'));
        // Affichage "+N" pour classes additionnelles
        $this->assertStringContainsString('$classeNames', $content);
        $this->assertStringContainsString('+{{ $extras }}', $content);
    }

    public function test_service_has_scope_resolution_methods(): void
    {
        $service = app(\App\Services\ExamenSchedulingService::class);
        $this->assertTrue(method_exists($service, 'resolveScopedClasses'));
        $this->assertTrue(method_exists($service, 'autoDetectScope'));
        $this->assertTrue(method_exists($service, 'syncExamenClasses'));
        $this->assertTrue(method_exists($service, 'getEcuesGroupedByUe'));
        $this->assertTrue(method_exists($service, 'detectSharedParcours'));
    }

    public function test_resolve_scoped_classes_returns_empty_collection_for_invalid_scope(): void
    {
        $service = app(\App\Services\ExamenSchedulingService::class);
        $result = $service->resolveScopedClasses('invalid_scope', null);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_resolve_scoped_classes_returns_empty_when_scope_id_null(): void
    {
        $service = app(\App\Services\ExamenSchedulingService::class);
        // parcours sans id → vide
        $this->assertTrue($service->resolveScopedClasses('parcours', null)->isEmpty());
        $this->assertTrue($service->resolveScopedClasses('mention', null)->isEmpty());
        $this->assertTrue($service->resolveScopedClasses('domaine', null)->isEmpty());
        $this->assertTrue($service->resolveScopedClasses('classe', null)->isEmpty());
    }

    public function test_auto_detect_scope_falls_back_to_classe_when_no_classe(): void
    {
        $service = app(\App\Services\ExamenSchedulingService::class);
        $result = $service->autoDetectScope(null, null);
        $this->assertSame('classe', $result['scope_type']);
        $this->assertNull($result['scope_id']);
    }

    public function test_migration_file_exists(): void
    {
        $files = glob(database_path('migrations/*create_esbtp_examen_classes*'));
        $this->assertNotEmpty($files, 'Migration esbtp_examen_classes doit exister');
    }
}
