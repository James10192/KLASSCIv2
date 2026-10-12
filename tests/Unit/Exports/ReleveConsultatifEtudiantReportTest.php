<?php

namespace Tests\Unit\Exports;

use App\Domain\Exports\ReleveConsultatifEtudiantReport;
use PHPUnit\Framework\TestCase;

class ReleveConsultatifEtudiantReportTest extends TestCase
{
    public function test_cache_keys_do_not_mix_student_documents(): void
    {
        $screen = ['systeme' => 'BTS', 'semestres' => [['code' => 'annuel', 'moyenne' => 13.5, 'matieres' => []]]];
        $year = (object) ['id' => 9, 'name' => '2026-2027'];

        $a = new ReleveConsultatifEtudiantReport(['etudiant' => (object) ['id' => 1, 'matricule' => 'A'], 'annee' => $year, 'ecran' => $screen]);
        $b = new ReleveConsultatifEtudiantReport(['etudiant' => (object) ['id' => 2, 'matricule' => 'B'], 'annee' => $year, 'ecran' => $screen]);

        $this->assertNotSame($a->cacheKey(), $b->cacheKey());
        $this->assertSame('esbtp.etudiants.releve-consultatif-pdf', $a->pdfView());
    }

    public function test_cache_changes_when_grade_changes(): void
    {
        $student = (object) ['id' => 1, 'matricule' => 'A'];
        $year = (object) ['id' => 9, 'name' => '2026-2027'];
        $a = new ReleveConsultatifEtudiantReport(['etudiant' => $student, 'annee' => $year, 'ecran' => ['semestres' => [['moyenne' => 10]]]]);
        $b = new ReleveConsultatifEtudiantReport(['etudiant' => $student, 'annee' => $year, 'ecran' => ['semestres' => [['moyenne' => 12]]]]);

        $this->assertNotSame($a->cacheKey(), $b->cacheKey());
    }
}
