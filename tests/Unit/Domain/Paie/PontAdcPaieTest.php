<?php

namespace Tests\Unit\Domain\Paie;

use App\Domain\Paie\PontAdcPaie;
use PHPUnit\Framework\TestCase;

class PontAdcPaieTest extends TestCase
{
    public function test_un_timeout_ne_passe_pas_a_synchronise(): void
    {
        $this->assertFalse(PontAdcPaie::estSynchronise('accepted', true));
        $this->assertFalse(PontAdcPaie::estSynchronise('rejected', false));
        $this->assertTrue(PontAdcPaie::estSynchronise('accepted', false));
    }

    public function test_une_relance_n_ecrase_pas_un_ack_accepte(): void
    {
        $this->assertFalse(PontAdcPaie::peutRelancer('hours-1', true));
        $this->assertTrue(PontAdcPaie::peutRelancer('hours-1', false));
        $this->assertFalse(PontAdcPaie::peutRelancer('', false));
    }

    public function test_un_csv_exporte_n_est_pas_une_integration(): void
    {
        $this->assertTrue(PontAdcPaie::unExportCsvNEstPasUneIntegration());
    }
}
