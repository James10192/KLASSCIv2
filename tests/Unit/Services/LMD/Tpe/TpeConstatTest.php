<?php

namespace Tests\Unit\Services\LMD\Tpe;

use App\Enums\TypeSeance;
use App\Services\LMD\Tpe\TpeConstat;
use PHPUnit\Framework\TestCase;

class TpeConstatTest extends TestCase
{
    public function test_une_heure_tpe_etudiante_n_est_pas_payable(): void
    {
        $this->assertTrue(TpeConstat::nEstPasUneHeureEnseignantePayable());
        $this->assertFalse(TypeSeance::TPE->isVolumeTracked());
    }

    public function test_les_heures_retenues_sont_la_duree_constatee(): void
    {
        $this->assertSame(2.0, TpeConstat::heuresRetenues('08:00', '10:00'));
        $this->assertSame(1.5, TpeConstat::heuresRetenues('14:00', '15:30'));
        $this->assertSame(0.0, TpeConstat::heuresRetenues('', '10:00'));
    }

    public function test_un_tpe_qui_chevauche_un_cm_est_detecte(): void
    {
        $this->assertTrue(TpeConstat::seChevauchent('08:00', '10:00', '09:00', '11:00'));
        $this->assertFalse(TpeConstat::seChevauchent('08:00', '10:00', '10:00', '12:00'));
    }
}
