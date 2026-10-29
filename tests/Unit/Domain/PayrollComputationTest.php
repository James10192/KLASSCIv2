<?php

namespace Tests\Unit\Domain;

use App\Domain\Comptabilite\Paie\PayrollComputationService;
use App\Models\ESBTPTeacher;
use App\Services\TeacherHoursService;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;

/**
 * Calcul de paie : barème ITS progressif + gains heures×taux − retenues.
 */
class PayrollComputationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function service(?TeacherHoursService $hours = null): PayrollComputationService
    {
        return new PayrollComputationService($hours ?? Mockery::mock(TeacherHoursService::class));
    }

    public function test_its_progressif_barème_par_défaut(): void
    {
        $svc = $this->service();

        $this->assertSame(0.0, $svc->computeIts(50000));           // tranche 0%
        $this->assertSame(4000.0, $svc->computeIts(100000));        // (100000-75000)*16%
        $this->assertSame(39000.0, $svc->computeIts(300000));       // 165000*16% + 60000*21%
    }

    public function test_preview_gains_heures_fois_taux_moins_retenues(): void
    {
        $hours = Mockery::mock(TeacherHoursService::class);
        $hours->shouldReceive('summary')->once()->andReturn([
            'periode'          => ['from' => '2026-06-01', 'to' => '2026-06-30'],
            'taux_realisation' => 100.0,
            'par_type'         => [[
                'type' => 'CM', 'label' => 'Cours Magistral', 'facturable' => true,
                'icon' => 'fa-x', 'style' => '', 'nb_seances' => 5, 'nb_realisees' => 5,
                'heures_planifiees' => 10.0, 'heures_realisees' => 10.0,
            ]],
        ]);

        $teacher = new ESBTPTeacher();
        $teacher->taux_horaire = 5000;
        $teacher->setRelation('tauxSeances', collect());

        $svc = $this->service($hours);
        $p = $svc->computePreview($teacher, Carbon::parse('2026-06-01'), Carbon::parse('2026-06-30'));

        // 10h × 5000 = 50 000 de base, pas de prime.
        $this->assertSame(50000.0, $p['base']);
        $this->assertSame(50000.0, $p['brut']);
        // ITS = 0 (brut < 75 000), CNPS = 50000 × 6.3% = 3150.
        $this->assertSame(0.0, $p['impot_its']);
        $this->assertSame(3150.0, $p['cnps']);
        $this->assertSame(3150.0, $p['total_retenues']);
        $this->assertSame(46850.0, $p['net']);
        // Une ligne de gain CM + une ligne de retenue CNPS.
        $this->assertCount(1, $p['gains']);
        $this->assertCount(1, $p['retenues']);
        $this->assertSame('cnps', $p['retenues'][0]['type']);
    }

    public function test_impot_its_override_manuel_respecté(): void
    {
        $hours = Mockery::mock(TeacherHoursService::class);
        $hours->shouldReceive('summary')->once()->andReturn([
            'periode' => ['from' => '2026-06-01', 'to' => '2026-06-30'],
            'taux_realisation' => 100.0,
            'par_type' => [[
                'type' => 'CM', 'label' => 'CM', 'facturable' => true,
                'icon' => 'fa-x', 'style' => '', 'nb_seances' => 1, 'nb_realisees' => 1,
                'heures_planifiees' => 20.0, 'heures_realisees' => 20.0,
            ]],
        ]);

        $teacher = new ESBTPTeacher();
        $teacher->taux_horaire = 10000; // 20h × 10000 = 200 000
        $teacher->setRelation('tauxSeances', collect());

        $svc = $this->service($hours);
        $p = $svc->computePreview($teacher, Carbon::parse('2026-06-01'), Carbon::parse('2026-06-30'), [
            'impot_its' => 12345,
            'cnps'      => 0,
            'retenues'  => [['type' => 'avance', 'libelle' => 'Avance', 'montant' => 5000]],
        ]);

        $this->assertSame(200000.0, $p['brut']);
        $this->assertSame(12345.0, $p['impot_its']);
        $this->assertSame(17345.0, $p['total_retenues']); // 12345 ITS + 5000 avance (cnps=0)
        $this->assertSame(182655.0, $p['net']);
    }
}
