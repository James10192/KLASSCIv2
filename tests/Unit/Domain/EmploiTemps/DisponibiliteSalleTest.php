<?php

namespace Tests\Unit\Domain\EmploiTemps;

use App\Domain\EmploiTemps\DisponibiliteSalle;
use PHPUnit\Framework\TestCase;

class DisponibiliteSalleTest extends TestCase
{
    public function test_une_salle_en_maintenance_remonte_au_planning(): void
    {
        $this->assertTrue(DisponibiliteSalle::estFermee('maintenance'));
        $this->assertTrue(DisponibiliteSalle::conflitAvecPlanning(true, true));
        $this->assertFalse(DisponibiliteSalle::estFermee('available'));
        $this->assertFalse(DisponibiliteSalle::conflitAvecPlanning(true, false));
    }
}
