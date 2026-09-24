<?php

namespace Tests\Feature\Analytics;

use App\Domain\Analytics\Quality\PaymentQualityQuery;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PaymentQualityQueryTest extends TestCase
{
    use RefreshDatabase;

    private int $etudiantId;
    private int $anneeId;
    private int $awa;
    private int $koffi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->etudiantId = ESBTPEtudiant::factory()->create()->id;
        $this->anneeId = ESBTPAnneeUniversitaire::factory()->create()->id;
        $this->awa = User::factory()->create()->id;
        $this->koffi = User::factory()->create()->id;
    }

    /** @test */
    public function entries_are_grouped_by_day_and_account_with_old_payments_counted(): void
    {
        $this->payment('2026-05-06 10:00:00', '2026-01-15', $this->awa);
        $this->payment('2026-05-06 11:00:00', '2026-02-10', $this->awa);
        $this->payment('2026-05-06 15:00:00', '2026-05-06', $this->awa);
        $this->payment('2026-05-06 16:00:00', '2026-05-06', $this->koffi);
        $this->payment('2025-01-01 09:00:00', '2025-01-01', $this->awa); // hors fenetre

        $groups = collect((new PaymentQualityQuery())->entryGroups(CarbonImmutable::parse('2026-01-01'), 7))
            ->keyBy(fn ($g) => $g['jour'] . '#' . $g['user_id']);

        $this->assertCount(2, $groups);
        $this->assertSame(3, $groups['2026-05-06#' . $this->awa]['nombre']);
        $this->assertSame(2, $groups['2026-05-06#' . $this->awa]['anciens']);
        $this->assertSame(0, $groups['2026-05-06#' . $this->koffi]['anciens']);
    }

    /** @test */
    public function last_entry_ignores_deleted_payments(): void
    {
        $this->payment('2026-07-31 16:00:00', '2026-07-31', $this->awa);
        $deleted = $this->payment('2026-09-01 09:00:00', '2026-09-01', $this->awa);
        DB::table('esbtp_paiements')->where('id', $deleted)->update(['deleted_at' => now()]);

        $this->assertSame('2026-07-31', (new PaymentQualityQuery())->lastEntryAt()->toDateString());
    }

    /** @test */
    public function reliability_route_is_registered(): void
    {
        $this->assertTrue(Route::has('esbtp.comptabilite.analytics.fiabilite'));
    }

    private function payment(string $createdAt, string $paidOn, int $by): int
    {
        $paiement = ESBTPPaiement::factory()->create([
            'etudiant_id' => $this->etudiantId,
            'annee_universitaire_id' => $this->anneeId,
            'date_paiement' => $paidOn,
            'status' => 'validé',
        ]);
        // created_at / created_by sont poses par l'application : on les force
        // ici pour reproduire une saisie a une date donnee.
        DB::table('esbtp_paiements')->where('id', $paiement->id)->update(['created_at' => $createdAt, 'created_by' => $by]);

        return $paiement->id;
    }
}
