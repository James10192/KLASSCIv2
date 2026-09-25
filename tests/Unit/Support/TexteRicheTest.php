<?php

namespace Tests\Unit\Support;

use App\Casts\TexteRicheCast;
use App\Support\TexteRiche;
use PHPUnit\Framework\TestCase;

class TexteRicheTest extends TestCase
{
    public function test_le_texte_brut_est_rendu_tel_quel(): void
    {
        $this->assertSame("Ligne 1\nnote < 10 et 12 > 8", TexteRiche::nettoyer("Ligne 1\nnote < 10 et 12 > 8"));
        $this->assertFalse(TexteRiche::contientDuHtml('Je <3 KLASSCI'));
    }

    public function test_la_mise_en_forme_de_l_editeur_est_gardee(): void
    {
        $html = '<h3>Objectifs</h3><p>Un <strong>cycle</strong> de <em>3 ans</em>, <u>souligné</u></p><ul><li>BTS</li></ul><blockquote>citation</blockquote>';

        $this->assertSame($html, TexteRiche::nettoyer($html));
    }

    public function test_scripts_evenements_et_styles_disparaissent(): void
    {
        $sortie = TexteRiche::nettoyer('<p onclick="x()" style="color:red">texte</p><script>alert(1)</script><img src=x onerror=alert(1)><iframe src="https://x"></iframe><svg onload=alert(1)></svg>');

        $this->assertSame('<p>texte</p>', $sortie);
    }

    public function test_seuls_les_liens_surs_gardent_leur_adresse(): void
    {
        $sortie = TexteRiche::nettoyer('<a href="javascript:alert(1)">a</a><a href="data:text/html,x">b</a><a href="//evil.test">c</a><a href="/esbtp/cycles">d</a><a href="https://esbtp.ci">e</a>');

        $this->assertSame(
            '<a>a</a><a>b</a><a>c</a><a href="/esbtp/cycles">d</a><a href="https://esbtp.ci" target="_blank" rel="noopener noreferrer">e</a>',
            $sortie
        );
    }

    public function test_une_balise_inconnue_laisse_son_texte(): void
    {
        $this->assertSame('gros', TexteRiche::nettoyer('<div><span style="font-size:40px">gros</span></div>'));
    }

    public function test_un_editeur_vide_donne_une_description_vide(): void
    {
        $this->assertNull(TexteRiche::nettoyer('<p><br></p>'));
    }

    public function test_les_accents_survivent(): void
    {
        $html = '<p>Élève — année 2025-2026 : « débouchés »</p>';

        $this->assertSame($html, TexteRiche::nettoyer($html));
    }

    public function test_un_ancien_texte_brut_s_affiche_echappe_avec_ses_retours_a_la_ligne(): void
    {
        $this->assertSame("a<br />\nb &lt; c", (string) TexteRiche::afficher("a\nb < c"));
        $this->assertNull(TexteRiche::afficher('   '));
    }

    public function test_un_html_stocke_avant_le_cast_est_renettoye_a_l_affichage(): void
    {
        $this->assertSame('<p>ok</p>', (string) TexteRiche::afficher('<p>ok</p><script>alert(1)</script>'));
    }

    public function test_un_ancien_texte_brut_arrive_dans_l_editeur_en_paragraphes(): void
    {
        $this->assertSame("<p>Para 1<br>\nsuite</p><p>Para 2 &amp; co</p>", TexteRiche::pourEditeur("Para 1\nsuite\n\nPara 2 & co"));
    }

    public function test_le_cast_nettoie_a_l_enregistrement(): void
    {
        $cast = new TexteRicheCast();

        $this->assertSame('<p>x</p>', $cast->set(null, 'description', '<p>x</p><script>y</script>', []));
        $this->assertNull($cast->set(null, 'description', null, []));
    }
}
