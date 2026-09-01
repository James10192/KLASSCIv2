<?php

namespace Tests\Unit\Services;

use App\Services\RelanceCalculationService;
use App\Services\SoldeEtudiant;
use Mockery;
use Tests\TestCase;

class SoldeEtudiantTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_empty_ids_return_empty_map(): void
    {
        $relances = Mockery::mock(RelanceCalculationService::class);
        $relances->shouldNotReceive('preloadForInscriptions');

        $this->assertSame([], (new SoldeEtudiant($relances))->impayes([]));
    }
}
