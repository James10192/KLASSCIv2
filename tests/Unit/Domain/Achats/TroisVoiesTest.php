<?php

namespace Tests\Unit\Domain\Achats;

use App\Domain\Achats\TroisVoies;
use PHPUnit\Framework\TestCase;

class TroisVoiesTest extends TestCase
{
    public function test_une_reception_partielle_ne_facture_que_le_recu(): void
    {
        $this->assertSame(3.0, TroisVoies::quantiteFacturable(10, 3, 0));
        $this->assertSame(7.0, TroisVoies::reliquatCommande(10, 3));
    }

    public function test_le_reliquat_reste_visible_apres_une_premiere_facture(): void
    {
        $this->assertSame(2.0, TroisVoies::quantiteFacturable(10, 5, 3));
    }

    public function test_une_facture_ne_remue_pas_le_stock_deja_receptionne(): void
    {
        $this->assertFalse(TroisVoies::receptionNeDoublePasLeStock(true, true));
        $this->assertTrue(TroisVoies::receptionNeDoublePasLeStock(true, false));
    }

    public function test_une_proforma_n_est_pas_une_facture(): void
    {
        $this->assertFalse(TroisVoies::estFactureDefinitive('proforma'));
        $this->assertTrue(TroisVoies::estFactureDefinitive('facture'));
    }
}
