<?php

namespace Tests\Unit\Services;

use App\Services\BulletinSectionSummary;
use PHPUnit\Framework\TestCase;

class BulletinSectionSummaryTest extends TestCase
{
    public function test_summarize_puts_average_in_moyenne_and_sums_the_rest(): void
    {
        $summary = (new BulletinSectionSummary)->forSection([
            (object) ['matiere_id' => 1, 'moyenne' => 10, 'coefficient' => 2],
            (object) ['matiere_id' => 2, 'moyenne' => 15, 'coefficient' => 4],
        ], [
            1 => ['total_heures' => 3],
            2 => ['total_heures' => 1],
        ], null);

        $this->assertEqualsWithDelta(13.333, $summary['moyenne'], 0.01);
        $this->assertSame(6.0, $summary['coefficient']);
        $this->assertSame(80.0, $summary['weighted']);
        $this->assertSame(4.0, $summary['absences']);
        $this->assertNull($summary['rang']);
    }

    public function test_official_average_wins_over_recomputed(): void
    {
        $summary = (new BulletinSectionSummary)->forSection([
            (object) ['matiere_id' => 1, 'moyenne' => 10, 'coefficient' => 2],
        ], [], 13.57);

        $this->assertSame(13.57, $summary['moyenne']);
    }

    public function test_rank_counts_strictly_higher_averages(): void
    {
        $this->assertSame(1, BulletinSectionSummary::rankAmong([1 => 14.0, 2 => 12.0], 14.0));
        $this->assertSame(2, BulletinSectionSummary::rankAmong([1 => 16.0, 2 => 14.0, 3 => 14.0], 14.0));
        $this->assertSame(3, BulletinSectionSummary::rankAmong([1 => 16.0, 2 => 15.0, 3 => 10.0], 10.0));
    }
}
