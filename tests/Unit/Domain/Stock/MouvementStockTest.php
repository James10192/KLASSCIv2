<?php

namespace Tests\Unit\Domain\Stock;

use App\Domain\Stock\MouvementStock;
use PHPUnit\Framework\TestCase;

class MouvementStockTest extends TestCase
{
    public function test_une_facture_ne_remue_pas_un_stock_deja_receptionne(): void
    {
        $this->assertFalse(MouvementStock::doitMouvoir(MouvementStock::FACTURE, true));
        $this->assertTrue(MouvementStock::doitMouvoir(MouvementStock::RECEPTION, false));
    }

    public function test_une_sortie_vers_un_service_n_est_pas_une_cession(): void
    {
        $this->assertTrue(MouvementStock::sortieServiceNEstPasCession(MouvementStock::SORTIE_SERVICE));
        $this->assertFalse(MouvementStock::sortieServiceNEstPasCession(MouvementStock::CESSION));
    }
}
