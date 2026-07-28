<?php

namespace Tests\Unit\Services;

use App\Services\AppreciationScaleService;
use PHPUnit\Framework\TestCase;

class AppreciationScaleServiceTest extends TestCase
{
    public function testCustomBtsScaleUsesConfiguredBoundaries(): void
    {
        $scale = [
            ['min' => 18, 'max' => 20, 'label' => 'Excellent'],
            ['min' => 16, 'max' => 17.99, 'label' => 'très Bien'],
            ['min' => 14, 'max' => 15.99, 'label' => 'Bien'],
            ['min' => 12, 'max' => 13.99, 'label' => 'Assez-bien'],
            ['min' => 9.99, 'max' => 11.99, 'label' => 'Passable'],
            ['min' => 7, 'max' => 9.98, 'label' => 'Insuffisant'],
            ['min' => 1, 'max' => 6.99, 'label' => 'Mediocre'],
            ['min' => 0, 'max' => 0.99, 'label' => 'nul ou mal'],
        ];

        $service = new AppreciationScaleService(
            static fn (string $key, mixed $default = null): mixed => $key === AppreciationScaleService::BTS_SETTING_KEY
                ? json_encode($scale, JSON_THROW_ON_ERROR)
                : $default
        );

        self::assertSame('nul ou mal', $service->labelFor(0, 'bts'));
        self::assertSame('Mediocre', $service->labelFor(1, 'bts'));
        self::assertSame('Mediocre', $service->labelFor(6.99, 'bts'));
        self::assertSame('Insuffisant', $service->labelFor(7, 'bts'));
        self::assertSame('Insuffisant', $service->labelFor(9.98, 'bts'));
        self::assertSame('Passable', $service->labelFor(9.99, 'bts'));
        self::assertSame('Assez-bien', $service->labelFor(13.99, 'bts'));
        self::assertSame('Excellent', $service->labelFor(18, 'bts'));
    }

    public function testToneForConfiguredBtsLabels(): void
    {
        $service = new AppreciationScaleService;

        self::assertSame('danger', $service->toneFor('nul-ou-mal'));
        self::assertSame('danger', $service->toneFor('mediocre'));
        self::assertSame('warning', $service->toneFor('insuffisant'));
        self::assertSame('primary', $service->toneFor('passable'));
        self::assertSame('success', $service->toneFor('excellent'));
        self::assertSame('neutral', $service->toneFor('default'));
    }

    public function testRejectsOverlappingRanges(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new AppreciationScaleService())->normalizeScale([
            ['min' => 0, 'max' => 10, 'label' => 'A'],
            ['min' => 10, 'max' => 20, 'label' => 'B'],
        ]);
    }

    public function testLmdScaleCanBeResolvedFromLegacyThresholds(): void
    {
        $legacy = [
            'lmd_mention_p_threshold' => 9,
            'lmd_mention_ab_threshold' => 11,
            'lmd_mention_b_threshold' => 13,
            'lmd_mention_tb_threshold' => 15,
            'lmd_mention_excellent_threshold' => 17,
        ];

        $service = new AppreciationScaleService(
            static fn (string $key, mixed $default = null): mixed => $legacy[$key] ?? $default
        );

        self::assertSame('Insuffisant', $service->labelFor(8.99, 'lmd'));
        self::assertSame('Passable', $service->labelFor(9, 'lmd'));
        self::assertSame('Assez Bien', $service->labelFor(11, 'lmd'));
        self::assertSame('Très Bien', $service->labelFor(15, 'lmd'));
        self::assertSame('Excellent', $service->labelFor(17, 'lmd'));
    }
}
