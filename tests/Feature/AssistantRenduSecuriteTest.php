<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Le texte du modèle n'est pas fiable : une injection peut arriver par une
 * donnée qu'un outil a lue. Le client du panneau doit donc rendre en texte tout
 * lien qui sort de l'application, et désarmer les blocs mermaid du texte libre.
 * Même règle que AfficherTableau::estLienInterne côté serveur.
 */
class AssistantRenduSecuriteTest extends TestCase
{
    private function script(): string
    {
        return file_get_contents(public_path('js/assistant.js'));
    }

    public function test_seuls_les_chemins_internes_deviennent_des_liens(): void
    {
        $js = $this->script();

        $this->assertStringContainsString(
            'var LIEN_INTERNE = /^\/(esbtp|dashboard|chatbot)([\/?#][A-Za-z0-9\/_\-?=&%.#]*)?$/;',
            $js
        );
        // Le crochet DOMPurify retire le href d'un lien refusé, versNoeuds le réduit à son texte.
        $this->assertStringContainsString("node.removeAttribute('href');", $js);
        $this->assertStringContainsString("querySelectorAll('a:not([href])')", $js);
        // Aucun lien ne s'ouvre vers l'extérieur.
        $this->assertStringNotContainsString("target = '_blank'", $js);
        $this->assertStringNotContainsString("setAttribute('target', '_blank')", $js);
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

    public function test_mermaid_reste_strict_et_perd_ses_directives_et_ses_clics(): void
    {
        $js = $this->script();

        $this->assertStringContainsString("securityLevel: 'strict'", $js);
        $this->assertStringContainsString('function sourceMermaidSure(source)', $js);
        $this->assertStringContainsString("ligne.indexOf('%%{') === -1", $js);
        $this->assertStringContainsString('/^\s*click\b/i.test(ligne)', $js);
        $this->assertStringContainsString('var sure = sourceMermaidSure(source);', $js);
    }
}
