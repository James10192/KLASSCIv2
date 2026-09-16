<?php

namespace Tests\Unit\Domain\Scolarite;

use App\Domain\Scolarite\ReclamationNotes;
use PHPUnit\Framework\TestCase;

class ReclamationNotesTest extends TestCase
{
    public function test_un_bulletin_publie_se_corrige_par_une_nouvelle_version(): void
    {
        $this->assertFalse(ReclamationNotes::peutCorrigerUnBulletinPublie(true, false));
        $this->assertTrue(ReclamationNotes::peutCorrigerUnBulletinPublie(true, true));
        $this->assertTrue(ReclamationNotes::peutCorrigerUnBulletinPublie(false, false));
    }

    public function test_un_impaye_ne_bloque_pas_les_notes(): void
    {
        $this->assertTrue(ReclamationNotes::impayeNeBloquePasLesNotes());
    }
}
