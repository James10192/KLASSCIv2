<?php

namespace Tests\Unit\API;

use Tests\TestCase;

class LMSDataControllerSchemaTest extends TestCase
{
    public function test_emploi_temps_uses_current_seance_schema(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/API/LMSDataController.php'));
        $this->assertNotFalse($source);

        $method = $this->extractMethod($source, 'emploiTemps');

        $this->assertStringContainsString("whereBetween('date_seance'", $method);
        $this->assertStringContainsString("orderBy('date_seance'", $method);
        $this->assertStringContainsString("where('teacher_id'", $method);
        $this->assertStringNotContainsString("whereBetween('date_cours'", $method);
        $this->assertStringNotContainsString("orderBy('date_cours'", $method);
        $this->assertStringNotContainsString("where('enseignant_id'", $method);
        $this->assertStringNotContainsString("'salle',", $method);
    }

    private function extractMethod(string $source, string $method): string
    {
        $needle = "public function {$method}";
        $start = strpos($source, $needle);

        $this->assertNotFalse($start, "Method {$method} not found");

        $brace = strpos($source, '{', $start);
        $this->assertNotFalse($brace, "Method {$method} body not found");

        $depth = 0;
        $length = strlen($source);

        for ($i = $brace; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($source, $start, $i - $start + 1);
                }
            }
        }

        $this->fail("Method {$method} body is not balanced");
    }
}
