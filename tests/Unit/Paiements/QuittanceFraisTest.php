<?php

namespace Tests\Unit\Paiements;

use App\Models\ESBTPFraisSubscription;
use Tests\TestCase;

/**
 * Ce qu'un recu a le droit de certifier comme regle.
 *
 * Le recu porte la mention « ce document est officiel, toute falsification
 * constitue un delit ». Une case cochee y vaut quittance. Ces tests fixent la
 * seule chose qui autorise cette coche.
 *
 * Sans base : la regle vit sur le modele et ne lit que ses propres attributs,
 * donc elle se verifie en memoire. On boote quand meme l'application — le trait
 * Auditable du modele passe par des facades des l'instanciation — mais aucun
 * test ici n'ouvre de connexion. C'est precisement ce qui manquait quand elle
 * etait enfouie dans une methode privee derriere une requete Eloquent — le bug
 * a vecu jusqu'a ce qu'une caissiere le voie sur un papier imprime.
 */
class QuittanceFraisTest extends TestCase
{
    private function souscription(?float $montant, bool $enNature = false): ESBTPFraisSubscription
    {
        $sub = new ESBTPFraisSubscription;
        $sub->amount = $montant;
        $sub->satisfied_in_kind = $enNature;

        return $sub;
    }

    public function test_un_frais_integralement_paye_est_solde(): void
    {
        $this->assertTrue($this->souscription(3000.0)->estSolde(3000.0));
    }

    public function test_un_paiement_superieur_solde_aussi(): void
    {
        $this->assertTrue($this->souscription(3000.0)->estSolde(5000.0));
    }

    public function test_un_frais_partiellement_paye_n_est_pas_solde(): void
    {
        $this->assertFalse($this->souscription(3000.0)->estSolde(2999.0));
    }

    /**
     * Le bug qui a produit de faux recus.
     *
     * Categorie laissee sur son montant par defaut : le du est nul, le paye est
     * nul, et l'ancienne regle (`max(0, du - paye) <= 0`) cochait la ligne. Un
     * montant inconnu n'est pas un montant regle.
     */
    public function test_un_frais_sans_montant_n_est_jamais_solde(): void
    {
        $this->assertFalse($this->souscription(0.0)->estSolde(0.0));
        $this->assertFalse($this->souscription(null)->estSolde(0.0));
    }

    /**
     * Meme sans montant defini, de l'argent recu ne vaut pas quittance : on
     * ignore toujours ce qui etait du, donc on ne peut pas dire que c'est solde.
     */
    public function test_un_frais_sans_montant_n_est_pas_solde_meme_avec_un_versement(): void
    {
        $this->assertFalse($this->souscription(0.0)->estSolde(50000.0));
    }

    /**
     * Un depot en nature SOLDE le frais : l'etudiant s'est acquitte.
     *
     * La coche dit qu'il n'y a plus rien a reclamer. Le recu precise ensuite
     * COMMENT — « recu en nature » — pour ne pas pretendre qu'un encaissement
     * a eu lieu. Sans la coche, le papier faisait croire que le frais restait du.
     */
    public function test_un_depot_en_nature_coche_la_ligne(): void
    {
        $this->assertTrue($this->souscription(3000.0, enNature: true)->estSolde(0.0));
    }

    public function test_un_depot_en_nature_reste_coche_meme_avec_un_versement(): void
    {
        $this->assertTrue($this->souscription(3000.0, enNature: true)->estSolde(3000.0));
    }

    public function test_le_montant_non_defini_se_signale(): void
    {
        $this->assertTrue($this->souscription(0.0)->montantNonDefini());
        $this->assertTrue($this->souscription(null)->montantNonDefini());
        $this->assertFalse($this->souscription(3000.0)->montantNonDefini());
    }

    /**
     * Un depot en nature ramene le du a zero, mais ce n'est pas un montant
     * manquant : le recu doit ecrire « depose », pas « a definir ».
     */
    public function test_un_depot_en_nature_n_est_pas_un_montant_non_defini(): void
    {
        $this->assertFalse($this->souscription(3000.0, enNature: true)->montantNonDefini());
    }
}
