<?php

namespace Tests\Feature\Finances;

use App\Domain\Comptabilite\Paiements\Actions\ValiderPaiement;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPReliquatDetail;
use App\Models\User;
use App\Services\AvoirService;
use App\Services\Finances\SituationFinanciereEtudiant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SituationFinanciereEtudiantTest extends TestCase
{
    use DatabaseTransactions;

    private User $caissier;
    private ESBTPInscription $ancien;
    private ESBTPInscription $nouveau;
    private ESBTPReliquatDetail $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->caissier = User::factory()->create();
        $this->actingAs($this->caissier);

        $oldYear = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2024-2025',
            'start_date' => now()->subYears(2),
            'end_date' => now()->subYear(),
        ]);
        $newYear = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026',
            'start_date' => now()->subYear(),
            'end_date' => now(),
        ]);
        $this->ancien = ESBTPInscription::factory()->create([
            'annee_universitaire_id' => $oldYear->id,
            'status' => 'active',
        ]);
        $this->nouveau = ESBTPInscription::factory()->create([
            'annee_universitaire_id' => $newYear->id,
            'etudiant_id' => $this->ancien->etudiant_id,
            'status' => 'active',
        ]);
        $cat = ESBTPFraisCategory::factory()->create();
        $ancienneDette = ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->ancien->id,
            'frais_category_id' => $cat->id,
            'amount' => 150000,
            'is_active' => true,
        ]);
        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->nouveau->id,
            'frais_category_id' => $cat->id,
            'amount' => 300000,
            'is_active' => true,
        ]);
        $this->report = ESBTPReliquatDetail::create([
            'inscription_source_id' => $this->ancien->id,
            'inscription_destination_id' => $this->nouveau->id,
            'frais_subscription_id' => $ancienneDette->id,
            'montant_attendu' => 150000,
            'montant_paye' => 0,
            'montant_reliquat' => 150000,
            'montant_regle' => 0,
            'statut' => 'actif',
            'date_creation' => now(),
            'created_by' => $this->caissier->id,
        ]);
    }

    private function situation(): array
    {
        return app(SituationFinanciereEtudiant::class)->pour(
            $this->ancien->etudiant_id, $this->nouveau->id
        );
    }

    public function test_dette_reportee_comptee_une_seule_fois(): void
    {
        $s = $this->situation();
        $this->assertSame(300000.0, $s['courant']);
        $this->assertSame(150000.0, $s['arrieres']);
        $this->assertSame(450000.0, $s['total']);
        $this->assertSame(0.0, (float) $s['annees']->firstWhere('inscription_id', $this->ancien->id)['non_reporte']);
        $this->assertSame('2024-2025', $s['reliquats']->first()['annee_origine']);
    }

    public function test_validation_reduit_uniquement_l_ancienne_dette(): void
    {
        $paiement = $this->paiementReliquat(50000);
        app(ValiderPaiement::class)->execute($paiement, $this->caissier);

        $this->assertSame(50000.0, (float) $this->report->fresh()->montant_regle);
        $s = $this->situation();
        $this->assertSame(100000.0, $s['arrieres']);
        $this->assertSame(300000.0, $s['courant']);
        $this->assertSame(400000.0, $s['total']);
    }

    public function test_validation_refuse_un_surencaissement_meme_si_le_paiement_existe(): void
    {
        $this->report->update(['montant_regle' => 140000, 'statut' => 'partiellement_regle']);
        $paiement = $this->paiementReliquat(20000);

        $this->expectException(\DomainException::class);
        try {
            app(ValiderPaiement::class)->execute($paiement, $this->caissier);
        } finally {
            $this->assertSame('en_attente', $paiement->fresh()->status);
            $this->assertSame(140000.0, (float) $this->report->fresh()->montant_regle);
        }
    }

    public function test_avoir_rouvre_le_reliquat_sans_alterer_la_scolarite_courante(): void
    {
        $paiement = $this->paiementReliquat(50000);
        app(ValiderPaiement::class)->execute($paiement, $this->caissier);
        app(AvoirService::class)->issue($paiement->fresh(), 20000, AvoirService::KIND_CREDIT, 'Correction de reliquat test', $this->caissier->id);

        $this->assertSame(30000.0, (float) $this->report->fresh()->montant_regle);
        $s = $this->situation();
        $this->assertSame(120000.0, $s['arrieres']);
        $this->assertSame(300000.0, $s['courant']);
    }

    private function paiementReliquat(int $montant): ESBTPPaiement
    {
        return ESBTPPaiement::create([
            'inscription_id' => $this->nouveau->id,
            'etudiant_id' => $this->ancien->etudiant_id,
            'annee_universitaire_id' => $this->nouveau->annee_universitaire_id,
            'frais_category_id' => $this->report->fraisSubscription->frais_category_id,
            'type_paiement' => 'reliquat',
            'reliquat_detail_id' => $this->report->id,
            'montant' => $montant,
            'mode_paiement' => 'espèces',
            'date_paiement' => now(),
            'status' => 'en_attente',
            'nature' => 'encaissement',
            'created_by' => $this->caissier->id,
            'numero_recu' => 'REL-TEST-'.uniqid(),
        ]);
    }
}
