<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

class AcademicFicheUxContractTest extends TestCase
{
    private function view(string $name): string
    {
        return file_get_contents(__DIR__.'/../../../resources/views/'.$name);
    }

    public function test_student_and_inscription_keep_their_primary_content_before_notes(): void
    {
        $student = $this->view('esbtp/etudiants/show.blade.php');
        $inscription = $this->view('esbtp/inscriptions/show.blade.php');

        $studentJourney = strpos($student, "'esbtp.etudiants.partials.academic-journey-diagram', [\n        'academicJourney'");
        $studentNotes = strpos($student, "'url' => route('esbtp.fiche-notes.etudiant'");
        $inscriptionInfo = strpos($inscription, 'Informations académiques');
        $inscriptionNotes = strpos($inscription, "'url' => route('esbtp.fiche-notes.inscription'");

        $this->assertNotFalse($studentJourney);
        $this->assertNotFalse($studentNotes);
        $this->assertLessThan($studentNotes, $studentJourney);
        $this->assertNotFalse($inscriptionInfo);
        $this->assertNotFalse($inscriptionNotes);
        $this->assertLessThan($inscriptionNotes, $inscriptionInfo);
        $this->assertStringContainsString("@can('notes.view')", $student);
        $this->assertStringContainsString("@can('notes.view')", $inscription);
    }

    public function test_notes_are_lazy_loaded_and_reuse_existing_ajax_actions(): void
    {
        $module = $this->view('esbtp/partials/fiche-notes-module.blade.php');
        $content = $this->view('esbtp/partials/fiche-notes-contenu.blade.php');

        $this->assertStringContainsString('<details class="fng-disclosure">', $module);
        $this->assertStringContainsString("disclosure.addEventListener('toggle'", $module);
        $this->assertStringContainsString('if(disclosure.open)activate()', $module);
        $this->assertStringContainsString("if(e.key==='Escape' && !menu.hidden){e.preventDefault();e.stopPropagation();close();trigger.focus();}", $module);
        $this->assertStringContainsString('dlg.showModal()', $module);
        $this->assertStringContainsString("dlg.setAttribute('aria-labelledby',titleId)", $module);
        $this->assertStringContainsString("if(opener?.isConnected)opener.focus({preventScroll:true})", $module);
        $this->assertStringContainsString("if(documentLink.isConnected)documentLink.focus({preventScroll:true})", $module);
        $this->assertStringContainsString('dlg.querySelector(\'form\').addEventListener(\'submit\'', $module);
        $this->assertStringContainsString('data-save="{{ route(\'esbtp.notes.save-ajax\') }}"', $content);
        $this->assertStringNotContainsString('class="fng-head"', $content);
        $this->assertStringContainsString('data-fng-document', $content);
        $this->assertStringContainsString('data-fng-new-eval', $content);
    }

    public function test_coefficient_modal_reinitializes_after_ajax_swap_without_duplicate_events(): void
    {
        $results = $this->view('esbtp/resultats/etudiant.blade.php');
        $coeffModal = $this->view('esbtp/resultats/partials/_student-coefficients-script.blade.php');
        $helpers = $this->view('esbtp/resultats/partials/_ajax-ui-helpers.blade.php');

        $this->assertStringContainsString('window.scmInitCoefficientsModal = function ()', $coeffModal);
        $this->assertStringContainsString("form.dataset.scmBound === '1'", $coeffModal);
        $this->assertStringContainsString("form.dataset.scmBound = '1'", $coeffModal);
        $this->assertStringContainsString('window.srReinitResultsAfterSave()', $coeffModal);
        $this->assertStringContainsString("window.srReinitResultsAfterSave = function ()", $helpers);
        $this->assertStringContainsString('initStudentResults(true)', $helpers);
        $this->assertStringContainsString('window.scmInitCoefficientsModal()', $helpers);
        $this->assertStringContainsString('data-scm-init-script', $coeffModal);
        $this->assertStringContainsString("script[data-scm-init-script]", $helpers);
        $this->assertStringContainsString("initCoefficientsAfterSwap();", $results);
        $this->assertStringContainsString("esbtp.resultats.partials._ajax-ui-helpers", $results);
        $this->assertStringContainsString("esbtp.resultats.partials._student-coefficients-script", $this->view('esbtp/resultats/partials/student-coefficients-modal.blade.php'));
        $this->assertStringNotContainsString('window.location.reload()', $coeffModal);
    }

    public function test_results_keep_full_width_subject_tables_but_compact_secondary_details(): void
    {
        $results = $this->view('esbtp/resultats/etudiant.blade.php');
        $css = file_get_contents(__DIR__.'/../../../public/css/student-results.css');

        $this->assertStringContainsString('sr-results-summary-row', $results);
        $this->assertStringContainsString("components.student-results.subjects-table", $results);
        $this->assertStringContainsString("components.student-results.evaluations-detail", $results);
        $this->assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr))', $css);
        $this->assertStringContainsString('.sr-results-summary-row .sr-student-card', $css);
    }
}
