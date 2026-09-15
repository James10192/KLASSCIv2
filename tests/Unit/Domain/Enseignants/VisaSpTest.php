<?php

namespace Tests\Unit\Domain\Enseignants;

use App\Domain\Enseignants\VisaSp;
use PHPUnit\Framework\TestCase;

class VisaSpTest extends TestCase
{
    public function test_sans_visa_sp_on_ne_paie_pas(): void
    {
        $this->assertFalse(VisaSp::peutPayer(VisaSp::A_VISER, true));
        $this->assertFalse(VisaSp::peutPayer(VisaSp::REFUSE, true));
        $this->assertFalse(VisaSp::peutPayer(VisaSp::VISE, false));
        $this->assertTrue(VisaSp::peutPayer(VisaSp::VISE, true));
    }
}
