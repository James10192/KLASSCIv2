<?php

namespace Tests\Unit\Inscription;

use App\Models\ESBTPInscription;
use Tests\TestCase;

class AffectationStatusLabelTest extends TestCase
{
    public function test_les_trois_statuts_ont_un_libelle_lisible(): void
    {
        $this->assertSame('Affecté', $this->inscription('affecté')->affectationStatusLabel());
        $this->assertSame('Réaffecté', $this->inscription('réaffecté')->affectationStatusLabel());
        $this->assertSame('Non affecté', $this->inscription('non_affecté')->affectationStatusLabel());
    }

    private function inscription(string $statut): ESBTPInscription
    {
        $inscription = new ESBTPInscription;
        $inscription->affectation_status = $statut;

        return $inscription;
    }
}
