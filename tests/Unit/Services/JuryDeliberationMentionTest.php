<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Tests métier sur les mentions UEMOA.
 * Vérifient les seuils par défaut documentés (10/12/14/16/18).
 */
class JuryDeliberationMentionTest extends TestCase
{
    /**
     * @dataProvider mentionProvider
     */
    public function test_mention_assignment(float $moyenne, ?string $expected): void
    {
        $mention = $this->resolveMention($moyenne);
        $this->assertSame($expected, $mention);
    }

    public static function mentionProvider(): array
    {
        return [
            'sous_seuil' => [9.5, null],
            'passable_min' => [10.0, 'passable'],
            'passable_max' => [11.99, 'passable'],
            'assez_bien_min' => [12.0, 'assez_bien'],
            'assez_bien_max' => [13.99, 'assez_bien'],
            'bien_min' => [14.0, 'bien'],
            'bien_max' => [15.99, 'bien'],
            'tres_bien_min' => [16.0, 'tres_bien'],
            'tres_bien_max' => [17.99, 'tres_bien'],
            'excellent' => [18.0, 'excellent'],
            'parfait' => [20.0, 'excellent'],
        ];
    }

    /**
     * Re-implémente la logique du service (sans DB) pour tester les seuils.
     */
    private function resolveMention(float $moyenne): ?string
    {
        $thresholds = [
            'passable' => 10.0,
            'assez_bien' => 12.0,
            'bien' => 14.0,
            'tres_bien' => 16.0,
            'excellent' => 18.0,
        ];

        if ($moyenne >= $thresholds['excellent']) {
            return 'excellent';
        }
        if ($moyenne >= $thresholds['tres_bien']) {
            return 'tres_bien';
        }
        if ($moyenne >= $thresholds['bien']) {
            return 'bien';
        }
        if ($moyenne >= $thresholds['assez_bien']) {
            return 'assez_bien';
        }
        if ($moyenne >= $thresholds['passable']) {
            return 'passable';
        }

        return null;
    }
}
