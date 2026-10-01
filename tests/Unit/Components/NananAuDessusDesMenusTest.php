<?php

namespace Tests\Unit\Components;

use PHPUnit\Framework\TestCase;

/**
 * L'entrée « Aide » vit dans la feuille du compte (mobile) et dans le menu du
 * profil (bureau). Nanan arrête le clic avant eux : s'il ne les referme pas
 * lui-même, la feuille reste ouverte et masque la fenêtre (octobre 2026,
 * seul le bandeau « Aide — Avec Nanan » dépassait au-dessus de la feuille).
 */
class NananAuDessusDesMenusTest extends TestCase
{
    private function source(string $chemin): string
    {
        return (string) file_get_contents(dirname(__DIR__, 3).'/'.$chemin);
    }

    private function zIndex(string $css, string $selecteur): int
    {
        $this->assertSame(1, preg_match('/'.preg_quote($selecteur, '/').'\s*\{[^}]*z-index:\s*(\d+)/', $css, $m), "z-index introuvable pour {$selecteur}");

        return (int) $m[1];
    }

    public function test_nanan_referme_la_feuille_et_le_menu_avant_de_s_ouvrir(): void
    {
        $nanan = $this->source('resources/views/components/support/nanan.blade.php');

        $this->assertStringContainsString("this.declencheur = this.fermerLeMenu(", $nanan);
        $this->assertStringContainsString("new CustomEvent('m-sheet:close')", $nanan);
        $this->assertStringContainsString("[data-bs-toggle=\"dropdown\"][aria-expanded=\"true\"]", $nanan);
    }

    public function test_la_fenetre_et_son_voile_passent_au_dessus_de_la_feuille(): void
    {
        $nanan = $this->source('resources/views/components/support/nanan.blade.php');
        $shell = $this->source('public/css/mobile-shell.css');

        $feuille = max($this->zIndex($shell, '.m-sheet-root'), $this->zIndex($shell, '.m-sheet'));

        $this->assertGreaterThan($feuille, $this->zIndex($nanan, '.nsp-voile'));
        $this->assertGreaterThan($this->zIndex($nanan, '.nsp-voile'), $this->zIndex($nanan, '.nsp-fenetre'));
    }
}
