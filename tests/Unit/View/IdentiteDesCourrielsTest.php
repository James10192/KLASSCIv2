<?php

namespace Tests\Unit\View;

use App\View\Composers\IdentiteDesCourriels;
use PHPUnit\Framework\TestCase;

/**
 * Le seuil qui décide de la garde contre le mode sombre de Gmail : le double
 * fondu ne restitue que le blanc, donc il ne se pose que sur un texte blanc ou
 * presque, et jamais sur une valeur illisible.
 */
class IdentiteDesCourrielsTest extends TestCase
{
    public function test_le_blanc_et_le_quasi_blanc_portent_la_garde(): void
    {
        $this->assertTrue(IdentiteDesCourriels::texteQuasiBlanc('#ffffff'));
        $this->assertTrue(IdentiteDesCourriels::texteQuasiBlanc('#F8FAFC'));
    }

    public function test_un_texte_sombre_ou_colore_n_en_porte_pas(): void
    {
        $this->assertFalse(IdentiteDesCourriels::texteQuasiBlanc('#111827'));
        $this->assertFalse(IdentiteDesCourriels::texteQuasiBlanc('#f59e0b'));
        $this->assertFalse(IdentiteDesCourriels::texteQuasiBlanc('#0453cb'));
    }

    public function test_une_valeur_mal_formee_n_en_porte_pas(): void
    {
        $this->assertFalse(IdentiteDesCourriels::texteQuasiBlanc('blanc'));
        $this->assertFalse(IdentiteDesCourriels::texteQuasiBlanc(''));
    }
}
