<?php

namespace Tests\Unit\Domain\Patrimoine;

use App\Domain\Patrimoine\IndisponibiliteBien;
use App\Domain\Patrimoine\MissionRhEtLogistique;
use App\Domain\Patrimoine\TypeMaintenance;
use App\Domain\Stock\MouvementStock;
use PHPUnit\Framework\TestCase;

class PatrimoineTest extends TestCase
{
    public function test_fermer_un_ticket_n_efface_pas_les_couts(): void
    {
        $this->assertTrue(TypeMaintenance::fermerUnTicketNEffacePasLesCouts(true));
    }

    public function test_salle_ou_vehicule_indisponible_remonte_au_planning(): void
    {
        $this->assertTrue(IndisponibiliteBien::remonteAuPlanning('salle', true));
        $this->assertTrue(IndisponibiliteBien::remonteAuPlanning('vehicule', true));
        $this->assertFalse(IndisponibiliteBien::remonteAuPlanning('salle', false));
        $this->assertTrue(IndisponibiliteBien::salleEnMaintenanceRemonte('maintenance'));
    }

    public function test_mission_rh_et_reservation_restent_deux_objets(): void
    {
        $this->assertTrue(MissionRhEtLogistique::memeDeplacement(true, true));
        $this->assertTrue(MissionRhEtLogistique::fusionInterdite());
        $this->assertSame(MouvementStock::SORTIE_SERVICE, MissionRhEtLogistique::piecesConsommeesTypeStock());
    }
}
