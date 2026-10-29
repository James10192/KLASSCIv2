<?php

namespace Tests\Feature\Paiements;

use App\Exceptions\AvoirForbiddenException;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\AvoirService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AvoirPaiementTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private ESBTPInscription $inscription;
    private ESBTPFraisCategory $scolarite;
    private AvoirService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        $classe = ESBTPClasse::factory()->create();
        $this->inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'created_by' => $this->user->id,
        ]);

        $this->scolarite = ESBTPFraisCategory::create([
            'name' => 'Scolarité avoir',
            'code' => 'SCOL_AV_' . uniqid(),
            'is_mandatory' => true,
            'is_active' => true,
            'category_type' => 'academic',
            'sort_order' => 1,
            'default_amount' => 100000,
            'payment_deadline_days' => 30,
        ]);

        $this->service = app(AvoirService::class);
    }

    private function paiement(int $montant = 50000): ESBTPPaiement
    {
        return ESBTPPaiement::create([
            'inscription_id' => $this->inscription->id,
            'etudiant_id' => $this->inscription->etudiant_id,
            'annee_universitaire_id' => $this->inscription->annee_universitaire_id,
            'frais_category_id' => $this->scolarite->id,
            'montant' => $montant,
            'mode_paiement' => 'espèces',
            'date_paiement' => now()->toDateString(),
            'status' => 'validé',
            'nature' => 'encaissement',
            'created_by' => $this->user->id,
            'numero_recu' => 'REC-AV-'.uniqid(),
        ]);
    }

    public function test_credit_avoir_reduces_net_paid(): void
    {
        $parent = $this->paiement(50000);
        $avoir = $this->service->issue($parent, 20000, AvoirService::KIND_CREDIT, 'Trop perçu scolarité', $this->user->id);

        $this->assertTrue($avoir->isAvoir());
        $this->assertSame('credit', $avoir->avoir_kind);
        $this->assertSame(30000.0, (float) ESBTPPaiement::netPaidByCategory($this->inscription->id)[$this->scolarite->id]);
        $this->assertSame(30000.0, ESBTPPaiement::netPaidForInscription($this->inscription->id));
        $this->assertSame(50000.0, ESBTPPaiement::netCashSum(
            ESBTPPaiement::where('inscription_id', $this->inscription->id)->valides()
        ));
        $this->assertSame(30000.0, $parent->fresh()->avoir_disponible);
    }

    public function test_cannot_avoir_more_than_available(): void
    {
        $parent = $this->paiement(10000);
        $this->service->issue($parent, 7000, AvoirService::KIND_CREDIT, 'Avoir partiel test', $this->user->id);

        $this->expectException(AvoirForbiddenException::class);
        $this->service->issue($parent->fresh(), 4000, AvoirService::KIND_REFUND, 'Dépasse le reliquat', $this->user->id);
    }

    public function test_cannot_avoir_on_avoir(): void
    {
        $parent = $this->paiement(5000);
        $avoir = $this->service->issue($parent, 5000, AvoirService::KIND_CREDIT, 'Avoir total test xx', $this->user->id);

        $this->expectException(AvoirForbiddenException::class);
        $this->service->issue($avoir, 1000, AvoirService::KIND_CREDIT, 'Avoir sur avoir interdit', $this->user->id);
    }

    public function test_refund_http_store(): void
    {
        $this->withoutMiddleware();
        $parent = $this->paiement(8000);

        $response = $this->post(route('esbtp.paiements.avoir.store', $parent), [
            'montant' => 8000,
            'avoir_kind' => 'refund',
            'motif' => 'Remboursement demandé par le parent',
        ]);

        $response->assertRedirect(route('esbtp.paiements.index'));
        $this->assertDatabaseHas('esbtp_paiements', [
            'parent_paiement_id' => $parent->id,
            'nature' => 'avoir',
            'avoir_kind' => 'refund',
            'montant' => 8000,
        ]);
    }
}
