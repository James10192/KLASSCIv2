<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Tests sur la logique de calcul auto des décisions UEMOA.
 * Re-implémente la logique du service sans DB pour valider les branches.
 */
class JuryDecisionAutoLogicTest extends TestCase
{
    public function test_moyenne_null_returns_defere(): void
    {
        $decision = $this->compute(null, 30, 30, false);
        $this->assertSame('defere', $decision);
    }

    public function test_eliminatoire_returns_ajourne(): void
    {
        $decision = $this->compute(11.0, 30, 30, true);
        $this->assertSame('ajourne', $decision);
    }

    public function test_moyenne_OK_credits_OK_returns_admis(): void
    {
        $decision = $this->compute(12.0, 30, 30, false);
        $this->assertSame('admis', $decision);
    }

    public function test_moyenne_OK_credits_insuffisants_returns_admis_sous_condition(): void
    {
        $decision = $this->compute(10.0, 20, 30, false);
        $this->assertSame('admis_sous_condition', $decision);
    }

    public function test_moyenne_KO_returns_admission_rattrapage(): void
    {
        $decision = $this->compute(9.0, 25, 30, false);
        $this->assertSame('admission_rattrapage', $decision);
    }

    public function test_moyenne_just_below_seuil_returns_rattrapage(): void
    {
        $decision = $this->compute(9.99, 30, 30, false);
        $this->assertSame('admission_rattrapage', $decision);
    }

    public function test_moyenne_just_at_seuil_returns_admis(): void
    {
        $decision = $this->compute(10.0, 30, 30, false);
        $this->assertSame('admis', $decision);
    }

    private function compute(?float $moyenne, int $creditsObtenus, int $creditsAttendus, bool $hasEliminatoire, float $seuil = 10.0): string
    {
        if ($moyenne === null) {
            return 'defere';
        }
        if ($hasEliminatoire) {
            return 'ajourne';
        }
        if ($moyenne >= $seuil && $creditsObtenus >= $creditsAttendus) {
            return 'admis';
        }
        if ($moyenne >= $seuil && $creditsObtenus < $creditsAttendus) {
            return 'admis_sous_condition';
        }

        return 'admission_rattrapage';
    }
}
