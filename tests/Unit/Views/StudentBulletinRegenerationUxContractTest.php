<?php

namespace Tests\Unit\Views;

use Tests\TestCase;

class StudentBulletinRegenerationUxContractTest extends TestCase
{
    public function test_la_modale_de_regeneration_est_centree_et_adaptee_au_mobile(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/etudiants/show.blade.php'));

        $this->assertStringContainsString('#insc-bulletin-dialog {', $view);
        $this->assertStringContainsString('position: fixed;', $view);
        $this->assertStringContainsString('inset: 0;', $view);
        $this->assertStringContainsString('margin: auto;', $view);
        $this->assertStringContainsString('max-height: calc(100dvh - 32px);', $view);
        $this->assertStringContainsString('#insc-bulletin-dialog::backdrop', $view);
    }

    public function test_le_recalcul_actualise_le_bilan_en_ajax_sans_rechargement(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/etudiants/show.blade.php'));
        $start = strpos($view, "const dialog = document.getElementById('insc-bulletin-dialog');");
        $this->assertNotFalse($start);
        $script = substr($view, $start, strpos($view, '</script>', $start) - $start);

        $this->assertStringContainsString('async function refreshAcademicBilan()', $script);
        $this->assertStringContainsString("cache: 'no-store'", $script);
        $this->assertStringContainsString('new DOMParser().parseFromString', $script);
        $this->assertStringContainsString("parsed.getElementById('tab-academique')", $script);
        $this->assertStringContainsString("nextAcademic.childNodes", $script);
        $this->assertStringContainsString("notesNouvelles.replaceWith(notesActuelles)", $script);
        $this->assertStringContainsString("liveAcademic.querySelectorAll('.collapse.show[id]')", $script);
        $this->assertStringContainsString("lmd-sem-tab.active", $script);
        $this->assertStringContainsString("liveAverage.replaceWith(nextAverage)", $script);
        $this->assertStringContainsString("completed = true;", $script);
        $this->assertStringNotContainsString('window.location.reload()', $script);
    }
}
