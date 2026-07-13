<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AcademicPeriodNormalizerTest extends TestCase
{
    /** @dataProvider periodProvider */
    public function test_it_normalizes_supported_period_aliases(string|int $input, string $expected): void
    {
        $this->assertSame($expected, (new AcademicPeriodNormalizer)->normalize($input));
    }

    public function periodProvider(): array
    {
        return [
            [1, 'semestre1'],
            ['S1', 'semestre1'],
            ['semestre 1', 'semestre1'],
            ['semester_2', 'semestre2'],
            ['année', 'annuel'],
            ['ANNÉE', 'annuel'],
            ['annual', 'annuel'],
        ];
    }

    public function test_it_rejects_unknown_periods(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new AcademicPeriodNormalizer)->normalize('trimestre3');
    }

    public function test_it_supports_lmd_semesters_and_previous_periods(): void
    {
        $normalizer = new AcademicPeriodNormalizer;

        $this->assertSame('semestre10', $normalizer->normalize('Semestre 10'));
        $this->assertSame(10, $normalizer->semesterNumber('S10'));
        $this->assertSame('semestre9', $normalizer->previous('S10'));
        $this->assertNull($normalizer->previous('S1'));
    }
}
