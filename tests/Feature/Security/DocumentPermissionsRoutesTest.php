<?php

namespace Tests\Feature\Security;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Un lien masqué dans une card n'est jamais une mesure de sécurité :
 * chaque URL directe, y compris PDF, preview, CSV et AJAX, doit être gardée.
 */
class DocumentPermissionsRoutesTest extends TestCase
{
    public function test_each_class_document_endpoint_has_its_own_permission(): void
    {
        $this->assertRouteGuards([
            'esbtp.classes.index' => 'classes.view',
            'esbtp.classes.show' => 'classes.view',
            'esbtp.classes.etudiants' => 'classes.students.view|bulletins.generate|lmd.bulletins.generate',
            'esbtp.classes.student-table-html' => 'classes.students.view',
            'esbtp.classes.liste-appel' => 'classes.liste_appel.view',
            'esbtp.classes.liste-appel.pdf' => 'classes.liste_appel.export',
            'esbtp.classes.liste-complete' => 'classes.liste_complete.view',
            'esbtp.classes.liste-complete.pdf' => 'classes.liste_complete.export',
            'esbtp.classes.liste-complete.excel' => 'classes.liste_complete.export',
            'esbtp.classes.export.excel' => 'classes.export',
            'esbtp.classes.export.csv' => 'classes.export',
            'esbtp.classes.export.pdf' => 'classes.export',
        ]);
    }

    public function test_grade_document_endpoints_have_separate_pdf_and_excel_permissions(): void
    {
        $this->assertRouteGuards([
            'esbtp.notes.export-excel' => 'notes.export_excel',
            'esbtp.notes.saisie-rapide.pdf' => 'notes.feuille_vierge.export',
            'esbtp.notes.saisie-rapide.pdf-preview' => 'notes.feuille_vierge.export',
            'esbtp.notes.saisie-rapide-blank.pdf' => 'notes.feuille_vierge.export',
            'esbtp.notes.saisie-rapide-blank.pdf-preview' => 'notes.feuille_vierge.export',
            'esbtp.evaluations.pdf' => 'evaluations.notes.export',
            'esbtp.evaluations.pdf-preview' => 'evaluations.notes.export',
            'evaluations.pdf' => 'evaluations.notes.export',
            'esbtp.bts.releves.preview' => 'bts.releve.view',
            'esbtp.bts.releves.download' => 'bts.releve.export',
        ]);
    }

    public function test_permission_only_read_access_does_not_inherit_admin_identity(): void
    {
        $this->assertRouteGuards([
            'esbtp.notes.index' => 'notes.view',
            'esbtp.notes.evaluations.by-class-matiere' => 'notes.view',
            'esbtp.notes.classes.students' => 'notes.view',
            'esbtp.lmd.notes.index' => 'lmd.notes.view',
            'esbtp.lmd.notes.classe-data' => 'lmd.notes.view',
            'esbtp.lmd.releves.preview' => 'lmd.releve.view',
            'esbtp.lmd.releves.download' => 'lmd.releve.view',
        ]);

        foreach (['esbtp.classes.index', 'esbtp.notes.index', 'esbtp.lmd.notes.index', 'esbtp.lmd.releves.preview'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            foreach ($route->gatherMiddleware() as $middleware) {
                $this->assertFalse(
                    str_starts_with($middleware, 'permission:admin.access|identity.'),
                    $name.' ne doit pas refuser un accès individuel valide via une garde de rôle générique.'
                );
            }
        }
    }

    /** @param array<string, string> $expected */
    private function assertRouteGuards(array $expected): void
    {
        foreach ($expected as $name => $permission) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertInstanceOf(RoutingRoute::class, $route, 'Route absente : '.$name);
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth', $middleware, 'Authentification manquante : '.$name);
            $this->assertContains('permission:'.$permission, $middleware, 'Permission manquante : '.$name);
        }
    }
}
