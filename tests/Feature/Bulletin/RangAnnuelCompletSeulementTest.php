<?php

namespace Tests\Feature\Bulletin;

use App\Models\ESBTPEtudiant;
use App\Services\BulletinService;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use App\Services\RankingService;
use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RangAnnuelCompletSeulementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_un_snapshot_annuel_incomplet_ne_recoit_pas_de_rang_annuel(): void
    {
        $complet = ESBTPEtudiant::factory()->create();
        $partiel = ESBTPEtudiant::factory()->create();
        $ids = [$complet->id, $partiel->id];

        $snapshots = Mockery::mock(BtsCurrentResultSnapshotService::class);
        $snapshots->shouldReceive('getPeriodeSnapshotsPourCohorte')
            ->once()
            ->with($ids, 10, 3, 'annuel')
            ->andReturn([
                $complet->id => [
                    'state' => 'annual_complete',
                    'raw_total' => 14.0,
                    'effective_total' => 14.0,
                    'attendance_note' => 0.0,
                ],
                $partiel->id => [
                    'state' => 'annual_incomplete',
                    'raw_total' => 20.0,
                    'effective_total' => 20.0,
                    'attendance_note' => 0.0,
                ],
            ]);

        $bulletins = Mockery::mock(BulletinService::class);
        $bulletins->shouldReceive('isAttendanceNoteEnabled')->once()->andReturn(false);

        $cohorte = Mockery::mock(BtsClassCohortCounter::class);
        $cohorte->shouldReceive('etudiantIdsPourPeriode')
            ->once()
            ->with(10, 3, 'annuel')
            ->andReturn($ids);

        $resultat = (new RankingService($snapshots, $bulletins, $cohorte))
            ->calculerRangsClasse(10, 3, 'annuel');

        $ligneComplete = $resultat['rows']->firstWhere('etudiant_id', $complet->id);
        $lignePartielle = $resultat['rows']->firstWhere('etudiant_id', $partiel->id);

        $this->assertSame(1, $ligneComplete['rang']);
        $this->assertNull($lignePartielle['rang']);
        $this->assertSame(1, $resultat['total']);
    }
}
