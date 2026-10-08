<?php

namespace Tests\Feature\Resultat;

use Tests\TestCase;

class StudentAcademicResultsLinksContractTest extends TestCase
{
    public function test_each_academic_inscription_has_year_scoped_results_shortcut(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/etudiants/show.blade.php'));

        $this->assertStringContainsString('Résultats par inscription', $view);
        $this->assertStringContainsString('@foreach($acadInscs as $inscriptionResultats)', $view);
        $this->assertStringContainsString("route('esbtp.resultats.etudiant'", $view);
        $this->assertStringContainsString("route('esbtp.lmd.resultats.etudiant'", $view);
        $this->assertStringContainsString('\'annee_universitaire_id\' => $inscriptionResultats->annee_universitaire_id', $view);
        $this->assertStringContainsString('\'classe_id\' => $inscriptionResultats->classe_id', $view);
        $this->assertStringContainsString("'include_all_statuses' => 1", $view);
    }
}
