<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

class AcademicDocumentsNavigationTest extends TestCase
{
    private function source(string $path): string
    {
        $content = file_get_contents(dirname(__DIR__, 3).'/'.$path);
        $this->assertNotFalse($content, $path);

        return $content;
    }

    public function test_student_and_enrollment_share_the_contextual_academic_documents_module(): void
    {
        $student = $this->source('resources/views/esbtp/etudiants/show.blade.php');
        $enrollment = $this->source('resources/views/esbtp/inscriptions/show.blade.php');
        $module = $this->source('resources/views/esbtp/partials/fiche-notes-contenu.blade.php');

        $this->assertStringContainsString('esbtp.partials.fiche-notes-module', $student);
        $this->assertStringContainsString('esbtp.partials.fiche-notes-module', $enrollment);
        $this->assertStringContainsString('data-fng-lmd-releve', $module);
        $this->assertStringContainsString("esbtp.bts.releves.preview", $module);
        $this->assertStringContainsString("esbtp.lmd.jurys.index", $module);
    }

    public function test_lmd_has_its_own_notes_screen_with_class_pv_and_student_fiche_links(): void
    {
        $btsNotesController = $this->source('app/Http/Controllers/ESBTPNoteController.php');
        $lmdNotes = $this->source('resources/views/esbtp/lmd/notes/index.blade.php');
        $lmdClasses = $this->source('resources/views/esbtp/lmd/notes/partials/_classes.blade.php');

        $this->assertStringContainsString("orWhere('systeme_academique', '!=', 'LMD')", $btsNotesController);
        $this->assertStringContainsString('Fiche · Relevé', $lmdNotes);
        $this->assertStringContainsString('students.view', $lmdNotes);
        $this->assertStringContainsString('esbtp.lmd.jurys.index', $lmdClasses);
        $this->assertStringContainsString('lmd.jury.view', $lmdClasses);
    }

    public function test_bts_transcript_is_permissioned_scoped_and_not_a_national_exam_record(): void
    {
        $routes = $this->source('routes/web.php');
        $controller = $this->source('app/Http/Controllers/ESBTP/BtsStudentTranscriptController.php');
        $pdf = $this->source('resources/views/pdf/bts-releve-interne.blade.php');

        $this->assertStringContainsString("'permission:students.view'", $routes);
        $this->assertStringContainsString("'permission:bulletins.view'", $routes);
        $this->assertStringContainsString("where('annee_universitaire_id', \$annee->id)", $controller);
        $this->assertStringContainsString("where('etudiant_id', \$etudiant->id)", $controller);
        $this->assertStringContainsString("'systeme_academique', 'BTS'", $controller);
        $this->assertStringContainsString('$bulletin->is_published', $controller);
        $this->assertStringContainsString('ne remplace pas le relevé', $pdf);
    }

    public function test_lmd_emission_still_uses_the_existing_status_and_permissions(): void
    {
        $module = $this->source('resources/views/esbtp/partials/fiche-notes-contenu.blade.php');
        $script = $this->source('resources/views/esbtp/partials/fiche-notes-module.blade.php');
        $this->assertStringContainsString("esbtp.lmd.releves.status", $module);
        $this->assertStringContainsString("esbtp.lmd.releves.issue", $module);
        $this->assertStringContainsString("can('lmd.releve.issue')", $module);
        $this->assertStringContainsString('data.readiness?.ok', $script);
        $this->assertStringContainsString("send(btn.dataset.issueUrl,'POST',form)", $script);
    }
}
