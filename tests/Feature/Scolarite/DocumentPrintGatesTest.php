<?php

namespace Tests\Feature\Scolarite;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPDocumentApproval;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\DocumentPrintGuard;
use App\Services\TenantScolariteSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DocumentPrintGatesTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private ESBTPInscription $inscription;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::findOrCreate('superAdmin', 'web');
        Permission::findOrCreate('documents.print', 'web');

        $this->user = User::factory()->create();
        $this->user->assignRole('superAdmin');
        $this->user->givePermissionTo('documents.print');

        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $this->inscription = ESBTPInscription::factory()->create([
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
            'created_by' => $this->user->id,
            'date_inscription' => now()->subMonths(6),
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_unpaid_balance_blocks_before_approval(): void
    {
        $this->souscrire(40000);

        $guard = $this->guardOn();
        $etudiantId = (int) $this->inscription->etudiant_id;

        $decision = $guard->decide($this->user, 'certificat', $etudiantId);
        $this->assertSame(\App\Services\PrintDecision::SOLDE, $decision->reason);
        $this->assertFalse($decision->allowed);
        $this->assertStringContainsString('échéance', $decision->message());
    }

    public function test_paid_student_still_needs_approval(): void
    {
        $this->souscrire(40000);
        ESBTPPaiement::factory()->pour($this->inscription)->create(['montant' => 40000]);

        $guard = $this->guardOn();
        $etudiantId = (int) $this->inscription->etudiant_id;

        $decision = $guard->decide($this->user, 'certificat', $etudiantId);
        $this->assertSame(0.0, $decision->solde);
        $this->assertSame(\App\Services\PrintDecision::APPROVAL, $decision->reason);
    }

    public function test_paid_and_approved_can_print(): void
    {
        $this->souscrire(40000);
        ESBTPPaiement::factory()->pour($this->inscription)->create(['montant' => 40000]);

        $etudiantId = (int) $this->inscription->etudiant_id;
        ESBTPDocumentApproval::create([
            'document_type' => 'certificat',
            'etudiant_id' => $etudiantId,
            'status' => ESBTPDocumentApproval::STATUS_APPROVED,
            'requested_by' => $this->user->id,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);

        $guard = $this->guardOn();

        $this->assertTrue($guard->decide($this->user, 'certificat', $etudiantId)->allowed);
    }

    public function test_approval_request_is_rejected_when_unpaid(): void
    {
        $this->souscrire(25000);
        $this->actingAs($this->user);

        $settings = Mockery::mock(TenantScolariteSettings::class);
        $settings->shouldReceive('printRequiresApproval')->andReturn(true);
        $this->app->instance(TenantScolariteSettings::class, $settings);

        $response = $this->post(route('esbtp.documents.approvals.store'), [
            'document_type' => 'bulletin',
            'etudiant_id' => $this->inscription->etudiant_id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('esbtp_document_approvals', [
            'etudiant_id' => $this->inscription->etudiant_id,
            'document_type' => 'bulletin',
        ]);
    }

    private function guardOn(): DocumentPrintGuard
    {
        $settings = Mockery::mock(TenantScolariteSettings::class);
        $settings->shouldReceive('printRequiresApproval')->andReturn(true);

        return new DocumentPrintGuard($settings, new \App\Services\SoldeEtudiant());
    }

    private function souscrire(int $montant): void
    {
        $categorie = ESBTPFraisCategory::factory()->create([
            'is_mandatory' => true,
            'is_active' => true,
            'default_amount' => $montant,
        ]);

        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $categorie->id,
            'amount' => $montant,
            'is_active' => true,
            'satisfied_in_kind' => false,
            'created_by' => $this->user->id,
        ]);
    }
}
