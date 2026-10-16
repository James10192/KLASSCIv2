<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

class AcademicPlanningUxContractTest extends TestCase
{
    private function source(string $path): string
    {
        $contents = file_get_contents(__DIR__.'/../../../resources/views/'.$path);
        $this->assertNotFalse($contents);

        return $contents;
    }

    public function test_bulletin_period_navigation_always_loads_its_classifications(): void
    {
        $page = $this->source('esbtp/bulletins/partials/_config-matieres-script.blade.php');

        $this->assertStringContainsString('this.switchPeriode(p, true)', $page);
        $this->assertStringContainsString('const sequence = ++this.periodRequestSequence', $page);
        $this->assertStringContainsString('if (sequence !== this.periodRequestSequence) return;', $page);
        $this->assertStringContainsString('this.applyPeriodeFromAjax(targetPeriode, fromPopstate)', $page);
        $this->assertStringContainsString("currentUrl.searchParams.set('periode', this.currentPeriode)", $page);
        $this->assertStringContainsString('x-text="otherPeriodeLabel"', $this->source('esbtp/bulletins/config-matieres.blade.php'));
    }

    public function test_copy_preview_never_confirms_an_outdated_period_or_mode(): void
    {
        $page = $this->source('esbtp/bulletins/partials/_config-matieres-script.blade.php');

        $this->assertStringContainsString('const sequence = ++this.previewRequestSequence', $page);
        $this->assertStringContainsString('sequence === this.previewRequestSequence && this.copyOpen', $page);
        $this->assertStringContainsString('source === this.currentPeriode && mode === this.copyMode', $page);
        $this->assertStringContainsString('copySaving || previewLoading || !preview', $this->source('esbtp/bulletins/config-matieres.blade.php'));
    }

    public function test_bts_maquette_filters_discard_old_ajax_responses(): void
    {
        $page = $this->source('esbtp/matieres/partials/_classification-script.blade.php');

        $this->assertStringContainsString('const sequence = ++this.comboRequestSequence', $page);
        $this->assertStringContainsString('sequence !== this.comboRequestSequence', $page);
        $this->assertStringContainsString('filiereId !== this.filiereId || niveauId !== this.niveauId', $page);
        $this->assertStringContainsString('this.comboRequestSequence++;', $page);
    }

    public function test_lmd_planning_rejects_stale_filters_and_surfaces_retry(): void
    {
        $script = $this->source('esbtp/lmd/planning/_scripts.blade.php');
        $page = $this->source('esbtp/lmd/planning/index.blade.php');

        $this->assertStringContainsString('const sequence = ++this.partialSequence', $script);
        $this->assertStringContainsString('this.partialController.abort()', $script);
        $this->assertStringContainsString('if (sequence !== this.partialSequence) return false;', $script);
        $this->assertStringContainsString('if (loaded) this.syncUrl()', $script);
        $this->assertStringContainsString('this.error =', $script);
        $this->assertStringContainsString('x-show="!error"', $page);
        $this->assertStringContainsString('retryFilters()', $page);
    }

    public function test_timetable_filters_prevent_stale_swap_and_unsafe_conflict_markup(): void
    {
        $page = $this->source('esbtp/emploi-temps/index.blade.php');
        $dialog = $this->source('esbtp/emploi-temps/partials/_quick-generate-script.blade.php');

        $this->assertStringContainsString('const sequence = ++refreshSequence', $page);
        $this->assertStringContainsString('refreshController.abort()', $page);
        $this->assertStringContainsString('if (sequence !== refreshSequence) return;', $page);
        $this->assertStringContainsString("$('#emploiTempsTable').DataTable().destroy();", $page);
        $this->assertStringContainsString('if (sequence === refreshSequence) hideOverlay()', $page);
        $this->assertStringContainsString("esbtp.emploi-temps.partials._quick-generate-script", $page);
        foreach (['group.class_name', 'item.matiere', 'item.enseignant', 'item.jour', 'item.heure_debut', 'item.heure_fin'] as $field) {
            $this->assertStringContainsString('${escapeConflictHtml('.$field.')}', $dialog);
        }
    }
}
