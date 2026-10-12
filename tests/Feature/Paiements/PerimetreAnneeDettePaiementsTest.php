<?php

namespace Tests\Feature\Paiements;

use App\Domain\Comptabilite\Reliquats\Services\LireReliquatsCaisse;
use App\Domain\Students\StudentCountService;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPReliquatDetail;
use App\Models\User;
use App\Http\Controllers\Comptabilite\EtatFinancierController;
use App\Services\Frais\EtatFinancierParFrais;
use ReflectionMethod;
use App\Services\PaymentFilterService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

class PerimetreAnneeDettePaiementsTest extends TestCase
{
    use DatabaseTransactions;

    private ESBTPInscription $ancienneInscription;
    private ESBTPInscription $inscriptionCourante;
    private ESBTPAnneeUniversitaire $anneeCourante;

    protected function setUp(): void
    {
        parent::setUp();

        // Une seule année officielle courante pour des assertions déterministes.
        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $anneeAncienne = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2024-2025', 'is_current' => false,
        ]);
        $this->anneeCourante = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2026-2027', 'is_current' => true,
        ]);
        $this->ancienneInscription = ESBTPInscription::factory()->create([
            'annee_universitaire_id' => $anneeAncienne->id,
        ]);
        $this->inscriptionCourante = ESBTPInscription::factory()->create([
            'annee_universitaire_id' => $this->anneeCourante->id,
        ]);
    }

    public function test_annee_de_la_dette_et_date_du_paiement_ne_creent_pas_de_faux_inscrits(): void
    {
        $compte = app(StudentCountService::class);
        $avant = $compte->inscritsDe($this->anneeCourante->id);
        $nbInscriptions = ESBTPInscription::count();

        // Payé AUJOURD'HUI pour une inscription de 2024-2025.
        ESBTPPaiement::factory()->pour($this->ancienneInscription)->create([
            'date_paiement' => now()->toDateString(),
            'montant' => 15000,
        ]);

        $this->assertSame($avant, $compte->inscritsDe($this->anneeCourante->id));
        $this->assertSame($nbInscriptions, ESBTPInscription::count());
    }

    public function test_les_trois_portees_exposent_les_bons_encaissements(): void
    {
        $ancien = ESBTPPaiement::factory()->pour($this->ancienneInscription)->create(['montant' => 10000]);
        $courant = ESBTPPaiement::factory()->pour($this->inscriptionCourante)->create(['montant' => 20000]);
        $reporte = ESBTPPaiement::factory()->pour($this->inscriptionCourante)->reliquat()->create(['montant' => 30000]);
        $ids = [$ancien->id, $courant->id, $reporte->id];

        $comptes = app(PaymentFilterService::class);
        $req = fn (string $portee) => Request::create('/esbtp/paiements', 'GET', ['portee' => $portee]);

        $this->assertSame(3, $comptes->baseFilteredQuery($req('tous'))->whereIn('esbtp_paiements.id', $ids)->count());
        $this->assertSame(1, $comptes->baseFilteredQuery($req('annee'))->whereIn('esbtp_paiements.id', $ids)->count());
        $this->assertSame(2, $comptes->baseFilteredQuery($req('arrieres'))->whereIn('esbtp_paiements.id', $ids)->count());
    }

    public function test_rechercher_une_ancienne_inscription_sans_reinscription_courante(): void
    {
        $this->withoutMiddleware();
        $matricule = $this->ancienneInscription->etudiant->matricule;
        $response = $this->getJson(route('esbtp.api.caisse.inscriptions', [
            'q' => $matricule,
            'anciennes' => 1,
        ]));

        $response->assertOk()->assertJsonPath('results.0.id', $this->ancienneInscription->id);
    }

    public function test_inscription_terminee_reste_recherchable_en_caisse_pour_regler_sa_dette(): void
    {
        $this->withoutMiddleware();
        $this->ancienneInscription->update(['status' => 'terminée']);

        $response = $this->getJson(route('esbtp.api.caisse.inscriptions', [
            'q' => $this->ancienneInscription->etudiant->matricule,
            'anciennes' => 1,
        ]));

        $response->assertOk()->assertJsonPath('results.0.id', $this->ancienneInscription->id);
    }

    public function test_inscription_annulee_ne_peut_pas_etre_proposee_comme_dette_encaisser(): void
    {
        $this->withoutMiddleware();
        $this->ancienneInscription->update(['status' => 'annulée']);

        $response = $this->getJson(route('esbtp.api.caisse.inscriptions', [
            'q' => $this->ancienneInscription->etudiant->matricule,
            'anciennes' => 1,
        ]));

        $response->assertOk()->assertJsonPath('results', []);
    }

    public function test_export_detaille_respecte_le_meme_perimetre_que_la_liste(): void
    {
        $ancien = ESBTPPaiement::factory()->pour($this->ancienneInscription)->create(['montant' => 10000]);
        $courant = ESBTPPaiement::factory()->pour($this->inscriptionCourante)->create(['montant' => 20000]);
        $reporte = ESBTPPaiement::factory()->pour($this->inscriptionCourante)->reliquat()->create(['montant' => 30000]);
        $ids = [$ancien->id, $courant->id, $reporte->id];
        $export = app(\App\Services\PaiementExportService::class);

        $this->assertSame(3, $export->buildQuery(['portee' => 'tous'])->whereIn('esbtp_paiements.id', $ids)->count());
        $this->assertSame(1, $export->buildQuery(['portee' => 'annee'])->whereIn('esbtp_paiements.id', $ids)->count());
        $this->assertSame(2, $export->buildQuery(['portee' => 'arrieres'])->whereIn('esbtp_paiements.id', $ids)->count());
        $resumes = $export->buildFiltersSummary(['portee' => 'tous', 'date_debut' => now()->toDateString()]);
        $this->assertSame('Années des inscriptions', $resumes[0]['label']);
        $this->assertSame('Dates de versement', $resumes[1]['label']);
    }

    public function test_encaissement_du_jour_sur_ancienne_inscription_figure_dans_le_journal_toutes_annees(): void
    {
        $aujourdhui = now()->toDateString();
        $ancien = ESBTPPaiement::factory()->pour($this->ancienneInscription)->create([
            'date_paiement' => $aujourdhui,
            'montant' => 25000,
        ]);
        $courantHier = ESBTPPaiement::factory()->pour($this->inscriptionCourante)->create([
            'date_paiement' => now()->subDay()->toDateString(),
            'montant' => 50000,
        ]);
        $ids = [$ancien->id, $courantHier->id];

        $request = Request::create('/esbtp/paiements', 'GET', [
            'portee' => 'tous',
            'date_debut' => $aujourdhui,
            'date_fin' => $aujourdhui,
        ]);

        $journal = app(PaymentFilterService::class)->baseFilteredQuery($request)
            ->whereIn('esbtp_paiements.id', $ids)->pluck('esbtp_paiements.id')->all();
        $export = app(\App\Services\PaiementExportService::class)
            ->buildQuery(['portee' => 'tous', 'date_debut' => $aujourdhui, 'date_fin' => $aujourdhui])
            ->whereIn('esbtp_paiements.id', $ids)->pluck('esbtp_paiements.id')->all();

        $this->assertEqualsCanonicalizing([$ancien->id], $journal);
        $this->assertEqualsCanonicalizing($journal, $export);
    }

    public function test_l_ancienne_dette_non_reportee_est_proposee_sans_inscription_nouvelle(): void
    {
        $categorie = ESBTPFraisCategory::factory()->create();
        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->ancienneInscription->id,
            'frais_category_id' => $categorie->id,
            'amount' => 100000,
        ]);

        $avant = ESBTPInscription::count();
        $situation = app(LireReliquatsCaisse::class)->pour($this->ancienneInscription);
        $ancienneDette = $situation['dettes_non_reportees']->firstWhere('inscription_id', $this->ancienneInscription->id);

        $this->assertNotNull($ancienneDette);
        $this->assertSame(100000.0, $ancienneDette['montant']);
        $this->assertStringContainsString('inscription_id='.$this->ancienneInscription->id, $ancienneDette['url']);
        $this->assertSame(0, $situation['reliquats']->count());
        $this->assertSame($avant, ESBTPInscription::count());
    }

    public function test_etat_cumule_inclut_les_inscriptions_terminees_sans_inclure_les_annulations(): void
    {
        $this->ancienneInscription->update(['status' => 'terminée']);
        $annulee = ESBTPInscription::factory()->create([
            'annee_universitaire_id' => $this->ancienneInscription->annee_universitaire_id,
            'status' => 'annulée',
        ]);

        $selection = new ReflectionMethod(EtatFinancierController::class, 'inscriptions');
        $controleur = app(EtatFinancierController::class);
        $toutes = $selection->invoke($controleur, null, '', null);
        $courante = $selection->invoke($controleur, $this->anneeCourante, '', null);

        $this->assertTrue($toutes->contains('id', $this->ancienneInscription->id));
        $this->assertFalse($toutes->contains('id', $annulee->id));
        $this->assertFalse($courante->contains('id', $this->ancienneInscription->id));
    }

    public function test_etat_par_frais_compte_une_creance_reportee_une_seule_fois(): void
    {
        $this->ancienneInscription->update(['status' => 'terminée']);
        $frais = ESBTPFraisCategory::factory()->create();
        $souscription = ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->ancienneInscription->id,
            'frais_category_id' => $frais->id,
            'amount' => 100000,
            'is_active' => true,
        ]);

        ESBTPReliquatDetail::create([
            'inscription_source_id' => $this->ancienneInscription->id,
            'inscription_destination_id' => $this->inscriptionCourante->id,
            'frais_subscription_id' => $souscription->id,
            'montant_attendu' => 100000,
            'montant_paye' => 0,
            'montant_reliquat' => 60000,
            'montant_regle' => 20000,
            'statut' => 'partiellement_regle',
            'date_creation' => now(),
            'created_by' => User::factory()->create()->id,
        ]);

        $sources = ESBTPInscription::query()->whereKey($this->ancienneInscription->id)
            ->with(['etudiant', 'classe', 'anneeUniversitaire'])->get();
        $lignes = app(EtatFinancierParFrais::class)->construire($sources, $frais->id);

        $this->assertCount(2, $lignes);
        $this->assertEquals(100000, $lignes->sum('du'));
        $this->assertEquals(20000, $lignes->sum('paye'));
        $this->assertEquals(80000, $lignes->sum('reste'));
        $this->assertTrue($lignes->contains(fn ($ligne) => str_contains($ligne['frais'], 'reliquat reporté')));
    }

}
