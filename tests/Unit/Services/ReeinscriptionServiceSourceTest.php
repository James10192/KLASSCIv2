<?php

namespace Tests\Unit\Services;

use Tests\TestCase;

class ReeinscriptionServiceSourceTest extends TestCase
{
    public function test_missing_previous_year_returns_empty_result_instead_of_throwing(): void
    {
        $source = file_get_contents(app_path('Services/ReeinscriptionService.php'));
        $this->assertNotFalse($source);

        $this->assertStringContainsString('return $this->emptyDecisionResult();', $source);
        $this->assertStringContainsString('emptyDecisionResult', $source);
        $this->assertStringNotContainsString(
            "throw new \\Exception(\"Aucune année universitaire précédente trouvée pour l'analyse de réinscription\")",
            $source
        );
    }
}
