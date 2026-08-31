<?php

namespace Tests\Feature\Frais;

use App\Exceptions\RepartitionRefuseeException;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;
use App\Models\User;
use App\Services\Frais\RepartitionDuVersement;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * L'imputation d'un versement, decidee au moment ou l'argent entre.
 *
 * Deux proprietes portent tout le reste :
 *
 * 1. CONSERVATION — la somme des allocations vaut EXACTEMENT le versement. Sa
 *    rupture est silencieuse : un versement qui porte des allocations est lu par
 *    elles et plus du tout par sa categorie, donc tout ce qui n'est pas alloue
 *    sort des totaux sans erreur ni trace.
 *
 * 2. PLAFOND — aucun frais ne recoit plus qu'il ne reclame, EN COMPTANT les
 *    versements encore en attente de validation. Sans ce dernier point, deux
 *    versements successifs de 300 000 F sur une dette de 300 000 F passent tous
 *    les deux : le premier, non valide, est invisible au garde-fou du second.
 */
class RepartitionDuVersementTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private ESBTPInscription $inscription;

    private ESBTPFraisCategory $scolarite;

    private ESBTPFraisCategory $tenue;

    private RepartitionDuVersement $repartition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->inscription = $this->creerInscription();

        // L'ordre de service est celui de l'ecole : `sort_order` 1 puis 2.
        $this->scolarite = ESBTPFraisCategory::factory()->ordre(1)->create(['name' => 'Scolarite']);
        $this->tenue = ESBTPFraisCategory::factory()->ordre(2)->create(['name' => 'Tenue']);

        $this->doit($this->scolarite, 150000);
        $this->doit($this->tenue, 100000);

        $this->repartition = app(RepartitionDuVersement::class);
    }

    // ------------------------------------------------------------------
    // Conservation
    // ------------------------------------------------------------------

    public function test_un_versement_couvrant_plusieurs_frais_est_impute_en_totalite(): void
    {
        // 255 000 F pour 150 000 + 100 000 dus : le scenario meme du defaut.
        $allocations = $this->calculer(255000, $this->scolarite);

        $this->assertSame(255000.0, round(array_sum($allocations), 2));

        // 100 000 soldent la tenue ; les 5 000 F qui depassent restent sur le
        // frais que le caissier a designe — la meme avance, au meme endroit, que
        // celle posee par la reprise d'historique (RepartitionTropPercu).
        $this->assertSame(155000.0, $allocations[$this->scolarite->id]);
        $this->assertSame(100000.0, $allocations[$this->tenue->id]);
    }

    public function test_les_allocations_ecrites_totalisent_le_versement(): void
    {
        $paiement = $this->verse(255000, $this->scolarite);
        $this->repartition->ecrire($paiement, $this->calculer(255000, $this->scolarite));

        $ecrites = ESBTPPaiementAllocation::where('paiement_id', $paiement->id)
            ->pluck('montant', 'frais_category_id');

        $this->assertSame(255000.0, (float) $ecrites->sum());
        $this->assertSame(155000.0, (float) $ecrites[$this->scolarite->id]);
        $this->assertSame(100000.0, (float) $ecrites[$this->tenue->id]);
    }

    public function test_un_frais_unique_recoit_une_allocation_couvrant_la_totalite(): void
    {
        // L'invariant doit tenir meme quand la repartition ne dit rien de plus
        // que le paiement : sinon le versement retombe dans le mode « sans
        // allocation », et l'imputation redevient un etat qui se fixe hors bande.
        $allocations = $this->calculer(80000, $this->scolarite);

        $this->assertSame([$this->scolarite->id => 80000.0], $allocations);
    }

    public function test_le_frais_designe_est_servi_avant_les_autres(): void
    {
        // La tenue est deuxieme dans l'ordre de l'ecole, mais c'est elle que le
        // caissier a nommee : c'est l'intention explicite du versement.
        $allocations = $this->calculer(120000, $this->tenue);

        $this->assertSame(100000.0, $allocations[$this->tenue->id]);
        $this->assertSame(20000.0, $allocations[$this->scolarite->id]);
    }

    public function test_un_versement_nul_n_impute_rien(): void
    {
        // Une exoneration confirmee. Ecrire des lignes a zero ne dirait rien de
        // plus que le versement lui-meme.
        $this->assertSame([], $this->calculer(0, $this->scolarite));
    }

    // ------------------------------------------------------------------
    // Plafond
    // ------------------------------------------------------------------

    public function test_un_versement_qui_n_eteint_aucune_dette_est_refuse(): void
    {
        // Tout est deja couvert par un premier versement, ENCORE EN ATTENTE de
        // validation. C'est precisement le cas qui laissait passer un doublon :
        // sans le comptage des « en attente », ce premier versement est invisible
        // au garde-fou du second.
        $premier = $this->verseEnAttente(255000, $this->scolarite);
        $this->repartition->ecrire($premier, $this->calculer(255000, $this->scolarite));

        $this->expectException(RepartitionRefuseeException::class);
        $this->expectExceptionMessageMatches('/ne doit plus rien/');

        $this->calculer(255000, $this->scolarite);
    }

    public function test_le_plafond_compte_les_versements_encore_en_attente(): void
    {
        // Premier versement : il solde la scolarite, mais reste EN ATTENTE.
        $premier = $this->verseEnAttente(150000, $this->scolarite);
        $this->repartition->ecrire($premier, [$this->scolarite->id => 150000.0]);

        // La scolarite ne reclame plus rien ; seule la tenue reste due.
        $reste = $this->repartition->resteConnuParFrais($this->inscription->id);
        $this->assertSame(0.0, $reste[$this->scolarite->id]);
        $this->assertSame(100000.0, $reste[$this->tenue->id]);
    }

    public function test_un_second_versement_ne_solde_pas_deux_fois_le_meme_frais(): void
    {
        $premier = $this->verseEnAttente(150000, $this->scolarite);
        $this->repartition->ecrire($premier, [$this->scolarite->id => 150000.0]);

        // Le caissier redesigne la scolarite, mais elle ne reclame plus rien :
        // l'argent va la ou il reste une dette, la tenue.
        $allocations = $this->calculer(100000, $this->scolarite);

        $this->assertSame([$this->tenue->id => 100000.0], $allocations);
    }

    // ------------------------------------------------------------------
    // Le zero veut dire « inconnu »
    // ------------------------------------------------------------------

    public function test_un_frais_sans_tarif_configure_reste_encaissable(): void
    {
        $bibliotheque = ESBTPFraisCategory::factory()->ordre(3)->create(['name' => 'Bibliotheque']);
        $this->doit($bibliotheque, 0);

        // Aucun plafond a opposer : l'ecole n'a pas encore dit ce que ce frais
        // coute, et zero ne prouve pas qu'il n'y a rien a payer.
        $this->assertArrayNotHasKey(
            $bibliotheque->id,
            $this->repartition->resteConnuParFrais($this->inscription->id)
        );

        $this->assertSame([$bibliotheque->id => 40000.0], $this->calculer(40000, $bibliotheque));
    }

    public function test_un_frais_sans_souscription_reste_encaissable(): void
    {
        $carte = ESBTPFraisCategory::factory()->ordre(4)->create(['name' => 'Carte etudiant']);

        $this->assertSame([$carte->id => 5000.0], $this->calculer(5000, $carte));
    }

    // ------------------------------------------------------------------
    // Repartition explicite du caissier
    // ------------------------------------------------------------------

    public function test_une_repartition_explicite_qui_fait_le_compte_est_acceptee(): void
    {
        $allocations = $this->calculer(120000, $this->scolarite, [
            $this->scolarite->id => 70000,
            $this->tenue->id => 50000,
        ]);

        $this->assertSame(70000.0, $allocations[$this->scolarite->id]);
        $this->assertSame(50000.0, $allocations[$this->tenue->id]);
    }

    public function test_une_repartition_qui_ne_fait_pas_le_compte_est_refusee(): void
    {
        $this->expectException(RepartitionRefuseeException::class);
        $this->expectExceptionMessageMatches('/totalise/');

        $this->calculer(120000, $this->scolarite, [
            $this->scolarite->id => 70000,
            $this->tenue->id => 40000,
        ]);
    }

    public function test_une_repartition_qui_depasse_un_frais_non_designe_est_refusee(): void
    {
        $this->expectException(RepartitionRefuseeException::class);
        $this->expectExceptionMessageMatches('/ne reclame plus que/');

        // La tenue ne doit que 100 000 : lui en imputer 150 000 ferait entrer
        // 50 000 F dans un frais qui ne les reclame pas.
        $this->calculer(250000, $this->scolarite, [
            $this->scolarite->id => 100000,
            $this->tenue->id => 150000,
        ]);
    }

    public function test_une_avance_deliberee_sur_le_frais_designe_est_acceptee(): void
    {
        // Le seul endroit ou une avance peut se poser : le frais que le caissier
        // a nomme. Ailleurs elle serait subie ; ici elle est voulue.
        $allocations = $this->calculer(300000, $this->scolarite, [
            $this->scolarite->id => 200000,
            $this->tenue->id => 100000,
        ]);

        $this->assertSame(300000.0, round(array_sum($allocations), 2));
        $this->assertSame(200000.0, $allocations[$this->scolarite->id]);
    }

    public function test_une_part_negative_est_refusee(): void
    {
        $this->expectException(RepartitionRefuseeException::class);

        $this->calculer(100000, $this->scolarite, [
            $this->scolarite->id => 150000,
            $this->tenue->id => -50000,
        ]);
    }

    // ------------------------------------------------------------------
    // L'invariant est verifie a l'ecriture, pas seulement affirme
    // ------------------------------------------------------------------

    public function test_ecrire_refuse_une_repartition_qui_ne_couvre_pas_le_versement(): void
    {
        $paiement = $this->verse(250000, $this->scolarite);

        $this->expectException(\App\Exceptions\AllocationIncoherenteException::class);

        // 100 000 F sortiraient des totaux par frais sans erreur ni trace.
        $this->repartition->ecrire($paiement, [$this->scolarite->id => 150000.0]);
    }

    // ------------------------------------------------------------------
    // Bout en bout : ce que le calcul par frais lit ensuite
    // ------------------------------------------------------------------

    public function test_le_total_par_frais_reflete_la_repartition_ecrite(): void
    {
        $paiement = $this->verse(250000, $this->scolarite);
        $this->repartition->ecrire($paiement, $this->calculer(250000, $this->scolarite));

        $net = ESBTPPaiement::netPaidByCategory($this->inscription->id);

        $this->assertSame(150000.0, (float) $net[$this->scolarite->id]);
        $this->assertSame(100000.0, (float) $net[$this->tenue->id]);
    }

    public function test_la_reprise_d_historique_ignore_un_versement_deja_reparti(): void
    {
        // La consequence attendue du recadrage : un versement encaisse arrive
        // deja impute, donc la reprise n'a plus rien a en dire. Sans cela, les
        // deux mecanismes se marcheraient dessus.
        $paiement = $this->verse(250000, $this->scolarite);
        $this->repartition->ecrire($paiement, $this->calculer(250000, $this->scolarite));

        $avant = ESBTPPaiementAllocation::where('paiement_id', $paiement->id)
            ->orderBy('frais_category_id')
            ->pluck('montant', 'frais_category_id')
            ->all();

        app(\App\Services\Frais\RepartitionTropPercu::class)
            ->executer(true, $this->inscription->id);

        $apres = ESBTPPaiementAllocation::where('paiement_id', $paiement->id)
            ->orderBy('frais_category_id')
            ->pluck('montant', 'frais_category_id')
            ->all();

        $this->assertSame($avant, $apres);
    }

    // ------------------------------------------------------------------

    /**
     * @param  array<int, float>|null  $saisie
     * @return array<int, float>
     */
    private function calculer(float $montant, ESBTPFraisCategory $designe, ?array $saisie = null): array
    {
        return $this->repartition->calculer(
            $this->inscription->id,
            $montant,
            $designe->id,
            $saisie
        );
    }

    private function verse(float $montant, ?ESBTPFraisCategory $categorie): ESBTPPaiement
    {
        return ESBTPPaiement::factory()
            ->pour($this->inscription)
            ->surCategorie($categorie?->id)
            ->montant($montant)
            ->create();
    }

    /**
     * Un versement qui n'a PAS encore ete valide. C'est l'etat dans lequel
     * l'encaissement cree tout paiement, et celui que le garde-fou doit voir.
     */
    private function verseEnAttente(float $montant, ?ESBTPFraisCategory $categorie): ESBTPPaiement
    {
        return ESBTPPaiement::factory()
            ->pour($this->inscription)
            ->surCategorie($categorie?->id)
            ->montant($montant)
            ->enAttente()
            ->create();
    }

    private function doit(ESBTPFraisCategory $categorie, float $montant): void
    {
        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $categorie->id,
            'amount' => $montant,
            'created_by' => $this->user->id,
        ]);
    }

    private function creerInscription(): ESBTPInscription
    {
        $classe = ESBTPClasse::factory()->create();

        return ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'created_by' => $this->user->id,
        ]);
    }
}
