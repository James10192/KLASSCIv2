<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/**
 * Tests de non-régression pour l'enrichissement des liens entités liées
 * dans le journal d'audit (/esbtp/audit).
 *
 * Couvre :
 * - Route esbtp.audit.related-links existe et est nommée
 * - Throttle middleware appliqué (anti-DoS)
 * - Permission security.audit.view requise
 * - Composant Blade audit-links compile sans erreur
 *
 * Note : ne touche pas la DB (TestCase pur), check routing + Blade.
 */
class AuditEntityLinksTest extends TestCase
{
    public function test_related_links_route_exists_and_is_named(): void
    {
        $route = \Route::getRoutes()->getByName('esbtp.audit.related-links');
        $this->assertNotNull($route, "La route 'esbtp.audit.related-links' doit exister");
        $this->assertContains('GET', $route->methods());
        $this->assertStringContainsString('/related-links', $route->uri());
    }

    public function test_related_links_route_has_throttle(): void
    {
        $route = \Route::getRoutes()->getByName('esbtp.audit.related-links');
        $this->assertNotNull($route);

        $middleware = $route->gatherMiddleware();
        $hasThrottle = collect($middleware)->contains(
            fn ($m) => str_contains((string) $m, 'throttle')
        );
        $this->assertTrue($hasThrottle, 'related-links route doit avoir un throttle middleware');
    }

    public function test_related_links_requires_auth(): void
    {
        // Sans authentification → redirection login (302), pas 200
        $response = $this->get('/esbtp/audit/1/related-links');
        $this->assertContains($response->status(), [302, 401, 403, 419]);
    }

    public function test_audit_links_component_view_exists(): void
    {
        $this->assertTrue(
            view()->exists('components.audit-links'),
            'Le composant Blade components.audit-links doit exister'
        );
    }

    public function test_audit_links_component_compiles_with_empty_links(): void
    {
        // Rend le composant avec links=[] — ne doit pas lever d'exception
        $html = view('components.audit-links', ['links' => [], 'title' => 'Test', 'compact' => false])->render();
        $this->assertIsString($html);
    }

    public function test_audit_links_component_renders_link_card(): void
    {
        $links = [[
            'key' => 'etudiant',
            'label' => 'Étudiant',
            'value' => 'Doe John',
            'sublabel' => 'DEMO001',
            'route' => 'https://example.com/student',
            'icon' => 'fa-user-graduate',
            'emphasis' => 'primary',
        ]];

        $html = view('components.audit-links', ['links' => $links, 'title' => 'Test', 'compact' => false])->render();
        $this->assertStringContainsString('Doe John', $html);
        $this->assertStringContainsString('DEMO001', $html);
        $this->assertStringContainsString('Étudiant', $html);
        $this->assertStringContainsString('al-item--primary', $html);
        $this->assertStringContainsString('fa-user-graduate', $html);
        $this->assertStringContainsString('href="https://example.com/student"', $html);
    }

    public function test_audit_links_compact_mode_omits_header(): void
    {
        $links = [[
            'key' => 'etudiant',
            'label' => 'Étudiant',
            'value' => 'Doe',
            'sublabel' => null,
            'route' => null,
            'icon' => 'fa-user',
            'emphasis' => 'normal',
        ]];

        $compactHtml = view('components.audit-links', ['links' => $links, 'title' => 'X', 'compact' => true])->render();
        $fullHtml = view('components.audit-links', ['links' => $links, 'title' => 'X', 'compact' => false])->render();

        // Le mode compact n'affiche pas le header avec le titre
        $this->assertStringNotContainsString('al-header', $compactHtml);
        $this->assertStringContainsString('al-header', $fullHtml);
        // Mais la card item est dans les deux
        $this->assertStringContainsString('al-item', $compactHtml);
    }

    public function test_audit_entity_resolver_is_instantiable(): void
    {
        $resolver = app(\App\Services\Audit\AuditEntityResolver::class);
        $this->assertInstanceOf(\App\Services\Audit\AuditEntityResolver::class, $resolver);
    }

    public function test_audit_show_route_exists_and_has_permission(): void
    {
        $route = \Route::getRoutes()->getByName('esbtp.audit.show');
        $this->assertNotNull($route, "La route esbtp.audit.show doit exister");

        $middleware = $route->gatherMiddleware();
        $hasAudit = collect($middleware)->contains(
            fn ($m) => str_contains((string) $m, 'security.audit.view') || str_contains((string) $m, 'permission')
        );
        $this->assertTrue($hasAudit, 'show route doit être gated par permission');
    }
}
