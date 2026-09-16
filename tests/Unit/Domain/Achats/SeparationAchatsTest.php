<?php

namespace Tests\Unit\Domain\Achats;

use App\Domain\Achats\PaiementIndetermine;
use App\Domain\Achats\SeparationAchats;
use PHPUnit\Framework\TestCase;

class SeparationAchatsTest extends TestCase
{
    public function test_le_saisisseur_ne_vise_pas_son_paiement(): void
    {
        $this->assertFalse(SeparationAchats::peutViserPaiement(4, 4));
        $this->assertTrue(SeparationAchats::peutViserPaiement(4, 9));
    }

    public function test_un_iban_change_apres_visa_invalide_le_visa(): void
    {
        $this->assertTrue(SeparationAchats::changementIbanApresVisa(true, 'CI93A', 'BJ93B'));
        $this->assertFalse(SeparationAchats::changementIbanApresVisa(true, 'CI93A', 'CI93A'));
        $this->assertFalse(SeparationAchats::changementIbanApresVisa(false, 'CI93A', 'BJ93B'));
    }

    public function test_un_paiement_inconnu_ne_se_relance_pas(): void
    {
        $this->assertFalse(PaiementIndetermine::peutRelancer('inconnu'));
        $this->assertFalse(PaiementIndetermine::peutRelancer('en_cours'));
        $this->assertTrue(PaiementIndetermine::peutRelancer('echoue'));
        $this->assertTrue(PaiementIndetermine::peutRelancer('confirme'));
    }
}
