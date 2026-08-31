<?php

namespace Tests\Feature\Frais;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;
use App\Models\User;
use App\Services\AvoirService;
use App\Services\Frais\RepartitionTropPercu;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * La repartition d'un versement sur plusieurs frais.
 *
 * Le test central est celui de CONSERVATION : ce qui a ete encaisse doit se
 * retrouver, au franc pres, dans les totaux par frais. C'est la seule propriete
 * qui compte vraiment ici, parce que sa rupture est SILENCIEUSE — un versement
 * qui porte des allocations est lu par elles et plus du tout par sa categorie,
 * donc tout ce qui n'est pas alloue disparait sans erreur ni trace.
 */
class RepartitionTropPercuTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private ESBTPInscription $inscription;

    private ESBTPFraisCategory $scolarite;

    private ESBTPFraisCategory $tenue;

    private RepartitionTropPercu $repartition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->inscription = $this->creerInscription();

        // L'ordre de service est celui de l'ecole : `sort_order` 1 puis 2.
        $this->scolarite = ESBTPFraisCategory::factory()->ordre(1)->create(['name' => 'Scolarite']);
        $this->tenue = ESBTPFraisCategory::factory()->ordre(2)->create(['name' => 'Tenue']);

        $this->doit($this->inscription, $this->scolarite, 150000);
        $this->doit($this->inscription, $this->tenue, 100000);

        $this->repartition = app(RepartitionTropPercu::class);
    }

    // ------------------------------------------------------------------
    // Conservation — le coeur du sujet
    // ------------------------------------------------------------------

    public function test_la_repartition_conserve_la_totalite_du_versement(): void
    {
        $paiement = $this->verse(255000, $this->scolarite);

        $resultat = $this->appliquer();

        $this->assertTrue($resultat['applique']);

        // La somme des allocations vaut EXACTEMENT le versement. Sans cela, la
        // difference sortirait des totaux sans que rien ne le signale.
        $this->assertSame(
            255000.0,
            (float) ESBTPPaiementAllocation::where('paiement_id', $paiement->id)->sum('montant')
        );

        // 150 000 soldent la scolarite, 100 000 la tenue, et les 5 000 restants
        // sont une avance : ils demeurent sur le frais que le caissier avait
        // designe.
        $net = ESBTPPaiement::netPaidByCategory($this->inscription->id);
        $this->assertSame(155000.0, (float) $net[$this->scolarite->id]);
        $this->assertSame(100000.0, (float) $net[$this->tenue->id]);
    }

    public function test_rejouer_sans_reset_ne_change_rien(): void
    {
        $this->verse(255000, $this->scolarite);
        $this->appliquer();

        $avant = $this->photographieDesAllocations();

        $second = $this->appliquer();

        $this->assertSame(0, $second['allocations'], 'Un versement deja reparti n\'est plus candidat.');
        $this->assertSame($avant, $this->photographieDesAllocations());
    }

    public function test_rejouer_avec_reset_reproduit_le_meme_etat(): void
    {
        $this->verse(255000, $this->scolarite);
        $this->appliquer();

        $avant = $this->photographieDesAllocations();

        $this->repartition->executer(true, $this->inscription->id, null, true);

        $this->assertSame($avant, $this->photographieDesAllocations());
    }

    // ------------------------------------------------------------------
    // Les chemins qui rompaient la conservation
    // ------------------------------------------------------------------

    public function test_un_versement_sans_categorie_ne_fait_pas_echouer_le_lot(): void
    {
        // `frais_category_id` est nullable : `(int) null` valait zero, et une
        // allocation sur la categorie 0 faisait echouer la cle etrangere — donc
        // TOUTE la transaction, y compris les versements sans rapport.
        $sansCategorie = $this->verse(255000, null);

        $autre = $this->creerInscription();
        $this->doit($autre, $this->scolarite, 150000);
        $this->doit($autre, $this->tenue, 100000);
        $paiementDeLAutre = ESBTPPaiement::factory()
            ->pour($autre)
            ->surCategorie($this->scolarite->id)
            ->montant(255000)
            ->create();

        $resultat = $this->repartition->executer(true);

        $this->assertTrue($resultat['applique']);

        // Le versement sans categorie est reparti : sans allocation il n'etait
        // impute a aucun frais, il l'est desormais.
        $this->assertSame(
            255000.0,
            (float) ESBTPPaiementAllocation::where('paiement_id', $sansCategorie->id)->sum('montant')
        );

        $net = ESBTPPaiement::netPaidByCategory($this->inscription->id);
        $this->assertSame(150000.0, (float) $net[$this->scolarite->id]);
        // Faute de categorie d'origine, l'avance echoit au dernier frais servi.
        $this->assertSame(105000.0, (float) $net[$this->tenue->id]);

        // Et le lot n'a pas ete emporte avec lui.
        $this->assertSame(
            255000.0,
            (float) ESBTPPaiementAllocation::where('paiement_id', $paiementDeLAutre->id)->sum('montant')
        );
    }

    public function test_un_reliquat_n_est_jamais_candidat(): void
    {
        // Un reliquat eteint une dette d'une annee anterieure : le calcul par
        // frais l'ignore. S'il devenait candidat, il consommerait du reste et
        // recevrait une allocation invisible — privant le versement reel qui
        // suit du frais qu'il devait couvrir.
        $reliquat = ESBTPPaiement::factory()
            ->pour($this->inscription)
            ->surCategorie($this->scolarite->id)
            ->montant(200000)
            ->reliquat()
            ->create(['date_paiement' => now()->subMonth()->toDateString()]);

        $reel = $this->verse(255000, $this->scolarite);

        $this->appliquer();

        $this->assertSame(0, ESBTPPaiementAllocation::where('paiement_id', $reliquat->id)->count());

        // Le versement reel trouve les deux frais entiers, comme si le reliquat
        // n'existait pas — parce que pour ce calcul, il n'existe pas.
        $this->assertSame(
            255000.0,
            (float) ESBTPPaiementAllocation::where('paiement_id', $reel->id)->sum('montant')
        );

        $net = ESBTPPaiement::netPaidByCategory($this->inscription->id);
        $this->assertSame(155000.0, (float) $net[$this->scolarite->id]);
        $this->assertSame(100000.0, (float) $net[$this->tenue->id]);
    }

    public function test_le_diagnostic_signale_une_repartition_qui_ne_boucle_pas(): void
    {
        $paiement = $this->verse(255000, $this->scolarite);
        $this->appliquer();

        // On casse l'invariant a la main, comme le ferait une suppression de
        // categorie en cascade ou une correction SQL malheureuse.
        ESBTPPaiementAllocation::where('paiement_id', $paiement->id)
            ->where('frais_category_id', $this->tenue->id)
            ->delete();

        $this->artisan('frais:verifier-allocations', ['--inscription' => $this->inscription->id])
            ->assertExitCode(1);
    }

    // ------------------------------------------------------------------
    // Avoirs — annuler la ou l'argent est alle
    // ------------------------------------------------------------------

    public function test_un_avoir_total_ramene_tous_les_frais_a_zero(): void
    {
        $paiement = $this->verse(250000, $this->scolarite);
        $this->appliquer();

        // 150 000 sur la scolarite, 100 000 sur la tenue.
        $net = ESBTPPaiement::netPaidByCategory($this->inscription->id);
        $this->assertSame(150000.0, (float) $net[$this->scolarite->id]);
        $this->assertSame(100000.0, (float) $net[$this->tenue->id]);

        app(AvoirService::class)->issue(
            $paiement->fresh(),
            250000,
            AvoirService::KIND_CREDIT,
            'Annulation totale du versement',
            $this->user->id
        );

        // L'avoir annule 250 000 : plus rien ne doit rester nulle part. Impute
        // en entier a la seule scolarite, il y aurait ete ecrete par le
        // `max(0, ...)` et aurait laisse 100 000 F de paiement fantome sur la
        // tenue.
        $net = ESBTPPaiement::netPaidByCategory($this->inscription->id);
        $this->assertSame(0.0, (float) $net[$this->scolarite->id]);
        $this->assertSame(0.0, (float) $net[$this->tenue->id]);
    }

    public function test_un_avoir_partiel_est_annule_au_prorata(): void
    {
        $paiement = $this->verse(250000, $this->scolarite);
        $this->appliquer();

        app(AvoirService::class)->issue(
            $paiement->fresh(),
            125000,
            AvoirService::KIND_CREDIT,
            'Remboursement de la moitie du versement',
            $this->user->id
        );

        // Rien ne dit quel frais l'ecole entend rembourser d'abord : la moitie
        // rendue s'applique dans les memes proportions que le versement.
        $net = ESBTPPaiement::netPaidByCategory($this->inscription->id);
        $this->assertSame(75000.0, (float) $net[$this->scolarite->id]);
        $this->assertSame(50000.0, (float) $net[$this->tenue->id]);
    }

    public function test_un_avoir_emis_avant_la_repartition_est_remis_en_phase(): void
    {
        // L'ordre inverse du precedent — et celui qui condamne le remede
        // consistant a « refuser de repartir un versement qui porte des
        // avoirs » : ici l'avoir existe deja quand la repartition passe.
        $paiement = $this->verse(250000, $this->scolarite);

        app(AvoirService::class)->issue(
            $paiement->fresh(),
            250000,
            AvoirService::KIND_CREDIT,
            'Annulation totale avant repartition',
            $this->user->id
        );

        $this->appliquer();

        $net = ESBTPPaiement::netPaidByCategory($this->inscription->id);
        $this->assertSame(0.0, (float) $net[$this->scolarite->id]);
        $this->assertSame(0.0, (float) $net[$this->tenue->id]);
    }

    // ------------------------------------------------------------------

    private function appliquer(): array
    {
        return $this->repartition->executer(true, $this->inscription->id);
    }

    private function verse(float $montant, ?ESBTPFraisCategory $categorie): ESBTPPaiement
    {
        return ESBTPPaiement::factory()
            ->pour($this->inscription)
            ->surCategorie($categorie?->id)
            ->montant($montant)
            ->create();
    }

    private function doit(ESBTPInscription $inscription, ESBTPFraisCategory $categorie, float $montant): void
    {
        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $inscription->id,
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

    /**
     * Ce qui est ecrit, sous une forme comparable d'une execution a l'autre.
     *
     * @return array<string, float>
     */
    private function photographieDesAllocations(): array
    {
        return ESBTPPaiementAllocation::query()
            ->whereIn('paiement_id', ESBTPPaiement::where('inscription_id', $this->inscription->id)->select('id'))
            ->orderBy('paiement_id')
            ->orderBy('frais_category_id')
            ->get()
            ->mapWithKeys(fn ($a) => [$a->paiement_id.':'.$a->frais_category_id => (float) $a->montant])
            ->all();
    }
}
