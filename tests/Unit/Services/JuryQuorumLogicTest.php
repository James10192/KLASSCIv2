<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class JuryQuorumLogicTest extends TestCase
{
    public function test_quorum_ok_with_president_secretaire_assesseur(): void
    {
        $result = $this->checkQuorum([
            ['role' => 'president', 'present' => true],
            ['role' => 'secretaire', 'present' => true],
            ['role' => 'assesseur', 'present' => true],
        ]);
        $this->assertTrue($result['ok']);
        $this->assertSame(3, $result['present']);
    }

    public function test_quorum_ko_no_president(): void
    {
        $result = $this->checkQuorum([
            ['role' => 'secretaire', 'present' => true],
            ['role' => 'assesseur', 'present' => true],
            ['role' => 'assesseur', 'present' => true],
        ]);
        $this->assertFalse($result['ok']);
        $this->assertContains('Président absent', $result['reasons']);
    }

    public function test_quorum_ko_below_min(): void
    {
        $result = $this->checkQuorum([
            ['role' => 'president', 'present' => true],
        ]);
        $this->assertFalse($result['ok']);
    }

    public function test_quorum_ignores_absent_members(): void
    {
        $result = $this->checkQuorum([
            ['role' => 'president', 'present' => true],
            ['role' => 'secretaire', 'present' => false],
            ['role' => 'assesseur', 'present' => true],
        ]);
        $this->assertTrue($result['ok']); // 2 présents, min 2, président présent
    }

    private function checkQuorum(array $membres, int $min = 2, int $minAssesseurs = 1): array
    {
        $present = array_filter($membres, fn ($m) => $m['present'] === true);
        $presentCount = count($present);
        $hasPresident = (bool) array_filter($present, fn ($m) => $m['role'] === 'president');
        $assesseurs = count(array_filter($present, fn ($m) => $m['role'] === 'assesseur'));

        $ok = true;
        $reasons = [];

        if ($presentCount < $min) {
            $ok = false;
            $reasons[] = 'Quorum non atteint';
        }
        if (! $hasPresident) {
            $ok = false;
            $reasons[] = 'Président absent';
        }

        return ['ok' => $ok, 'present' => $presentCount, 'reasons' => $reasons];
    }
}
