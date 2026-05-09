<?php

namespace Tests\Unit\Services;

use App\Services\EcheancierComputationService;
use App\Services\EcheancierPaymentAllocationService;
use App\Services\EcheancierProjectionService;
use App\Services\EcheancierResolverService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * Sémantique amount = 0 ("gratuit pour ce statut") :
 *  - Une catégorie configurée avec amount=0 doit produire un item dans le snapshot
 *    (avec is_free=true) pour que les diagnostics analytics voient la catégorie
 *    comme "configurée et gratuite", PAS comme "manquante / fallback".
 *  - Aucune due_line n'est projetée (rien à payer = rien à projeter).
 *  - amount > 0 garde son comportement existant (item + tranches).
 */
class EcheancierComputationServiceFreeAmountTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_zero_amount_emits_item_marked_as_free_with_no_tranches(): void
    {
        $resolver = Mockery::mock(EcheancierResolverService::class);
        $resolver->shouldReceive('resolveForConfiguration')->andReturn(null);
        $resolver->shouldReceive('resolveForOptionAssignment')->andReturn(null);
        $resolver->shouldReceive('findBestAssignmentForInscription')->andReturn(null);
        $service = new EcheancierComputationService(
            $resolver,
            new EcheancierProjectionService(),
            new EcheancierPaymentAllocationService(),
        );

        $inscription = $this->makeInscription();
        $categories = collect([$this->makeMandatoryCategory(1, 'SCOLARITE')]);
        $configurations = collect([$this->makeConfiguration(1, $inscription->filiere_id, $inscription->niveau_id, amountAffecte: 0.0)]);
        $subscriptions = collect();

        $result = $service->buildScheduleForInscription($inscription, $categories, $configurations, $subscriptions);

        $this->assertCount(1, $result['items'], 'Free category should still emit an item');
        $this->assertSame(0.0, $result['items'][0]['amount']);
        $this->assertTrue($result['items'][0]['is_free'] ?? false);
        $this->assertSame([], $result['due_lines'], 'Free category should produce no due_line to pay');
    }

    public function test_positive_amount_still_emits_tranches_normally(): void
    {
        $resolver = Mockery::mock(EcheancierResolverService::class);
        $resolver->shouldReceive('resolveForConfiguration')->andReturn(null);
        $resolver->shouldReceive('resolveForOptionAssignment')->andReturn(null);
        $resolver->shouldReceive('findBestAssignmentForInscription')->andReturn(null);
        $service = new EcheancierComputationService(
            $resolver,
            new EcheancierProjectionService(),
            new EcheancierPaymentAllocationService(),
        );

        $inscription = $this->makeInscription();
        $categories = collect([$this->makeMandatoryCategory(1, 'SCOLARITE')]);
        $configurations = collect([$this->makeConfiguration(1, $inscription->filiere_id, $inscription->niveau_id, amountAffecte: 600_000.0)]);
        $subscriptions = collect();

        $result = $service->buildScheduleForInscription($inscription, $categories, $configurations, $subscriptions);

        $this->assertCount(1, $result['items']);
        $this->assertSame(600_000.0, $result['items'][0]['amount']);
        $this->assertFalse($result['items'][0]['is_free'] ?? false);
        $this->assertCount(1, $result['due_lines'], 'Fallback should emit at least 1 line for paying category');
        $this->assertSame(600_000.0, $result['due_lines'][0]['amount']);
    }

    private function makeInscription(): \App\Models\ESBTPInscription
    {
        $insc = new \App\Models\ESBTPInscription();
        $insc->id = 99;
        $insc->filiere_id = 10;
        $insc->niveau_id = 20;
        $insc->affectation_status = 'affecté';
        $insc->date_inscription = '2025-09-15';
        return $insc;
    }

    private function makeMandatoryCategory(int $id, string $code): \App\Models\ESBTPFraisCategory
    {
        $c = new \App\Models\ESBTPFraisCategory();
        $c->id = $id;
        $c->code = $code;
        $c->name = 'Scolarité';
        $c->is_mandatory = true;
        $c->payment_deadline_days = 30;
        $c->default_amount = 600_000;
        return $c;
    }

    private function makeConfiguration(int $catId, int $filiereId, int $niveauId, float $amountAffecte): \App\Models\ESBTPFraisConfiguration
    {
        $cfg = new \App\Models\ESBTPFraisConfiguration();
        $cfg->id = 100 + $catId;
        $cfg->frais_category_id = $catId;
        $cfg->filiere_id = $filiereId;
        $cfg->niveau_id = $niveauId;
        $cfg->amount = $amountAffecte;
        $cfg->amount_affecte = $amountAffecte;
        $cfg->amount_reaffecte = null;
        $cfg->amount_non_affecte = null;
        $cfg->payment_deadline_days = 30;
        return $cfg;
    }
}
