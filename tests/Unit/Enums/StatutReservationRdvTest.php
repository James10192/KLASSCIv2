<?php

namespace Tests\Unit\Enums;

use App\Enums\StatutReservationRdv;
use Tests\TestCase;

class StatutReservationRdvTest extends TestCase
{
    public function test_seules_les_lignes_actives_occupent_le_creneau(): void
    {
        $this->assertTrue(StatutReservationRdv::Confirmee->occupeLeCreneau());
        $this->assertTrue(StatutReservationRdv::Honoree->occupeLeCreneau());
        $this->assertTrue(StatutReservationRdv::Manquee->occupeLeCreneau());
        $this->assertFalse(StatutReservationRdv::Annulee->occupeLeCreneau());
        $this->assertFalse(StatutReservationRdv::Liberee->occupeLeCreneau());
    }
}
