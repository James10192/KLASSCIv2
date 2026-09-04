<?php

namespace Tests\Unit\Services;

use App\Models\ESBTPPaiement;
use App\Observers\ESBTPPaiementAnalyticsScanObserver;
use App\Services\Analytics\AnalyticsScanCache;
use App\Services\Analytics\CashFlowProjectionService;
use App\Services\Analytics\RecouvrementGapService;
use Tests\TestCase;

/**
 * Les deux balayages ne partagent leur mémoire que si le conteneur les résout
 * bien, et que si l'observateur d'invalidation est effectivement branché. Ces
 * deux points cassent en silence : rien n'échoue, le balayage est juste rejoué,
 * ou la donnée reste périmée. D'où ce filet, qui ne demande aucune base.
 */
class AnalyticsScanWiringTest extends TestCase
{
    public function test_les_services_de_balayage_se_resolvent(): void
    {
        $this->assertInstanceOf(RecouvrementGapService::class, app(RecouvrementGapService::class));
        $this->assertInstanceOf(CashFlowProjectionService::class, app(CashFlowProjectionService::class));
        $this->assertInstanceOf(AnalyticsScanCache::class, app(AnalyticsScanCache::class));
    }

    public function test_une_seule_instance_par_requete(): void
    {
        // Sinon le contrôleur et AnomalyDetector rebalayeraient chacun de leur côté.
        $this->assertSame(app(RecouvrementGapService::class), app(RecouvrementGapService::class));
        $this->assertSame(app(CashFlowProjectionService::class), app(CashFlowProjectionService::class));
    }

    public function test_l_observateur_d_invalidation_est_branche(): void
    {
        $dispatcher = ESBTPPaiement::getEventDispatcher();

        foreach (['created', 'updated', 'deleted'] as $evenement) {
            $ecouteurs = $dispatcher->getListeners('eloquent.' . $evenement . ': ' . ESBTPPaiement::class);

            $this->assertNotEmpty(
                $ecouteurs,
                "Aucun écouteur sur eloquent.{$evenement} : l'invalidation ne partirait jamais.",
            );
        }

        $this->assertTrue(
            class_exists(ESBTPPaiementAnalyticsScanObserver::class),
            'Observateur introuvable.',
        );
    }
}
