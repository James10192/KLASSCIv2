<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Tests sur la logique de recalcul max|replace post-rattrapage UEMOA.
 */
class RattrapageRecalculLogicTest extends TestCase
{
    public function test_max_mode_garde_la_meilleure(): void
    {
        $final = $this->recompute(8.0, 12.0, false);
        $this->assertSame(12.0, $final);
    }

    public function test_max_mode_garde_normale_si_meilleure(): void
    {
        $final = $this->recompute(14.0, 11.0, false);
        $this->assertSame(14.0, $final);
    }

    public function test_replace_mode_remplace_toujours_par_rattrapage(): void
    {
        $final = $this->recompute(14.0, 11.0, true);
        $this->assertSame(11.0, $final);
    }

    public function test_replace_mode_remplace_meme_si_pire(): void
    {
        $final = $this->recompute(8.0, 5.0, true);
        $this->assertSame(5.0, $final);
    }

    public function test_normale_null_max_mode_uses_rattrapage(): void
    {
        $final = $this->recompute(null, 12.0, false);
        $this->assertSame(12.0, $final);
    }

    private function recompute(?float $normale, float $rattrapage, bool $replace): float
    {
        if ($replace) {
            return $rattrapage;
        }

        return max((float) ($normale ?? 0), $rattrapage);
    }
}
