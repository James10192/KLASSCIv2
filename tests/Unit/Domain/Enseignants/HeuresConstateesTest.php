<?php

namespace Tests\Unit\Domain\Enseignants;

use App\Domain\Enseignants\HeuresConstatees;
use App\Domain\Enseignants\TitulaireOuRemplacant;
use PHPUnit\Framework\TestCase;

class HeuresConstateesTest extends TestCase
{
    public function test_sans_constat_on_retient_le_planifie(): void
    {
        $this->assertSame(2.0, HeuresConstatees::dureeHeures('08:00', '10:00'));
    }

    public function test_un_cours_ecourte_retient_la_duree_constatee(): void
    {
        $this->assertSame(1.5, HeuresConstatees::dureeHeures('08:00', '10:00', '08:00', '09:30'));
    }

    public function test_le_remplacant_est_paye_pas_le_titulaire(): void
    {
        $this->assertSame(7, TitulaireOuRemplacant::paye(3, 7));
        $this->assertSame(3, TitulaireOuRemplacant::paye(3, null));
    }
}
