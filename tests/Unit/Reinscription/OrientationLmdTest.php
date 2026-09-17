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

    public function test_deja_specialise_ne_rouvre_pas_le_choix(): void
    {
        $this->assertFalse(OrientationLmd::doitProposerTousLesParcoursDeLaMention(11, [11]));
        $this->assertFalse(OrientationLmd::doitProposerTousLesParcoursDeLaMention(11, [11, 12]));
    }

    public function test_une_l1_deja_specialisee_garde_son_parcours(): void
    {
        // Abidjan : L1 Bâtiment et L1 Travaux Publics sont distinctes dès l'entrée.
        // Être en L1 n'ouvre aucun choix ; seul un parcours qui s'arrête le fait.
        $this->assertFalse(OrientationLmd::doitProposerTousLesParcoursDeLaMention(11, [11, 12]));
    }

    public function test_aucun_numero_d_annee_ne_decide_de_l_orientation(): void
    {
        $this->assertFalse(method_exists(OrientationLmd::class, 'estAnneeDOrientation'));
    }

    public function test_un_seul_parcours_suivant_n_est_pas_une_orientation(): void
    {
        $this->assertFalse(OrientationLmd::doitProposerTousLesParcoursDeLaMention(null, [11]));
        $this->assertFalse(OrientationLmd::doitProposerTousLesParcoursDeLaMention(null, []));
    }
}
