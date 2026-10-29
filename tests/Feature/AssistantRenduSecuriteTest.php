<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Garde-fous statiques du rendu de l'assistant. La preuve de comportement est
 * le harnais navigateur tests/js/assistant-securite.spec.mjs (voir
 * tests/js/README.md), qui charge les vrais scripts et rejoue les charges
 * malveillantes. Ce test-ci vérifie seulement que le montage n'a pas bougé :
 * les cinq scripts, dans l'ordre, et la règle des liens partagée avec le serveur.
 */
class AssistantRenduSecuriteTest extends TestCase
{
    private const SCRIPTS = ['noyau', 'markdown', 'rendus', 'vue', 'composant'];

    public function test_le_composant_charge_les_cinq_scripts_dans_l_ordre(): void
    {
        $vue = file_get_contents(resource_path('views/components/chatbot/assistant.blade.php'));

        $positions = array_map(fn ($nom) => strpos($vue, "js/assistant/{$nom}.js"), self::SCRIPTS);
        $this->assertNotContains(false, $positions);
        $trie = $positions;
        sort($trie);
        $this->assertSame($trie, $positions);
        $this->assertFileDoesNotExist(public_path('js/assistant.js'));

        foreach (self::SCRIPTS as $nom) {
            $this->assertLessThan(1000, count(file(public_path("js/assistant/{$nom}.js"))), $nom);
        }
    }

    public function test_la_regle_des_liens_est_celle_du_serveur(): void
    {
        $noyau = file_get_contents(public_path('js/assistant/noyau.js'));

        $this->assertStringContainsString(
            'var LIEN_INTERNE = /^\/(esbtp|dashboard|chatbot)([\/?#][A-Za-z0-9\/_\-?=&%.#]*)?$/;',
            $noyau
        );
        $this->assertStringContainsString("FORBID_ATTR: ['style', 'id']", $noyau);
        $this->assertStringContainsString('ALLOW_DATA_ATTR: false', $noyau);
        $this->assertStringContainsString("securityLevel: 'strict'", $noyau);
    }

    public function test_la_regle_des_liens_refuse_les_detournements_connus(): void
    {
        // Le motif JavaScript, rejoué en PHP. Le modificateur D est indispensable ici :
        // sans lui, le $ de PCRE accepte un retour à la ligne final, ce que le $ de
        // JavaScript (sans drapeau m) ne fait pas.
        $motif = '/^\/(esbtp|dashboard|chatbot)([\/?#][A-Za-z0-9\/_\-?=&%.#]*)?$/D';

        foreach (['/esbtp/etudiants/12', '/dashboard', '/chatbot?c=3', '/esbtp/paiements?status=en_attente#x'] as $bon) {
            $this->assertSame(1, preg_match($motif, $bon), $bon);
        }
        foreach (['https://evil.example', '//evil.example', '/\\evil.com', "/esbtp\t/x", "/esbtp/x\n", 'javascript:alert(1)', '/esbtpx', '/autre'] as $mauvais) {
            $this->assertSame(0, preg_match($motif, $mauvais), $mauvais);
        }
    }
}
