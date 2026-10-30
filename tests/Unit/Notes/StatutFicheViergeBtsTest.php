<?php

namespace Tests\Unit\Notes;

use App\Domain\Notes\StatutFicheViergeBts;
use PHPUnit\Framework\TestCase;

class StatutFicheViergeBtsTest extends TestCase
{
    public function test_statuts_valides_et_orthographes_alternatives(): void
    {
        $this->assertSame('AFF', StatutFicheViergeBts::abreger('affecté'));
        $this->assertSame('AFF', StatutFicheViergeBts::abreger('affecte'));
        $this->assertSame('NAFF', StatutFicheViergeBts::abreger('non_affecté'));
        $this->assertSame('NAFF', StatutFicheViergeBts::abreger('non-affecte'));
        $this->assertSame('RAFF', StatutFicheViergeBts::abreger('réaffecté'));
    }

    public function test_absence_ou_statut_inconnu_ne_devient_jamais_aff(): void
    {
        $this->assertSame('—', StatutFicheViergeBts::abreger(null));
        $this->assertSame('—', StatutFicheViergeBts::abreger('  '));
        $this->assertSame('en attente', StatutFicheViergeBts::abreger('en attente'));
    }
}
