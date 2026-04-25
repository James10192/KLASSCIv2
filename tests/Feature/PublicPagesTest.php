<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public function test_changelog_page_renders_with_content(): void
    {
        $response = $this->get('/changelog');

        $response->assertStatus(200);
        $response->assertSee('Avril 2026', false);
        $response->assertSee('Tout ce que nous avons livré', false);
        // Footer must link to working routes (not href="#")
        $response->assertDontSee('href="#" >Documentation', false);
    }

    public function test_docs_index_lists_articles(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200);
        $response->assertSee('Documentation', false);
        $response->assertSee('Bienvenue sur KLASSCI');
        // The 3 available articles must appear with clickable links
        $response->assertSee(route('docs.show', 'getting-started'), false);
        $response->assertSee(route('docs.show', 'superadmin/onboarding'), false);
        $response->assertSee(route('docs.show', 'secretaire/inscriptions'), false);
    }

    public function test_docs_getting_started_renders(): void
    {
        $response = $this->get(route('docs.show', 'getting-started'));

        $response->assertStatus(200);
        $response->assertSee('Bienvenue sur KLASSCI');
        $response->assertSee('Vocabulaire essentiel', false);
    }

    public function test_docs_superadmin_onboarding_renders(): void
    {
        $response = $this->get(route('docs.show', 'superadmin/onboarding'));

        $response->assertStatus(200);
        $response->assertSee('installation initiale', false);
        $response->assertSee('année universitaire', false);
    }

    public function test_docs_secretaire_inscriptions_renders(): void
    {
        $response = $this->get(route('docs.show', 'secretaire/inscriptions'));

        $response->assertStatus(200);
        $response->assertSee('inscriptions', false);
        $response->assertSee('workflow_step', false);
    }

    public function test_unavailable_doc_throws_not_found(): void
    {
        // The 404 view is a separate concern (it crashes due to a pre-existing
        // `hasAnyPermission()` call in the layout — unrelated to PR3).
        // Here we only assert the controller correctly throws NotFoundHttpException.
        $this->withoutExceptionHandling();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->get('/docs/enseignant/notes');
    }

    public function test_unknown_doc_throws_not_found(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->get('/docs/this-does-not-exist');
    }

    public function test_api_reference_renders_with_endpoints(): void
    {
        $response = $this->get('/api-reference');

        $response->assertStatus(200);
        $response->assertSee('API LMS', false);
        // Real LMS endpoints listed
        $response->assertSee('/api/lms/auth/login', false);
        $response->assertSee('/api/lms/structure', false);
        $response->assertSee('/api/lms/evaluations', false);
        // Internal CLI endpoints MUST NOT leak
        $response->assertDontSee('/api/cli/');
        // Beta banner is mandatory
        $response->assertSee('Beta', false);
    }

    public function test_robots_txt_file_contains_public_pages(): void
    {
        // Static files in public/ are served by the web server, not the
        // Laravel test client. We assert the on-disk content directly.
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertNotFalse($robots);
        $this->assertStringContainsString('Sitemap: https://klassci.com/sitemap.xml', $robots);
        $this->assertStringContainsString('Allow: /docs', $robots);
        $this->assertStringContainsString('Allow: /changelog', $robots);
        $this->assertStringContainsString('Allow: /api-reference', $robots);
    }

    public function test_sitemap_xml_file_contains_public_pages(): void
    {
        $sitemap = file_get_contents(public_path('sitemap.xml'));

        $this->assertNotFalse($sitemap);
        $this->assertStringContainsString('https://klassci.com/changelog', $sitemap);
        $this->assertStringContainsString('https://klassci.com/docs', $sitemap);
        $this->assertStringContainsString('https://klassci.com/api-reference', $sitemap);
    }
}
