<?php

namespace Tests\Unit\Caisse;

use App\Enums\CashSessionStatus;
use App\Enums\ModePaiement;
use Tests\TestCase;

class CashSessionRulesTest extends TestCase
{
    public function test_only_especes_go_in_the_drawer(): void
    {
        $this->assertTrue(ModePaiement::ESPECES->isDrawer());
        $this->assertFalse(ModePaiement::WAVE->isDrawer());
        $this->assertFalse(ModePaiement::ORANGE_MONEY->isDrawer());
        $this->assertFalse(ModePaiement::VIREMENT->isDrawer());
        $this->assertTrue(ModePaiement::fromLegacy('Espèces')->isDrawer());
    }

    public function test_closed_and_auto_closed_lock_the_till(): void
    {
        $this->assertFalse(CashSessionStatus::OPEN->isLocked());
        $this->assertTrue(CashSessionStatus::CLOSED->isLocked());
        $this->assertTrue(CashSessionStatus::AUTO_CLOSED->isLocked());
    }
}
