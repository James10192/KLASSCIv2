<?php

namespace Tests\Unit\Domain\Enseignants;

use App\Domain\Enseignants\AgrementEnseignant;
use PHPUnit\Framework\TestCase;

class AgrementEnseignantTest extends TestCase
{
    public function test_un_agrement_expire_bloque_une_nouvelle_affectation(): void
    {
        $this->assertFalse(AgrementEnseignant::autoriseNouvelleAffectation('2026-06-01', '2026-09-15', 'actif'));
        $this->assertTrue(AgrementEnseignant::autoriseNouvelleAffectation('2026-12-01', '2026-09-15', 'actif'));
    }

    public function test_revoque_ou_suspendu_bloque(): void
    {
        $this->assertFalse(AgrementEnseignant::autoriseNouvelleAffectation(null, '2026-09-15', 'revoque'));
        $this->assertFalse(AgrementEnseignant::autoriseNouvelleAffectation(null, '2026-09-15', 'suspendu'));
    }

    public function test_l_expiration_n_efface_pas_une_dette_deja_due(): void
    {
        $this->assertTrue(AgrementEnseignant::serviceDejaFaitConserveLaDette(true));
        $this->assertFalse(AgrementEnseignant::serviceDejaFaitConserveLaDette(false));
    }
}
