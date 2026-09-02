<?php

namespace Tests\Unit\Services\Frais;

use App\Models\ESBTPFraisSubscription;
use App\Services\Frais\EtatFinancierParFrais;
use Tests\TestCase;

/**
 * La phrase que l'etablissement lit sur l'etat financier.
 *
 * Le piege est le montant nul : il veut dire « on ne sait pas ce que ce frais
 * coute », pas « il n'y a rien a payer ». Les annoncer tous les deux « Soldé »
 * ferait croire une dette eteinte, et cette ligne-la ne serait jamais relancee.
 */
class EtatFinancierStatutTest extends TestCase
{
    private EtatFinancierParFrais $etat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->etat = new EtatFinancierParFrais();
    }

    private function souscription(float $montant, bool $enNature = false): ESBTPFraisSubscription
    {
        $souscription = new ESBTPFraisSubscription(['amount' => $montant]);
        $souscription->satisfied_in_kind = $enNature;

        return $souscription;
    }

    public function test_rien_paye_sur_un_frais_du(): void
    {
        $this->assertSame('Aucun paiement', $this->etat->statut($this->souscription(50000), 0));
    }

    public function test_paiement_partiel(): void
    {
        $this->assertSame('Partiel', $this->etat->statut($this->souscription(50000), 20000));
    }

    public function test_frais_couvert_exactement(): void
    {
        $this->assertSame('Soldé', $this->etat->statut($this->souscription(50000), 50000));
    }

    public function test_frais_paye_au_dela_reste_solde(): void
    {
        $this->assertSame('Soldé', $this->etat->statut($this->souscription(50000), 75000));
    }

    /**
     * Un depot en nature vaut quittance : l'etudiant s'est acquitte, meme si
     * aucun argent n'est entre.
     */
    public function test_depot_en_nature_vaut_quittance(): void
    {
        $this->assertSame('Déposé en nature', $this->etat->statut($this->souscription(60000, true), 0));
    }

    /**
     * Le cas qui ne doit surtout pas ressortir « Soldé » : sans montant
     * configure, le du vaut zero et « paye >= du » serait vrai.
     */
    public function test_un_frais_sans_montant_configure_n_est_pas_solde(): void
    {
        $this->assertSame('Montant non défini', $this->etat->statut($this->souscription(0), 0));
    }

    public function test_le_depot_en_nature_prime_sur_le_montant_non_defini(): void
    {
        $this->assertSame('Déposé en nature', $this->etat->statut($this->souscription(0, true), 0));
    }
}
