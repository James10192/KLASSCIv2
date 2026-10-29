<?php

namespace Tests\Unit\Services\LMD;

use App\Services\LMD\Tpe\TpePlanification;
use PHPUnit\Framework\TestCase;

class TpePlanificationTest extends TestCase
{
    public function test_le_defaut_n_est_pas_planifiable(): void
    {
        $this->assertFalse(TpePlanification::modeIsPlanifiable(''));
        $this->assertFalse(TpePlanification::modeIsPlanifiable(TpePlanification::MODE_NON_PLANIFIABLE));
    }

    public function test_une_seance_encadree_est_planifiable(): void
    {
        $this->assertTrue(TpePlanification::modeIsPlanifiable(TpePlanification::MODE_SEANCE_ENCADREE));
        $this->assertTrue(TpePlanification::modeIsPlanifiable(TpePlanification::MODE_HYBRIDE));
    }
}
