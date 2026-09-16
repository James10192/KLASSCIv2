<?php

namespace Tests\Unit\Domain\Scolarite;

use App\Domain\Scolarite\FamilleEvaluation;
use App\Domain\Scolarite\MoyenneEcue;
use App\Domain\Scolarite\NatureDeNote;
use PHPUnit\Framework\TestCase;

class MoyenneEcueTest extends TestCase
{
    public function test_zero_absence_et_dispense_ne_sont_pas_la_meme_chose(): void
    {
        $this->assertSame(NatureDeNote::ZERO, NatureDeNote::classifier(0.0, false, false));
        $this->assertSame(NatureDeNote::ABSENCE, NatureDeNote::classifier(null, true, false));
        $this->assertSame(NatureDeNote::DISPENSE, NatureDeNote::classifier(12.0, false, true));
        $this->assertSame(NatureDeNote::MANQUANTE, NatureDeNote::classifier(null, false, false));
    }

    public function test_une_absence_n_entre_pas_comme_zero_si_le_reglage_l_interdit(): void
    {
        $this->assertNull(NatureDeNote::valeurPourMoyenne(NatureDeNote::ABSENCE, null, false));
        $this->assertSame(0.0, NatureDeNote::valeurPourMoyenne(NatureDeNote::ABSENCE, null, true));
        $this->assertSame(0.0, NatureDeNote::valeurPourMoyenne(NatureDeNote::ZERO, 0.0, false));
    }

    public function test_cc_et_examen_sont_ponderes(): void
    {
        $moyenne = MoyenneEcue::calculer([
            ['valeur' => 10.0, 'absent' => false, 'dispense' => false, 'famille' => FamilleEvaluation::CC, 'coefficient' => 1, 'bareme' => 20],
            ['valeur' => 20.0, 'absent' => false, 'dispense' => false, 'famille' => FamilleEvaluation::EXAMEN, 'coefficient' => 1, 'bareme' => 20],
        ], 40, 60);

        $this->assertSame(16.0, $moyenne);
    }

    public function test_sans_examen_on_ne_compte_pas_un_zero_invente(): void
    {
        $moyenne = MoyenneEcue::calculer([
            ['valeur' => 12.0, 'absent' => false, 'dispense' => false, 'famille' => FamilleEvaluation::CC, 'coefficient' => 1, 'bareme' => 20],
        ], 40, 60);

        $this->assertSame(12.0, $moyenne);
    }
}
