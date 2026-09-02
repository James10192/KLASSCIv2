<?php

namespace Tests\Unit\Services\Frais;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;
use App\Services\Frais\MontantsParFrais;
use Tests\TestCase;

/**
 * La regle qui dit ou va l'argent d'un versement.
 *
 * Elle a vecu recopiee en cinq endroits, tenue par des commentaires. Ces tests
 * sont ce qui remplace ces commentaires : ils tournent sans base de donnees,
 * donc rien n'empeche de les lancer avant chaque livraison.
 *
 * Le cas qui compte est le dernier : un versement reparti ne doit JAMAIS rendre
 * son montant entier sur un seul frais. C'est ce defaut-la qui avait fait
 * apparaitre 255 000 F sur un frais qui n'en avait recu que 60 000.
 */
class MontantsParFraisTest extends TestCase
{
    private MontantsParFrais $regle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->regle = new MontantsParFrais();
    }

    private function frais(int $id, string $nom, string $type = 'academic'): ESBTPFraisCategory
    {
        $categorie = new ESBTPFraisCategory(['name' => $nom, 'category_type' => $type]);
        $categorie->id = $id;

        return $categorie;
    }

    private function versement(float $montant, ?ESBTPFraisCategory $categorie, array $allocations = []): ESBTPPaiement
    {
        $paiement = new ESBTPPaiement(['montant' => $montant]);
        $paiement->frais_category_id = $categorie?->id;
        $paiement->setRelation('fraisCategory', $categorie);
        $paiement->setRelation('categorie', null);

        $lignes = collect($allocations)->map(function (array $part) {
            $allocation = new ESBTPPaiementAllocation([
                'frais_category_id' => $part['frais']->id,
                'montant' => $part['montant'],
            ]);
            $allocation->setRelation('fraisCategory', $part['frais']);

            return $allocation;
        });

        $paiement->setRelation('allocations', $lignes);

        return $paiement;
    }

    public function test_un_versement_non_reparti_vaut_sa_propre_categorie(): void
    {
        $scolarite = $this->frais(1, 'Scolarité');
        $versement = $this->versement(150000, $scolarite);

        $ventilation = $this->regle->ventilation($versement);

        $this->assertCount(1, $ventilation);
        $this->assertSame('Scolarité', $ventilation->first()['nom']);
        $this->assertSame(150000.0, $ventilation->first()['montant']);
        $this->assertSame(150000.0, $this->regle->part($versement, 1));
    }

    public function test_un_versement_non_reparti_ne_donne_rien_a_un_autre_frais(): void
    {
        $versement = $this->versement(150000, $this->frais(1, 'Scolarité'));

        $this->assertSame(0.0, $this->regle->part($versement, 2));
    }

    /**
     * Le defaut qui a motive tout ce travail : sans la regle, ce versement
     * rendait 255 000 F sur CHACUN des trois frais.
     */
    public function test_un_versement_reparti_ne_rend_que_sa_part_sur_chaque_frais(): void
    {
        $inscription = $this->frais(1, 'Inscription');
        $scolarite = $this->frais(2, 'Scolarité');
        $tenue = $this->frais(3, 'Tenue', 'service');

        $versement = $this->versement(255000, $inscription, [
            ['frais' => $inscription, 'montant' => 95000],
            ['frais' => $scolarite, 'montant' => 100000],
            ['frais' => $tenue, 'montant' => 60000],
        ]);

        $this->assertSame(95000.0, $this->regle->part($versement, 1));
        $this->assertSame(100000.0, $this->regle->part($versement, 2));
        $this->assertSame(60000.0, $this->regle->part($versement, 3));

        // Et la somme des parts vaut le versement : rien ne se perd, rien ne se
        // cree. C'est cet invariant qui rend les totaux additionnables.
        $this->assertSame(255000.0, $this->regle->ventilation($versement)->sum('montant'));
    }

    public function test_les_allocations_priment_sur_la_categorie_du_guichet(): void
    {
        $designe = $this->frais(1, 'Scolarité');
        $reel = $this->frais(2, 'Tenue', 'service');

        // Le caissier avait etiquete « Scolarite », la repartition a tout mis
        // sur la tenue. C'est la repartition qui fait foi.
        $versement = $this->versement(60000, $designe, [
            ['frais' => $reel, 'montant' => 60000],
        ]);

        $this->assertSame(0.0, $this->regle->part($versement, 1));
        $this->assertSame(60000.0, $this->regle->part($versement, 2));
    }

    public function test_un_versement_reparti_nomme_tous_ses_frais(): void
    {
        $a = $this->frais(1, 'Inscription');
        $b = $this->frais(2, 'Ramette');

        $versement = $this->versement(10000, $a, [
            ['frais' => $a, 'montant' => 7000],
            ['frais' => $b, 'montant' => 3000],
        ]);

        $this->assertSame(
            ['Inscription', 'Ramette'],
            $this->regle->ventilation($versement)->pluck('nom')->all()
        );
    }

    /**
     * Un versement historique sans categorie doit rester affichable : la
     * ventilation rend toujours au moins une ligne, sinon les vues qui
     * l'appellent tomberaient sur une collection vide.
     */
    public function test_un_versement_sans_categorie_reste_affichable(): void
    {
        $paiement = new ESBTPPaiement(['montant' => 5000, 'motif' => 'Frais divers']);
        $paiement->frais_category_id = null;
        $paiement->setRelation('fraisCategory', null);
        $paiement->setRelation('categorie', null);
        $paiement->setRelation('allocations', collect());

        $ventilation = $this->regle->ventilation($paiement);

        $this->assertCount(1, $ventilation);
        $this->assertSame('Frais divers', $ventilation->first()['nom']);
        $this->assertNull($ventilation->first()['frais_id']);
    }

    public function test_une_allocation_sur_un_frais_supprime_ne_casse_pas_l_affichage(): void
    {
        $versement = $this->versement(20000, $this->frais(1, 'Scolarité'), [
            ['frais' => $this->frais(9, 'Scolarité'), 'montant' => 20000],
        ]);
        // La categorie de l'allocation n'est plus chargeable (frais supprime).
        $versement->allocations->first()->setRelation('fraisCategory', null);

        $ventilation = $this->regle->ventilation($versement);

        $this->assertSame('Frais supprimé', $ventilation->first()['nom']);
        $this->assertSame(20000.0, $ventilation->first()['montant']);
    }
}
