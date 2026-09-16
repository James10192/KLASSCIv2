<?php

namespace Tests\Unit\Domain\Scolarite;

use App\Domain\Scolarite\AttestationReussite;
use App\Domain\Scolarite\EquivalenceUe;
use App\Domain\Scolarite\HomologationProgramme;
use PHPUnit\Framework\TestCase;

class FinDeCycleTest extends TestCase
{
    public function test_l_attestation_de_reussite_n_est_pas_une_frequentation(): void
    {
        $this->assertTrue(AttestationReussite::nEstPasUneFrequentation(AttestationReussite::TYPE));
        $this->assertFalse(AttestationReussite::nEstPasUneFrequentation(AttestationReussite::FREQUENTATION));
    }

    public function test_une_equivalence_ne_recompte_pas_des_credits_deja_au_wallet(): void
    {
        $this->assertSame(0, EquivalenceUe::creditsAAjouterAuWallet(true, 6));
        $this->assertSame(6, EquivalenceUe::creditsAAjouterAuWallet(false, 6));
    }

    public function test_l_homologation_n_est_ni_agrement_ni_cames(): void
    {
        $this->assertTrue(HomologationProgramme::nEstPasUnAgrementEnseignant());
        $this->assertTrue(HomologationProgramme::nEstPasUneAccreditationCames());
    }
}
