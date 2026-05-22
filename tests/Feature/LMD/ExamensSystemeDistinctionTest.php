<?php

namespace Tests\Feature\LMD;

use App\Models\ESBTPClasse;
use App\Models\ESBTPExamenPlanifie;
use Tests\TestCase;

/**
 * Tests Feature pour la distinction BTS/LMD sur /esbtp/examens (Plan B,
 * confidence 85%, validé par Marcel le 22 mai 2026).
 *
 * Pas de DB hit — accessor testé avec mocks, vues testées en file_get_contents.
 */
class ExamensSystemeDistinctionTest extends TestCase
{
    /* ════════════════ ACCESSOR getSystemeAttribute ════════════════ */

    public function test_accessor_returns_lmd_when_unite_enseignement_id_present(): void
    {
        $examen = new ESBTPExamenPlanifie();
        $examen->unite_enseignement_id = 42;
        $this->assertSame('LMD', $examen->systeme);
    }

    public function test_accessor_returns_lmd_when_classe_systeme_is_lmd(): void
    {
        $examen = new ESBTPExamenPlanifie();
        $examen->unite_enseignement_id = null;
        $classe = new ESBTPClasse();
        $classe->systeme_academique = 'LMD';
        $examen->setRelation('classe', $classe);
        $this->assertSame('LMD', $examen->systeme);
    }

    public function test_accessor_returns_lmd_when_classe_systeme_is_lowercase_lmd(): void
    {
        // Robuste à la casse (data lowercase legacy)
        $examen = new ESBTPExamenPlanifie();
        $examen->unite_enseignement_id = null;
        $classe = new ESBTPClasse();
        $classe->systeme_academique = 'lmd';
        $examen->setRelation('classe', $classe);
        $this->assertSame('LMD', $examen->systeme);
    }

    public function test_accessor_returns_bts_when_no_ecue_and_classe_is_bts(): void
    {
        $examen = new ESBTPExamenPlanifie();
        $examen->unite_enseignement_id = null;
        $classe = new ESBTPClasse();
        $classe->systeme_academique = 'BTS';
        $examen->setRelation('classe', $classe);
        $this->assertSame('BTS', $examen->systeme);
    }

    public function test_accessor_defaults_to_bts_when_no_classe_no_ecue(): void
    {
        $examen = new ESBTPExamenPlanifie();
        $examen->unite_enseignement_id = null;
        // Pas de relation classe chargée
        $this->assertSame('BTS', $examen->systeme);
    }

    public function test_accessor_priority_ecue_overrides_classe_bts(): void
    {
        // Cas edge : ECUE LMD assigné à une classe BTS (data inconsistente)
        // → l'accessor privilégie l'ECUE (heuristique #1)
        $examen = new ESBTPExamenPlanifie();
        $examen->unite_enseignement_id = 7;
        $classe = new ESBTPClasse();
        $classe->systeme_academique = 'BTS';
        $examen->setRelation('classe', $classe);
        $this->assertSame('LMD', $examen->systeme, 'ECUE prend la priorité même sur classe BTS');
    }

    /* ════════════════ COMPOSANT BLADE ════════════════ */

    public function test_systeme_chip_component_view_exists(): void
    {
        $this->assertTrue(view()->exists('components.systeme-chip'), 'Composant systeme-chip doit exister');
    }

    public function test_systeme_chip_renders_lmd(): void
    {
        $html = view('components.systeme-chip', ['systeme' => 'LMD'])->render();
        $this->assertStringContainsString('LMD', $html);
        $this->assertStringContainsString('sys-chip--lmd', $html);
        $this->assertStringContainsString('fa-graduation-cap', $html);
        $this->assertStringContainsString('aria-label', $html);
        $this->assertStringContainsString('Licence-Master-Doctorat UEMOA', $html);
    }

    public function test_systeme_chip_renders_bts(): void
    {
        $html = view('components.systeme-chip', ['systeme' => 'BTS'])->render();
        $this->assertStringContainsString('BTS', $html);
        $this->assertStringContainsString('sys-chip--bts', $html);
        $this->assertStringContainsString('fa-screwdriver-wrench', $html);
        $this->assertStringContainsString('Brevet de Technicien Supérieur', $html);
    }

