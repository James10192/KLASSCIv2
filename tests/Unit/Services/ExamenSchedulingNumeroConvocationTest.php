<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Smoke tests sur le format du numéro de convocation
 * (sans dépendance DB — vérifie juste le pattern regex).
 */
class ExamenSchedulingNumeroConvocationTest extends TestCase
{
    public function test_format_pattern_matches(): void
    {
        $sample = 'CONV-PRESENTATION-20252026-0042';
        $this->assertMatchesRegularExpression(
            '/^CONV-[A-Z0-9]+-[A-Z0-9]+-\d{4}$/',
            $sample
        );
    }

    public function test_pv_format_pattern_matches(): void
    {
        $sample = 'PV-20252026-PRESENTATION-0042';
        $this->assertMatchesRegularExpression(
            '/^PV-[A-Z0-9]+-[A-Z0-9]+-\d{4}$/',
            $sample
        );
    }

    public function test_incrementation_seq_from_last(): void
    {
        // Simule l'extraction du dernier seq via regex utilisée par le service
        $last = 'CONV-PRES-20252026-0099';
        preg_match('/-(\d{4})$/', $last, $m);
        $this->assertSame('0099', $m[1]);
        $next = ((int) $m[1]) + 1;
        $this->assertSame(100, $next);
        $this->assertSame('0100', sprintf('%04d', $next));
    }

    public function test_seq_overflow_to_5_digits_still_works(): void
    {
        $next = 10000;
        // %04d ne tronque pas, il étend
        $this->assertSame('10000', sprintf('%04d', $next));
    }
}
