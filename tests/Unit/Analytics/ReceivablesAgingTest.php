<?php

namespace Tests\Unit\Analytics;

use App\Domain\Analytics\Aging\ReceivablesAging;
use App\Domain\Analytics\DTOs\StudentRiskFeatures;
use PHPUnit\Framework\TestCase;

class ReceivablesAgingTest extends TestCase
{
    /** @test */
    public function students_are_placed_in_the_right_bucket_with_their_overdue_amount(): void
    {
        $aging = (new ReceivablesAging())->build([
            $this->student(1, retard: 0, enRetard: 0, reste: 100000),
            $this->student(2, retard: 12, enRetard: 50000, reste: 150000),
            $this->student(3, retard: 45, enRetard: 80000, reste: 80000),
            $this->student(4, retard: 61, enRetard: 20000, reste: 20000),
            $this->student(5, retard: 200, enRetard: 250000, reste: 300000),
            $this->student(6, retard: 90, enRetard: 10000, reste: 10000),
        ], false);

        $byKey = array_column($aging['tranches'], null, 'tranche');
        $this->assertSame(1, $byKey['a_jour']['etudiants']);
        $this->assertSame(50000.0, $byKey['1_30']['montant_en_retard']);
        $this->assertSame(80000.0, $byKey['31_60']['montant_en_retard']);
        $this->assertSame(2, $byKey['61_90']['etudiants'], '61 et 90 jours sont dans la meme tranche');
        $this->assertSame(250000.0, $byKey['plus_90']['montant_en_retard']);
        $this->assertSame(410000.0, $aging['total_en_retard']);
        $this->assertSame(660000.0, $aging['total_restant_du']);
        $this->assertSame(61.0, $aging['part_plus_90_pct']);
        $this->assertNull($aging['avertissement']);
    }

    /** @test */
    public function a_student_with_days_but_nothing_overdue_is_up_to_date(): void
    {
        $aging = (new ReceivablesAging())->build([$this->student(1, retard: 30, enRetard: 0, reste: 5000)], false);

        $this->assertSame(1, array_column($aging['tranches'], null, 'tranche')['a_jour']['etudiants']);
        $this->assertSame(0.0, $aging['total_en_retard']);
    }

    /** @test */
    public function fallback_mode_is_stated_rather_than_hidden(): void
    {
        $aging = (new ReceivablesAging())->build([$this->student(1, retard: 150, enRetard: 400000, reste: 400000)], true);

        $this->assertTrue($aging['mode_degrade']);
        $this->assertStringContainsString("Aucun échéancier", $aging['avertissement']);
    }

    /** @test */
    public function an_empty_cohort_returns_zeros(): void
    {
        $aging = (new ReceivablesAging())->build([], false);

        $this->assertSame(0, $aging['etudiants']);
        $this->assertSame(0.0, $aging['part_plus_90_pct']);
        $this->assertCount(5, $aging['tranches']);
    }

    private function student(int $id, int $retard, float $enRetard, float $reste): StudentRiskFeatures
    {
        return new StudentRiskFeatures(
            inscriptionId: $id, etudiantId: $id, etudiantNom: 'Etudiant ' . $id, classeId: 1, classeNom: 'BTS 1',
            totalAttendu: $reste + 100000, totalPaye: 100000, soldeRestant: $reste, ratioPaye: 0.5,
            joursRetard: $retard, nbPaiements: 1, overdueAmount: $enRetard,
        );
    }
}
