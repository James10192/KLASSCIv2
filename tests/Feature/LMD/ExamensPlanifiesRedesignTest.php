<?php

namespace Tests\Feature\LMD;

use App\Enums\ExamenStatus;
use App\Enums\TypeExamen;
use Tests\TestCase;

/**
 * Tests Feature pour le redesign premium des examens LMD :
 *  - routes nommées (`options`, `store`, `update`, `kpis`)
 *  - middleware permission + throttle
 *  - vue `index` charge sans erreur (smoke)
 *  - enums Status/Type FR correctement câblés
 *
 * Pas de DB nécessaire — TestCase pur.
 */
class ExamensPlanifiesRedesignTest extends TestCase
{
    public function test_options_route_exists_and_named(): void
    {
        $route = \Route::getRoutes()->getByName('esbtp.examens.options');
        $this->assertNotNull($route, "Route esbtp.examens.options doit exister");
        $this->assertContains('GET', $route->methods());
    }

    public function test_options_route_has_permission_and_throttle(): void
    {
        $route = \Route::getRoutes()->getByName('esbtp.examens.options');
        $this->assertNotNull($route);
        $mw = $route->gatherMiddleware();
        $this->assertTrue(collect($mw)->contains(fn ($m) => str_contains((string) $m, 'lmd.examens.manage')));
        $this->assertTrue(collect($mw)->contains(fn ($m) => str_contains((string) $m, 'throttle')));
    }

    public function test_store_route_exists(): void
    {
        $route = \Route::getRoutes()->getByName('esbtp.examens.store');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
    }

    public function test_kpis_route_exists(): void
    {
        $route = \Route::getRoutes()->getByName('esbtp.examens.kpis');
        $this->assertNotNull($route);
        $this->assertContains('GET', $route->methods());
    }

    public function test_options_route_requires_auth(): void
    {
        $response = $this->get('/esbtp/examens/options');
        $this->assertContains($response->status(), [302, 401, 403, 419]);
    }

    /* ──────────────── Enum FR ──────────────── */

    public function test_examen_status_labels_in_french(): void
    {
        $this->assertSame('Brouillon', ExamenStatus::DRAFT->label());
        $this->assertSame('Planifié', ExamenStatus::PLANNED->label());
        $this->assertSame('En cours', ExamenStatus::IN_PROGRESS->label());
        $this->assertSame('Terminé', ExamenStatus::COMPLETED->label());
        $this->assertSame('Notes verrouillées', ExamenStatus::NOTES_LOCKED->label());
        $this->assertSame('Annulé', ExamenStatus::CANCELLED->label());
    }

    public function test_type_examen_labels_in_french(): void
    {
        $this->assertSame('Examen terminal', TypeExamen::EXAMEN->label());
        $this->assertSame('Partiel (mi-semestre)', TypeExamen::PARTIEL->label());
        $this->assertSame('Rattrapage (2ᵉ session)', TypeExamen::RATTRAPAGE->label());
        $this->assertSame('Soutenance', TypeExamen::SOUTENANCE->label());
    }

    public function test_examen_status_editable_excludes_notes_locked(): void
    {
        $editable = ExamenStatus::editable();
        $values = array_map(fn ($e) => $e->value, $editable);
        $this->assertNotContains('notes_locked', $values, 'notes_locked doit être exclu (verrouillage auto)');
        $this->assertContains('planned', $values);
        $this->assertContains('completed', $values);
    }

    public function test_examen_status_label_for_handles_unknown(): void
    {
        // Fallback pour rétrocompat avec données non-Enum (ucfirst + underscores → espaces)
        $this->assertSame('Brouillon', ExamenStatus::labelFor('draft'));
        $this->assertSame('Xx yy', ExamenStatus::labelFor('xx_yy'));
        $this->assertSame('', ExamenStatus::labelFor(null));
    }

    public function test_examen_status_select_options_returns_french_array(): void
    {
        $opts = ExamenStatus::selectOptions();
        $this->assertIsArray($opts);
        $this->assertArrayHasKey('draft', $opts);
        $this->assertSame('Brouillon', $opts['draft']);
        $this->assertSame('En cours', $opts['in_progress']);
    }

    /* ──────────────── Vue smoke ──────────────── */

    public function test_index_view_uses_au_select_premium(): void
    {
        // Vérifie que la vue index inclut le composant <x-au-select> (rule premium-selects)
        $path = resource_path('views/esbtp/examens/index.blade.php');
        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString('<x-au-select', $content, 'index.blade.php doit utiliser le composant premium');
        $this->assertStringNotContainsString('<select name="annee_universitaire_id"', $content, 'plus de <select> natif visible');
    }

    public function test_index_view_has_modal_create(): void
    {
        $content = file_get_contents(resource_path('views/esbtp/examens/index.blade.php'));
        $this->assertStringContainsString('exp-modal-backdrop', $content, 'Modal de création doit exister');
        $this->assertStringContainsString('openCreateModal', $content, 'Méthode openCreateModal doit être câblée');
        $this->assertStringContainsString('submitCreate', $content, 'AJAX submit no-reload');
    }

    public function test_create_view_removed(): void
    {
        // La création passe par le modal de l'index, plus de page dédiée
        $path = resource_path('views/esbtp/examens/create.blade.php');
        $this->assertFileDoesNotExist($path);
    }

    public function test_controller_uses_name_not_libelle_for_annee(): void
    {
        $path = app_path('Http/Controllers/ESBTPExamenPlanifieController.php');
        $content = file_get_contents($path);
        $this->assertStringNotContainsString("'libelle'", $content, 'libelle (mauvaise colonne) ne doit plus être utilisé');
        $this->assertStringContainsString("'name'", $content);
    }
}
