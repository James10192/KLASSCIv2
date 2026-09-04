<?php

namespace Tests\Unit\Services;

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

    /**
     * Le defaut ne doit rien changer au deploiement.
     *
     * Ce test remplace un precedent qui verifiait la presence d ecouteurs sur le
     * modele Paiement : il en existait DEJA avant, donc il passait avant le commit
     * et serait passe apres le retrait de ce qu il pretendait garder. Un filet qui
     * ne peut pas tomber n est pas un filet.
     *
     * Ce qui compte reellement : sans reglage, la memorisation est ETEINTE et le
     * calcul reste integral, exactement comme avant. Une ecole l allume quand elle
     * le decide.
     */
    public function test_la_memorisation_est_eteinte_sans_reglage(): void
    {
        $this->assertFalse(
            app(AnalyticsScanCache::class)->enabled(),
            'La memorisation s activerait d elle-meme au deploiement, sur six ecoles.'
        );
    }
}
