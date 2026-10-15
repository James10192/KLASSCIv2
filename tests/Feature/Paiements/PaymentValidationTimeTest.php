<?php

namespace Tests\Feature\Paiements;

use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaymentValidationTimeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_payment_stores_real_validation_hour_separately_from_accounting_day(): void
    {
        Carbon::setTestNow('2026-10-15 13:47:21');
        try {
            $paiement = ESBTPPaiement::factory()
                ->pour(ESBTPInscription::factory()->create())
                ->enAttente()
                ->create(['date_paiement' => '2026-10-10']);

            $this->assertNull($paiement->validation_recorded_at);
            $paiement->update(['status' => 'validé', 'date_validation' => now()]);

            $this->assertSame('2026-10-10', $paiement->fresh()->date_paiement->format('Y-m-d'));
            $this->assertSame('2026-10-15 13:47:21', $paiement->fresh()->validation_recorded_at->format('Y-m-d H:i:s'));
            $this->assertSame('2026-10-15', $paiement->fresh()->date_validation->format('Y-m-d'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_unrelated_edit_never_invents_validation_hour_on_legacy_payment(): void
    {
        $paiement = ESBTPPaiement::factory()
            ->pour(ESBTPInscription::factory()->create())
            ->enAttente()->create();
        $paiement->status = 'validé';
        $paiement->date_validation = '2025-01-01';
        $paiement->validation_recorded_at = null;
        $paiement->saveQuietly(); // Simule uniquement un enregistrement historique.

        $paiement->update(['commentaire' => 'Vérification comptable']);
        $this->assertNull($paiement->fresh()->validation_recorded_at);
    }

    public function test_rejection_also_captures_an_exact_instant(): void
    {
        $paiement = ESBTPPaiement::factory()
            ->pour(ESBTPInscription::factory()->create())
            ->enAttente()->create();
        $paiement->update(['status' => 'rejeté', 'date_validation' => now()]);
        $this->assertNotNull($paiement->fresh()->validation_recorded_at);
    }
}
