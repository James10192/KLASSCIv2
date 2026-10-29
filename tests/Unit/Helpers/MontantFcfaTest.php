<?php

namespace Tests\Unit\Helpers;

use App\Helpers\MontantFcfa;
use Illuminate\Support\HtmlString;
use PHPUnit\Framework\TestCase;

/**
 * Un montant de courriel ne se coupe jamais : milliers séparés par une espace
 * fine insécable, unité attachée par une espace insécable, le tout en nowrap.
 */
class MontantFcfaTest extends TestCase
{
    public function test_les_milliers_sont_separes_par_une_espace_fine_insecable(): void
    {
        $this->assertSame("150\u{202F}000", MontantFcfa::nombre(150000));
        $this->assertSame("1\u{202F}250\u{202F}000", MontantFcfa::nombre('1250000.00'));
        $this->assertSame('950', MontantFcfa::nombre(950));
    }

    public function test_aucune_espace_secable_ni_decimale(): void
    {
        $nombre = MontantFcfa::nombre(216625000.4);

        $this->assertStringNotContainsString(' ', $nombre, 'Une espace ordinaire laisserait le montant se couper.');
        $this->assertStringNotContainsString("\u{00A0}", $nombre);
        $this->assertSame("216\u{202F}625\u{202F}000", $nombre);
    }

    public function test_zero_et_valeur_vide_s_ecrivent_zero(): void
    {
        $this->assertSame('0', MontantFcfa::nombre(0));
        $this->assertSame('0', MontantFcfa::nombre(null));
    }

    public function test_le_html_attache_l_unite_et_interdit_la_coupure(): void
    {
        $html = MontantFcfa::html(150000);

        $this->assertInstanceOf(HtmlString::class, $html);
        $this->assertSame("<span style=\"white-space:nowrap\">150\u{202F}000&nbsp;FCFA</span>", (string) $html);
    }
}
