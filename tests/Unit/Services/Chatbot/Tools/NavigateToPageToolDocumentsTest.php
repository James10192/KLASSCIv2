<?php

namespace Tests\Unit\Services\Chatbot\Tools;

use App\Services\Chatbot\Tools\NavigateToPageTool;
use Tests\TestCase;

class NavigateToPageToolDocumentsTest extends TestCase
{
    public function test_it_opens_the_official_certificate_preview_with_the_student_id(): void
    {
        $result = (new NavigateToPageTool())->execute([
            'page' => 'etudiants.certificat.preview',
            'id' => 42,
        ], null);

        $this->assertStringEndsWith('/esbtp/etudiants/42/certificat-preview', $result['url']);
        $this->assertStringContainsString('Aperçu PDF', $result['page_guide']);
    }

    public function test_it_opens_the_official_attestation_preview_with_the_student_id(): void
    {
        $result = (new NavigateToPageTool())->execute([
            'page' => 'etudiants.attestation.preview',
            'id' => 42,
        ], null);

        $this->assertStringEndsWith('/esbtp/etudiants/42/attestation-frequentation-preview', $result['url']);
        $this->assertStringContainsString('Imprimer', $result['page_guide']);
    }

    public function test_official_document_pages_require_a_student_id(): void
    {
        $result = (new NavigateToPageTool())->execute([
            'page' => 'etudiants.certificat.preview',
        ], null);

        $this->assertNull($result['url']);
        $this->assertStringContainsString('nécessite un ID', $result['error']);
    }
}
