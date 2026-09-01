<?php

namespace Tests\Feature\Caisse;

use App\Enums\CashSessionStatus;
use App\Exceptions\CaisseCloturee;
use App\Models\ESBTPCashSession;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\Caisse\CashSessionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CashSessionServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_close_locks_the_till_and_blocks_cash(): void
    {
        $user = User::factory()->create();
        $service = app(CashSessionService::class);

        $session = $service->close($user, 15000, 'Tiroir compté');

        $this->assertTrue($session->isLocked());
        $this->assertSame(CashSessionStatus::CLOSED, $session->status);
        $this->assertSame('15000.00', (string) $session->counted_amount);
        $this->assertSame('0.00', (string) $session->expected_amount);
        $this->assertSame('15000.00', (string) $session->variance);

        $this->expectException(CaisseCloturee::class);
        $service->assertEspecesAutorisees($user, 'espèces');
    }

    public function test_wave_is_allowed_after_close(): void
    {
        $user = User::factory()->create();
        $service = app(CashSessionService::class);
        $service->close($user, 0);

        $service->assertEspecesAutorisees($user, 'wave');
        $this->assertTrue(true);
    }

    public function test_auto_close_stale_open_sessions(): void
    {
        Carbon::setTestNow('2026-09-02 09:00:00');
        $user = User::factory()->create();

        $stale = ESBTPCashSession::query()->create([
            'cashier_user_id' => $user->id,
            'business_date' => '2026-09-01',
            'status' => CashSessionStatus::OPEN,
            'opened_at' => '2026-09-01 08:00:00',
        ]);

        app(CashSessionService::class)->snapshot($user);

        $stale->refresh();
        $this->assertSame(CashSessionStatus::AUTO_CLOSED, $stale->status);
        $this->assertNotNull($stale->closed_at);
    }

    public function test_expected_amount_counts_only_validated_cash(): void
    {
        $user = User::factory()->create();
        ESBTPPaiement::factory()->create([
            'created_by' => $user->id,
            'montant' => 10000,
            'mode_paiement' => 'espèces',
            'status' => 'validé',
            'created_at' => now(),
        ]);
        ESBTPPaiement::factory()->create([
            'created_by' => $user->id,
            'montant' => 5000,
            'mode_paiement' => 'wave',
            'status' => 'validé',
            'created_at' => now(),
        ]);
        ESBTPPaiement::factory()->create([
            'created_by' => $user->id,
            'montant' => 2000,
            'mode_paiement' => 'espèces',
            'status' => 'en attente',
            'created_at' => now(),
        ]);

        $aggregat = app(CashSessionService::class)->aggregat($user->id, now()->toDateString());

        $this->assertSame(15000.0, $aggregat['total']);
        $this->assertSame(10000.0, $aggregat['especes']);
        $this->assertSame(2, $aggregat['count']);
    }
}
