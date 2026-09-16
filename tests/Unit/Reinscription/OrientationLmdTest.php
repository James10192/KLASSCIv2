<?php

namespace Tests\Unit\Reinscription;

use App\Services\Reinscription\OrientationLmd;
use PHPUnit\Framework\TestCase;

class OrientationLmdTest extends TestCase
{
    public function test_l1_tronc_commun_propose_les_deux_specialites_l2(): void
    {
        $this->assertTrue(OrientationLmd::doitProposerTousLesParcoursDeLaMention(null, [11, 12]));
    }

    public function test_l1_parcours_general_absent_des_l2_propose_les_deux_specialites(): void
    {
        $this->assertTrue(OrientationLmd::doitProposerTousLesParcoursDeLaMention(99, [11, 12]));
    }

    public function test_deja_en_productions_animales_ne_rouvre_pas_le_choix(): void
    {
        $this->assertFalse(OrientationLmd::doitProposerTousLesParcoursDeLaMention(11, [11]));
        $this->assertFalse(OrientationLmd::doitProposerTousLesParcoursDeLaMention(11, [11, 12]));
    }

    public function test_un_l1_propose_les_deux_specialites_meme_si_un_parcours_coincide(): void
    {
        $this->assertTrue(OrientationLmd::doitProposerTousLesParcoursDeLaMention(11, [11, 12], true));
        $this->assertTrue(OrientationLmd::estAnneeDOrientation(1, 'Licence'));
        $this->assertFalse(OrientationLmd::estAnneeDOrientation(2, 'Licence'));
        $this->assertTrue(OrientationLmd::estAnneeDOrientation(4, 'Master'));
    }

    public function test_un_seul_parcours_suivant_n_est_pas_une_orientation(): void
    {
        $this->assertFalse(OrientationLmd::doitProposerTousLesParcoursDeLaMention(null, [11]));
        $this->assertFalse(OrientationLmd::doitProposerTousLesParcoursDeLaMention(null, []));
    }
}
