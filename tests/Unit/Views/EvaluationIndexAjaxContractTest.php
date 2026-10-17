<?php

namespace Tests\Unit\Views;

use Tests\TestCase;

/**
 * Régression : le re-rendu AJAX d'une ligne ne doit pas multiplier les PATCH
 * ni refaire un chargement complet après chaque élément d'un lot.
 */
class EvaluationIndexAjaxContractTest extends TestCase
{
    private function script(): string
    {
        $view = file_get_contents(resource_path('views/esbtp/evaluations/index.blade.php'));
        $start = strpos($view, 'function initializeEvaluations()');
        $this->assertNotFalse($start);

        return substr($view, $start, strpos($view, '</script>', $start) - $start);
    }

    public function test_les_actions_et_selections_sont_deleguees_une_seule_fois(): void
    {
        $script = $this->script();

        $this->assertStringContainsString("resultsContainer.addEventListener('click', (event) => {", $script);
        $this->assertStringContainsString("resultsContainer.addEventListener('change', (event) => {", $script);
        $this->assertStringContainsString('}, true);', $script); // capture avant stopPropagation des cellules
        $this->assertStringNotContainsString("checkbox.addEventListener('change', handleRowSelection)", $script);
        $this->assertStringNotContainsString("button.addEventListener('click', (event) => {", $script);
        $this->assertStringNotContainsString("perPageSelect.addEventListener('change'", $script);
        $this->assertStringContainsString('resultsContainer.dataset.evaluationsInitialized', $script);
    }

    public function test_double_clic_et_lot_utilisent_un_verrou_et_des_etats_cibles(): void
    {
        $script = $this->script();

        $this->assertStringContainsString('processingIds.has(id)', $script);
        $this->assertStringContainsString('processingIds.add(id)', $script);
        $this->assertStringContainsString('processingIds.delete(id)', $script);
        $this->assertStringContainsString('button.disabled = Boolean(isLoading)', $script);
        $this->assertStringContainsString('if (bulkProcessing || processingIds.size) return;', $script);
        $this->assertStringContainsString("payload: { is_published: true }", $script);
        $this->assertStringContainsString("payload: { is_published: false }", $script);
        $this->assertStringContainsString("payload: { notes_published: true }", $script);
    }

    public function test_un_lot_stoppe_en_erreur_et_actualise_une_seule_fois_sans_reload(): void
    {
        $script = $this->script();
        $start = strpos($script, 'async function runBulkAction(');
        $end = strpos($script, '/**', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $bulk = substr($script, $start, $end - $start);

        $this->assertStringContainsString('for (const id of filteredIds)', $bulk);
        $this->assertStringContainsString('refreshRow: false', $bulk);
        $this->assertStringContainsString('if (!outcome.ok)', $bulk);
        $this->assertStringContainsString('break; // 429', $bulk);
        $this->assertSame(1, substr_count($bulk, 'await submitFilterForm(false)'));
        $this->assertStringContainsString('Lot interrompu', $bulk);
        $this->assertStringNotContainsString('refreshEvaluationRow(', $bulk);
        $this->assertStringNotContainsString('fetchSummaryOnly(', $bulk);
        $this->assertStringNotContainsString('location.reload(', $script);
    }
}
