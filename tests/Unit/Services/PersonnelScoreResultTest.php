<?php

namespace Tests\Unit\Services;

use App\Services\Scoring\PersonnelScoreResult;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PersonnelScoreResultTest extends TestCase
{
    public function test_legacy_measurable_result_can_omit_evidence_metadata(): void
    {
        $result = new PersonnelScoreResult('legacy', 'Historique', 80, 10);

        $this->assertSame(PersonnelScoreResult::STATE_MEASURABLE, $result->state);
        $this->assertNull($result->numerator);
        $this->assertNull($result->evidenceHash);
    }

    /**
     * @dataProvider invalidResultProvider
     */
    public function test_invalid_results_are_rejected(array $arguments): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PersonnelScoreResult(...$arguments);
    }

    public function invalidResultProvider(): array
    {
        return [
            'score supérieur à 100' => [['dimension', 'Libellé', 101, 10]],
            'poids négatif' => [['dimension', 'Libellé', 50, -1]],
            'état inconnu' => [['dimension', 'Libellé', 50, 10, [], [], 'unknown']],
            'preuve partielle' => [['dimension', 'Libellé', 50, 10, [], [], 'measurable', 1]],
            'numérateur supérieur' => [['dimension', 'Libellé', 50, 10, [], [], 'measurable', 2, 1, 1.0, 1.0, str_repeat('a', 64)]],
            'preuve sans empreinte' => [['dimension', 'Libellé', 50, 10, [], [], 'measurable', 1, 2, 1.0, 1.0]],
            'ratio hors limites' => [['dimension', 'Libellé', 50, 10, [], [], 'measurable', null, null, 1.1, 1.0]],
            'non applicable scoré' => [['dimension', 'Libellé', 1, 10, [], [], 'non_applicable', 0, 0]],
            'non applicable sans compteurs' => [['dimension', 'Libellé', 0, 10, [], [], 'non_applicable']],
            'données insuffisantes avec compteurs' => [['dimension', 'Libellé', 0, 10, [], [], 'insufficient_data', 0, 0]],
            'empreinte invalide' => [['dimension', 'Libellé', 50, 10, [], [], 'measurable', null, null, null, null, 'invalid']],
        ];
    }
}
