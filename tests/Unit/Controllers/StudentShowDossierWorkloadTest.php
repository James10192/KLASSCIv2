<?php

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;

class StudentShowDossierWorkloadTest extends TestCase
{
    public function test_student_show_does_not_build_an_unused_historical_dossier(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 3).'/app/Http/Controllers/ESBTPStudentController.php');
        $studentView = file_get_contents(dirname(__DIR__, 3).'/resources/views/esbtp/etudiants/show.blade.php');

        $this->assertNotFalse($controller);
        $this->assertNotFalse($studentView);

        $start = strpos($controller, 'public function show(');
        $end = strpos($controller, 'public function edit(', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $show = substr($controller, $start, $end - $start);

        $this->assertStringNotContainsString('buildDossier(', $show);
        $this->assertStringNotContainsString('EtudiantDossierService', $show);
        $this->assertStringNotContainsString("'dossier'", $show);
        $this->assertStringNotContainsString('$dossier', $studentView);

        // Les donnees dont la fiche se sert restent fournies.
        $this->assertStringContainsString("'academicJourney'", $show);
        $this->assertStringContainsString("'situationFinanciere'", $show);
    }
}
