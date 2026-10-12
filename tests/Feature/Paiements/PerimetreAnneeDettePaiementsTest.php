<?php

namespace Tests\Feature\Paiements;

use App\Domain\Comptabilite\Reliquats\Services\LireReliquatsCaisse;
use App\Domain\Students\StudentCountService;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
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
        ESBTPPaiement::factory()->pour($this->ancienneInscription)->create(['montant' => 10000]);
        ESBTPPaiement::factory()->pour($this->inscriptionCourante)->create(['montant' => 20000]);
        ESBTPPaiement::factory()->pour($this->inscriptionCourante)->reliquat()->create(['montant' => 30000]);

        $comptes = app(PaymentFilterService::class);
        $req = fn (string $portee) => Request::create('/esbtp/paiements', 'GET', ['portee' => $portee]);

        $this->assertSame(3, $comptes->baseFilteredQuery($req('tous'))->count());
        $this->assertSame(1, $comptes->baseFilteredQuery($req('annee'))->count());
        $this->assertSame(2, $comptes->baseFilteredQuery($req('arrieres'))->count());
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
}
