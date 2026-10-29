<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ValeurConnue;
use PHPUnit\Framework\TestCase;

/** « N/A », le vide et le nul ne sont pas des valeurs à écrire dans une phrase. */
class ValeurConnueTest extends TestCase
{
    public function test_une_valeur_inconnue_rend_null(): void
    {
        foreach ([null, '', '   ', 'N/A', ' N/A ', []] as $valeur) {
            $this->assertNull(ValeurConnue::ou($valeur), var_export($valeur, true));
        }
    }

    public function test_une_valeur_connue_rend_son_texte(): void
    {
        $this->assertSame('2A BTS Bâtiment', ValeurConnue::ou(' 2A BTS Bâtiment '));
        $this->assertSame('0', ValeurConnue::ou(0), 'Zéro est une valeur, pas une absence.');
        $this->assertSame('n/a minuscule', ValeurConnue::ou('n/a minuscule'));
    }
}
