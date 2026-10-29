<?php

namespace Tests\Unit\Domain\Analytics;

use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\DTOs\StudentRiskFeatures;
use App\Domain\Analytics\Predictors\DefaultRiskPredictor;
use App\Domain\Analytics\Repositories\StudentRiskRepository;
use App\Services\EcheancierReadinessService;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Couverture des garde-fous robustesse de Phase 2 chantier analytics :
 *  - Cohorte trop petite → unavailable (pas de score factice sur 3 étudiants)
 *  - Cohorte 100% saturée → auto-calibration (top X% reste haut, le reste descend en moyen)
 *  - Étudiant qui paye en avance → score < celui qui n'a rien payé même si overdue identique
 */
class DefaultRiskPredictorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Construit un set d'overrides complet pour bypasser SettingsHelper (qui touche la DB).
     */
    private function overrides(array $extra = []): array
    {
        return array_merge([
            'weight.solde'      => DefaultRiskPredictor::DEFAULT_WEIGHT_SOLDE,
            'weight.retard'     => DefaultRiskPredictor::DEFAULT_WEIGHT_RETARD,
            'weight.engagement' => DefaultRiskPredictor::DEFAULT_WEIGHT_ENGAGEMENT,
            'weight.montant'    => DefaultRiskPredictor::DEFAULT_WEIGHT_MONTANT,
            'weight.velocity'   => DefaultRiskPredictor::DEFAULT_WEIGHT_VELOCITY,
            'bias'              => DefaultRiskPredictor::DEFAULT_BIAS,
            'threshold_high'    => DefaultRiskPredictor::DEFAULT_THRESHOLD_HIGH,
            'threshold_medium'  => DefaultRiskPredictor::DEFAULT_THRESHOLD_MEDIUM,
            'top_n'             => (float) DefaultRiskPredictor::DEFAULT_TOP_N,
            'min_cohort_size'   => (float) DefaultRiskPredictor::DEFAULT_MIN_COHORT_SIZE,
            'auto_calibrate'    => DefaultRiskPredictor::DEFAULT_AUTO_CALIBRATE ? 1.0 : 0.0,
        ], $extra);
    }

    public function test_returns_unavailable_when_cohort_below_min_size(): void
    {
        $repository = Mockery::mock(StudentRiskRepository::class);
        $readiness = Mockery::mock(EcheancierReadinessService::class);
        $readiness->shouldReceive('unavailableReason')->andReturn(null);
        $readiness->shouldReceive('mode')->andReturn(EcheancierReadinessService::MODE_CONFIGURED);
        $readiness->shouldReceive('noteForMode')->andReturn(null);

        $repository->shouldReceive('activeStudents')
            ->andReturn($this->fakeStudents(5)); // < 10 (DEFAULT_MIN_COHORT_SIZE)

        $predictor = new DefaultRiskPredictor($repository, $readiness, $this->overrides([
            'min_cohort_size' => 10.0,
            'auto_calibrate'  => 0.0,
        ]));

        $result = $predictor->predict(AnalyticsContext::empty());

        $this->assertFalse($result->isAvailable());
        $this->assertStringContainsString('Cohorte trop petite', $result->explanation[0]);
    }

    public function test_auto_calibration_kicks_in_when_saturated(): void
    {
        $repository = Mockery::mock(StudentRiskRepository::class);
        $readiness = Mockery::mock(EcheancierReadinessService::class);
        $readiness->shouldReceive('unavailableReason')->andReturn(null);
        $readiness->shouldReceive('mode')->andReturn(EcheancierReadinessService::MODE_CONFIGURED);
        $readiness->shouldReceive('noteForMode')->andReturn(null);

        // 50 étudiants tous en retard MAIS avec gradient (overdue de 600k à 1M, retard 100→195j)
        // → tous au-dessus du seuil par défaut, mais scores différents pour permettre la discrimination calibrée
        $students = [];
        for ($i = 0; $i < 50; $i++) {
            $students[] = $this->fakeStudent($i + 1, [
                'overdue'  => 600_000 + ($i * 8_000),
                'expected' => 1_000_000,
                'paid_due' => 1_000_000 - (600_000 + ($i * 8_000)),
                'retard'   => 100 + ($i * 2),
            ]);
        }
        $repository->shouldReceive('activeStudents')->andReturn($students);

        $predictor = new DefaultRiskPredictor($repository, $readiness, $this->overrides([
            'min_cohort_size' => 10.0,
            'auto_calibrate'  => 1.0,
        ]));

        $result = $predictor->predict(AnalyticsContext::empty());

        $this->assertTrue($result->isAvailable());
        $this->assertTrue((bool) $result->metadata['auto_calibrated'], 'Auto-calibration should trigger on saturated cohort');
        // Le seuil effectif doit être supérieur au seuil par défaut
        $this->assertGreaterThan(
            $result->metadata['thresholds']['high'],
            $result->metadata['thresholds']['high_effective'],
        );
        // Pas tous à haut, sinon la calibration n'a pas marché
        $this->assertLessThan(50, $result->metadata['buckets']['haut']);
    }

    public function test_fast_payer_scores_lower_than_no_payer(): void
    {
        $repository = Mockery::mock(StudentRiskRepository::class);
        $readiness = Mockery::mock(EcheancierReadinessService::class);
        $readiness->shouldReceive('unavailableReason')->andReturn(null);
        $readiness->shouldReceive('mode')->andReturn(EcheancierReadinessService::MODE_CONFIGURED);
        $readiness->shouldReceive('noteForMode')->andReturn(null);

        // 1 fast payer (a payé 90% à date, retard 50j) + 1 no payer (0% payé, retard 50j)
        // + 18 autres étudiants moyens pour atteindre min cohort
        $students = array_merge(
            [$this->fakeStudent(1, ['overdue' => 100_000, 'expected' => 1_000_000, 'paid_due' => 900_000, 'retard' => 50])],
            [$this->fakeStudent(2, ['overdue' => 1_000_000, 'expected' => 1_000_000, 'paid_due' => 0, 'retard' => 50])],
            $this->fakeStudents(18, ['overdue' => 500_000, 'expected' => 1_000_000, 'paid_due' => 500_000, 'retard' => 50])
        );

        $repository->shouldReceive('activeStudents')->andReturn($students);

        $predictor = new DefaultRiskPredictor($repository, $readiness, $this->overrides([
            'min_cohort_size' => 10.0,
            'auto_calibrate'  => 0.0,
        ]));

        $result = $predictor->predict(AnalyticsContext::empty());

        $top = collect($result->metadata['top_at_risk'])->keyBy('inscription_id');
        $this->assertLessThan(
            $top[2]['score'],
            $top[1]['score'],
            'Fast payer (id=1) should score lower than no-payer (id=2)',
        );
    }

    /**
     * @return StudentRiskFeatures[]
     */
    private function fakeStudents(int $n, array $overrides = []): array
    {
        $out = [];
        for ($i = 1; $i <= $n; $i++) {
            $out[] = $this->fakeStudent($i, $overrides);
        }
        return $out;
    }

    private function fakeStudent(int $id, array $overrides = []): StudentRiskFeatures
    {
        $expected = (float) ($overrides['expected'] ?? 1_000_000);
        $overdue = (float) ($overrides['overdue'] ?? 700_000);
        $paidDue = (float) ($overrides['paid_due'] ?? max(0.0, $expected - $overdue));
        $retard = (int) ($overrides['retard'] ?? 100);

        return new StudentRiskFeatures(
            inscriptionId: $id,
            etudiantId: $id,
            etudiantNom: "Etudiant {$id}",
            classeId: 1,
            classeNom: 'Classe Test',
            totalAttendu: $expected,
            totalPaye: $paidDue,
            soldeRestant: max(0.0, $expected - $paidDue),
            ratioPaye: $expected > 0 ? min(1.0, $paidDue / $expected) : 0.0,
            joursRetard: $retard,
            nbPaiements: $paidDue > 0 ? 1 : 0,
            expectedDueToDate: $expected,
            paidDueToDate: $paidDue,
            overdueAmount: $overdue,
        );
    }
}
