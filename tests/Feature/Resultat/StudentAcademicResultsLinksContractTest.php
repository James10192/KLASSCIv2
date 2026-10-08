<?php

namespace Tests\Feature\Resultat;

use Tests\TestCase;

class StudentAcademicResultsLinksContractTest extends TestCase
{
    public function test_each_academic_inscription_has_year_scoped_results_shortcut(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/etudiants/show.blade.php'));

        $this->assertStringNotContainsString('Résultats par inscription', $view);
        $this->assertStringContainsString('$lienResultatsInscription($acadRef)', $view);
        $this->assertStringContainsString('$lienResultatsInscription($autreInsc)', $view);
        $this->assertStringContainsString('@foreach($acadInscsPrec as $autreInsc)', $view);
        $this->assertStringContainsString("route('esbtp.resultats.etudiant'", $view);
        $this->assertStringContainsString("route('esbtp.lmd.resultats.etudiant'", $view);
        $this->assertStringContainsString('\'annee_universitaire_id\' => $insc->annee_universitaire_id', $view);
        $this->assertStringContainsString('\'classe_id\' => $insc->classe_id', $view);
        $this->assertStringContainsString("'include_all_statuses' => 1", $view);
    }
}