    public function test_systeme_chip_renders_mixte_with_warning_style(): void
    {
        $html = view('components.systeme-chip', ['systeme' => 'MIXTE'])->render();
        $this->assertStringContainsString('MIXTE', $html);
        $this->assertStringContainsString('sys-chip--mixte', $html);
        $this->assertStringContainsString('Configuration invalide', $html);
    }

    public function test_systeme_chip_sizes(): void
    {
        $sm = view('components.systeme-chip', ['systeme' => 'LMD', 'size' => 'sm'])->render();
        $lg = view('components.systeme-chip', ['systeme' => 'LMD', 'size' => 'lg'])->render();
        $this->assertStringContainsString('sys-chip--sm', $sm);
        $this->assertStringContainsString('sys-chip--lg', $lg);
    }

    public function test_systeme_chip_can_hide_icon_or_text(): void
    {
        $noIcon = view('components.systeme-chip', ['systeme' => 'LMD', 'showIcon' => false])->render();
        $noText = view('components.systeme-chip', ['systeme' => 'LMD', 'showText' => false])->render();
        $this->assertStringNotContainsString('fa-graduation-cap', $noIcon);
        $this->assertStringNotContainsString('sys-chip-label', $noText);
    }

    /* ════════════════ MODÈLE hasConsistentSysteme() ════════════════ */

    public function test_has_consistent_systeme_returns_true_for_single_system(): void
    {
        $examen = new ESBTPExamenPlanifie();
        $c1 = new ESBTPClasse(); $c1->systeme_academique = 'LMD';
        $c2 = new ESBTPClasse(); $c2->systeme_academique = 'LMD';
        $examen->setRelation('classes', collect([$c1, $c2]));
        $this->assertTrue($examen->hasConsistentSysteme());
    }

    public function test_has_consistent_systeme_returns_false_for_mixed(): void
    {
        $examen = new ESBTPExamenPlanifie();
        $c1 = new ESBTPClasse(); $c1->systeme_academique = 'LMD';
        $c2 = new ESBTPClasse(); $c2->systeme_academique = 'BTS';
        $examen->setRelation('classes', collect([$c1, $c2]));
        $this->assertFalse($examen->hasConsistentSysteme());
    }

    public function test_has_consistent_systeme_robust_to_case(): void
    {
        $examen = new ESBTPExamenPlanifie();
        $c1 = new ESBTPClasse(); $c1->systeme_academique = 'LMD';
        $c2 = new ESBTPClasse(); $c2->systeme_academique = 'lmd';
        $examen->setRelation('classes', collect([$c1, $c2]));
        $this->assertTrue($examen->hasConsistentSysteme(), 'Casse normalisée → cohérent');
    }

    /* ════════════════ AUDIT classe_id ════════════════ */

    public function test_audit_include_contains_classe_id_and_matiere_id(): void
    {
        $examen = new ESBTPExamenPlanifie();
        $reflection = new \ReflectionClass($examen);
        $prop = $reflection->getProperty('auditInclude');
        $prop->setAccessible(true);
        $include = $prop->getValue($examen);
        $this->assertContains('classe_id', $include, 'classe_id doit être audité (changement de classe = changement de système)');
        $this->assertContains('matiere_id', $include);
    }

    /* ════════════════ VUE INDEX ════════════════ */

    public function test_index_view_includes_systeme_filter(): void
    {
        $content = file_get_contents(resource_path('views/esbtp/examens/index.blade.php'));
        $this->assertStringContainsString('name="systeme"', $content);
        $this->assertStringContainsString('hasMixedSystemes', $content);
        $this->assertStringContainsString('x-systeme-chip', $content);
    }

    public function test_index_view_calendar_has_legend_entries_for_systems(): void
    {
        $content = file_get_contents(resource_path('views/esbtp/examens/index.blade.php'));
        $this->assertStringContainsString('exp-event--sys-lmd', $content);
        $this->assertStringContainsString('exp-event--sys-bts', $content);
    }

    public function test_show_view_displays_systeme_badge(): void
    {
        $content = file_get_contents(resource_path('views/esbtp/examens/show.blade.php'));
        $this->assertStringContainsString('Système', $content);
        $this->assertStringContainsString('x-systeme-chip', $content);
    }
}
