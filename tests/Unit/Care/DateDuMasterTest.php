<?php

namespace Tests\Unit\Care;

use App\Domain\Support\Services\DateDuMaster;
use Tests\TestCase;

/** Les heures du Master, montrees a l'heure de l'instance. */
class DateDuMasterTest extends TestCase
{
    /** @test */
    public function une_instance_a_utc_plus_un_voit_son_heure_pas_celle_du_master(): void
    {
        config()->set('app.timezone', 'Africa/Porto-Novo');

        $this->assertSame('22/09 11:30', DateDuMaster::afficher('2026-09-22T10:30:00+00:00', 'd/m H:i'));
    }

    /** @test */
    public function une_date_absente_reste_absente(): void
    {
        $this->assertSame('—', DateDuMaster::afficher(null));
        $this->assertSame('—', DateDuMaster::afficher(''));
        $this->assertSame('—', DateDuMaster::afficher('pas une date'));
    }
}
