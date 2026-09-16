<?php

namespace Tests\Unit\Domain\Tresorerie;

use App\Domain\Achats\PaiementIndetermine;
use App\Domain\Tresorerie\DedupReleve;
use App\Domain\Tresorerie\PropositionRapprochement;
use App\Enums\ModePaiement;
use PHPUnit\Framework\TestCase;

class RapprochementBanqueTest extends TestCase
{
    public function test_un_releve_rejoue_est_un_doublon(): void
    {
        $this->assertTrue(DedupReleve::estDoublon('BNK-1', true));
        $this->assertFalse(DedupReleve::estDoublon('BNK-1', false));
        $this->assertFalse(DedupReleve::estDoublon('', true));
    }

    public function test_montant_et_date_seuls_ne_fusionnent_pas(): void
    {
        $this->assertTrue(PropositionRapprochement::peutProposer(false, true, true));
        $this->assertFalse(PropositionRapprochement::peutFusionnerAutomatiquement(false, true, true));
        $this->assertTrue(PropositionRapprochement::peutFusionnerAutomatiquement(true, true, true));
    }

    public function test_un_paiement_inconnu_ne_se_relance_pas(): void
    {
        $this->assertFalse(PaiementIndetermine::peutRelancer('inconnu'));
    }

    public function test_celtiis_est_un_mode_mobile_deja_la(): void
    {
        $this->assertTrue(ModePaiement::CELTIIS_CASH->estMobile());
        $this->assertFalse(ModePaiement::CELTIIS_CASH->isDrawer());
    }
}
