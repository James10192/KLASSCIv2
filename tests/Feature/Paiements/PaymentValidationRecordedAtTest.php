<?php

namespace Tests\Feature\Paiements;

use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaymentValidationRecordedAtTest extends TestCase
{
    use DatabaseTransactions;

    public function test_exact_instant_of_validation_does_not_change_the_accounting_day(): void
    {
        Carbon::setTestNow('2026-10-15 13:47:21');
        try {
            $payment = ESBTPPaiement::factory()
                ->pour(ESBTPInscription::factory()->create())
                ->enAttente()
                ->create(['date_paiement' => '2026-10-10']);
            $this->assertNull($payment->validation_recorded_at);
            $payment->update(['status' => 'validé', 'date_validation' => now()]);
            $saved = $payment->fresh();
            $this->assertSame('2026-10-10', $saved->date_paiement->format('Y-m-d'));
            $this->assertSame('2026-10-15', $saved->date_validation->format('Y-m-d'));
            $this->assertSame('2026-10-15 13:47:21', $saved->validation_recorded_at->format('Y-m-d H:i:s'));
            $this->assertSame('15/10/2026 à 13:47', $saved->dateValidationLisible());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_legacy_date_only_payment_never_gets_fake_hour_on_unrelated_edit(): void
    {
        $payment = ESBTPPaiement::factory()
            ->pour(ESBTPInscription::factory()->create())
            ->enAttente()->create();
        $payment->status = 'validé';
        $payment->date_validation = '2025-01-01';
        $payment->validation_recorded_at = null;
        $payment->saveQuietly();

        $payment->update(['commentaire' => 'Vérification comptable']);
        $saved = $payment->fresh();
        $this->assertNull($saved->validation_recorded_at);
        $this->assertSame('01/01/2025 · heure non conservée', $saved->dateValidationLisible());
    }

    public function test_rejection_and_revalidation_are_both_stamped_when_the_status_changes(): void
    {
        $payment = ESBTPPaiement::factory()
            ->pour(ESBTPInscription::factory()->create())
            ->enAttente()->create();

        Carbon::setTestNow('2026-10-15 11:42:09');
        try {
            $payment->update(['status' => 'rejeté', 'date_validation' => now()]);
            $this->assertSame('11:42', $payment->fresh()->validation_recorded_at->format('H:i'));

            // Simule une réouverture métier explicite puis une nouvelle validation.
            $payment->update(['status' => 'en_attente']);
            Carbon::setTestNow('2026-10-15 15:36:02');
            $payment->update(['status' => 'validé', 'date_validation' => now()]);
            $this->assertSame('15:36', $payment->fresh()->validation_recorded_at->format('H:i'));
        } finally {
            Carbon::setTestNow();
        }
    }
}
