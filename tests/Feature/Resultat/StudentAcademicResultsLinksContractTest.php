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
        $this->assertStringContainsString("'inscription_id' => \$insc->id", $view);
        $this->assertStringContainsString("'periode' => \$periode", $view);
        $this->assertStringContainsString("'semestre' => \$semestre", $view);
        $this->assertStringContainsString('id="inscription-{{ $acadRef->id }}"', $view);
        $this->assertStringContainsString('id="inscription-{{ $autreInsc->id }}"', $view);
    }

    public function test_results_pages_have_permission_guarded_return_and_period_history(): void
    {
        $bts = file_get_contents(resource_path('views/esbtp/resultats/etudiant.blade.php'));
        $lmd = file_get_contents(resource_path('views/esbtp/lmd/resultats/etudiant.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPLMDResultatController.php'));

        foreach ([$bts, $lmd] as $view) {
            $this->assertStringContainsString("@can('students.view')", $view);
            $this->assertStringContainsString("route('esbtp.etudiants.show'", $view);
            $this->assertStringContainsString('Fiche étudiant', $view);
            $this->assertStringContainsString('popstate', $view);
        }
        $this->assertStringContainsString("'inscription_id' => ['nullable', 'integer'", $controller);
        $this->assertStringContainsString('semestresAutorises->contains($semestreSelectionne)', $controller);
        $this->assertStringContainsString('data-lmd-sem=', $lmd);
        $this->assertStringContainsString('history.pushState', $lmd);
    }
}
