<?php

namespace Tests\Unit\Domain\Analytics;

use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\Predictors\CashFlowPredictor;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Services\Analytics\CashFlowProjectionService;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * La pondération échéancier / historique de CashFlowPredictor n'est plus un
 * littéral enfoui dans predict() : elle vit dans deux constantes publiques et
 * la pondération réellement appliquée est exposée dans metadata['weights'],
 * pour que les écrans l'affichent au lieu de la recopier.
 */
class CashFlowPredictorWeightsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @return array<int, array{month:int, value:float}> */
    private function historique(int $mois, float $valeur): array
    {
        $serie = [];
        for ($i = 0; $i < $mois; $i++) {
            $serie[] = ['month' => ($i % 12) + 1, 'value' => $valeur];
        }

        return $serie;
    }

    private function predicteur(array $historique, float $echeancier): CashFlowPredictor
    {
        $repo = Mockery::mock(AnalyticsRepository::class);
        $repo->shouldReceive('monthlyRevenue')->andReturn($historique);

        $projection = Mockery::mock(CashFlowProjectionService::class);
        $projection->shouldReceive('nextMonthRevenue')->andReturn($echeancier);

        return new CashFlowPredictor($repo, $projection);
    }

    public function test_les_deux_sources_donnent_la_ponderation_des_constantes(): void
    {
        $resultat = $this->predicteur($this->historique(12, 1_000_000.0), 2_000_000.0)
            ->predict(new AnalyticsContext(null, null, null, null));

        $this->assertSame(
            ['echeancier' => CashFlowPredictor::POIDS_ECHEANCIER, 'historique' => CashFlowPredictor::POIDS_HISTORIQUE],
            $resultat->metadata['weights']
        );
        $this->assertEqualsWithDelta(0.8, CashFlowPredictor::POIDS_ECHEANCIER, 1e-9);
        $this->assertEqualsWithDelta(0.2, CashFlowPredictor::POIDS_HISTORIQUE, 1e-9);

        // Sur une série plate, la prévision saisonnière vaut la valeur mensuelle :
        // la prévision est donc exactement la somme pondérée.
        $this->assertEqualsWithDelta(0.8 * 2_000_000 + 0.2 * 1_000_000, $resultat->value, 1.0);
    }

    public function test_echeancier_seul_quand_l_historique_manque(): void
    {
        $resultat = $this->predicteur($this->historique(2, 500_000.0), 750_000.0)
            ->predict(new AnalyticsContext(null, null, null, null));

        $this->assertSame(['echeancier' => 1.0, 'historique' => 0.0], $resultat->metadata['weights']);
        $this->assertEqualsWithDelta(750_000.0, $resultat->value, 0.01);
    }

    public function test_historique_seul_quand_aucun_echeancier(): void
    {
        $resultat = $this->predicteur($this->historique(12, 300_000.0), 0.0)
            ->predict(new AnalyticsContext(null, null, null, null));

        $this->assertSame(['echeancier' => 0.0, 'historique' => 1.0], $resultat->metadata['weights']);
        $this->assertEqualsWithDelta(300_000.0, $resultat->value, 1.0);
    }
}
