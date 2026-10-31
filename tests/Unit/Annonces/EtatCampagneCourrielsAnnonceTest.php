<?php

namespace Tests\Unit\Annonces;

use App\Services\Annonces\EtatCampagneCourrielsAnnonce;
use PHPUnit\Framework\TestCase;

class EtatCampagneCourrielsAnnonceTest extends TestCase
{
    public function test_sans_adresse_n_est_pas_une_campagne_delivree(): void
    {
        $this->assertSame('no_recipients', (new EtatCampagneCourrielsAnnonce)->calculer([])['status']);
    }

    public function test_accepte_n_est_pas_delivre_et_retard_ne_permet_pas_l_arret(): void
    {
        $etat = (new EtatCampagneCourrielsAnnonce)->calculer(['delivered' => 3, 'submitted' => 2, 'delayed' => 1]);
        $this->assertSame('pending', $etat['status']);
        $this->assertSame(50, $etat['percent']);
    }

    public function test_toutes_les_adresses_delivrees_sont_terminees(): void
    {
        $etat = (new EtatCampagneCourrielsAnnonce)->calculer(['delivered' => 25]);
        $this->assertSame('complete_delivered', $etat['status']);
        $this->assertSame(100, $etat['percent']);
        $this->assertSame(0, $etat['pending']);
    }

    public function test_echecs_etat_final_ne_signifient_pas_tout_delivre(): void
    {
        $etat = (new EtatCampagneCourrielsAnnonce)->calculer(['delivered' => 8, 'failed' => 2]);
        $this->assertSame('complete_with_failures', $etat['status']);
        $this->assertSame(80, $etat['percent']);
    }

    public function test_statut_inconnu_ne_declenche_pas_une_fausse_fin(): void
    {
        $this->assertSame('pending', (new EtatCampagneCourrielsAnnonce)->calculer(['future_state' => 1])['status']);
    }
}
