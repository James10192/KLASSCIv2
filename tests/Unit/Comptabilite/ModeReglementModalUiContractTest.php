<?php

namespace Tests\Unit\Comptabilite;

use PHPUnit\Framework\TestCase;

/**
 * Contrat du rendu du mode de règlement : mêmes droits sur index/show,
 * modales hors tableau et jamais supprimées pendant une transition Bootstrap.
 * Le test visuel réel sur presentation reste obligatoire avant propagation.
 */
class ModeReglementModalUiContractTest extends TestCase
{
    private function contenu(string $chemin): string
    {
        return (string) file_get_contents(dirname(__DIR__, 3).'/'.$chemin);
    }

    public function test_les_modales_de_la_liste_ne_sont_plus_enfants_du_tbody(): void
    {
        $table = $this->contenu('resources/views/esbtp/paiements/partials/table.blade.php');
        $ligne = $this->contenu('resources/views/esbtp/paiements/partials/ligne-paiement.blade.php');
        $fragments = $this->contenu('resources/views/esbtp/paiements/partials/modals-paiement.blade.php');

        $this->assertStringContainsString("'renderPaiementModals' => false", $table);
        $this->assertStringContainsString("partials.modals-paiement", $table);
        $this->assertGreaterThan(
            strpos($table, '</table>'),
            strpos($table, 'class="pi-modals-to-mount"'),
            'Le host de rendu des modales doit suivre la fermeture du tableau.'
        );
        $this->assertStringContainsString('renderPaiementModals ?? true', $ligne);
        $this->assertStringContainsString("partials.modals-paiement", $ligne);
        foreach (['partials.mode-reglement-modal', 'partials.avoir-modal', 'partials.supprimer-modal'] as $partial) {
            $this->assertStringContainsString($partial, $fragments);
        }
        $this->assertStringContainsString('rejetModal{{ $paiement->id }}', $fragments);
    }

    public function test_le_refresh_automatique_ne_detruit_jamais_un_modal_ouvert(): void
    {
        $index = $this->contenu('resources/views/esbtp/paiements/index.blade.php');

        foreach ([
            'id="pi-payment-modals-root"',
            "document.addEventListener('show.bs.modal'",
            "document.addEventListener('hide.bs.modal'",
            "document.addEventListener('hidden.bs.modal'",
            'modalesEnTransition.has(existing)',
            'existing !== el',
            'window.bootstrap?.Modal?.getInstance(el)?.dispose()',

            'if (paiementModalEnCours())',
            'refreshDiffere = true',
            'piMountModals(nouveauTableau.content, { replaceAll: true })',
            'piMountModals(tmpl.content)',
            'piMountModals(template.content, { updateExisting: true',
            'window.Alpine?.initTree?.(el)',
        ] as $garde) {
            $this->assertStringContainsString($garde, $index);
        }

        $this->assertStringNotContainsString(
            "document.getElementById('paiements-table-container').innerHTML = data.table", $index
        );
    }

    public function test_show_et_index_reutilisent_le_formulaire_et_sa_permission(): void
    {
        $show = $this->contenu('resources/views/esbtp/paiements/show.blade.php');
        $ligne = $this->contenu('resources/views/esbtp/paiements/partials/ligne-paiement.blade.php');
        $modal = $this->contenu('resources/views/esbtp/paiements/partials/mode-reglement-modal.blade.php');
        $index = $this->contenu('resources/views/esbtp/paiements/index.blade.php');
        $route = $this->contenu('routes/web.php');
        $controller = $this->contenu('app/Http/Controllers/Comptabilite/ModeReglementController.php');

        foreach ([$show, $ligne, $modal] as $vue) {
            $this->assertStringContainsString("@can('paiements.correct_mode')", $vue);
            $this->assertStringContainsString('! $paiement->reconciliation_locked_at', $vue);
        }
        $this->assertStringContainsString('partials.mode-reglement-modal', $show);
        $this->assertStringContainsString('modeReglementModal{{ $paiement->id }}', $show);
        $this->assertStringContainsString('name="motif"', $modal);
        $this->assertStringContainsString('minlength="10"', $modal);
        $this->assertStringContainsString("@method('PATCH')", $modal);
        $this->assertStringContainsString("permission:paiements.correct_mode", $route);
        $this->assertStringContainsString("abort_unless(\$request->user()?->can('paiements.correct_mode'), 403)", $controller);

        foreach ([$show, $index] as $vue) {
            $this->assertStringContainsString("css/paiements/mode-reglement.css", $vue);
            $this->assertStringContainsString("js/paiements/mode-reglement.js", $vue);
        }
        $this->assertStringNotContainsString('<script>', $modal);
        $sharedScript = $this->contenu('public/js/paiements/mode-reglement.js');
        $this->assertStringContainsString('window.avoirForm', $sharedScript);
        $this->assertStringContainsString('window.modeReglementForm', $sharedScript);
    }
}
