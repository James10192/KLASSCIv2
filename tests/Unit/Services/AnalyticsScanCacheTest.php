<?php

namespace Tests\Unit\Services;

use App\Services\Analytics\AnalyticsScanCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Aucune base n'est nécessaire : les réglages sont lus par Setting::get(), qui
 * passe d'abord par Cache::remember('setting_<clé>'). En préchargeant ces clés,
 * on exerce le vrai chemin de code sans jamais toucher MySQL.
 */
class AnalyticsScanCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->reglages(active: '1', ttl: 900);
    }

    private function reglages(string $active, int $ttl): void
    {
        Cache::put('setting_analytics.scan_cache.enabled', $active, 600);
        Cache::put('setting_analytics.scan_cache.ttl_seconds', $ttl, 600);
    }

    public function test_le_second_appel_ne_recalcule_pas(): void
    {
        $cache = new AnalyticsScanCache;
        $appels = 0;

        $premier = $cache->remember('recouvrement_gap', 'portee-a', function () use (&$appels) {
            $appels++;

            return ['2026-05' => 1200.0];
        });

        $second = $cache->remember('recouvrement_gap', 'portee-a', function () use (&$appels) {
            $appels++;

            return ['2026-05' => 9999.0];
        });

        $this->assertSame(1, $appels, 'Le balayage a été rejoué alors qu\'il était mémorisé.');
        $this->assertSame(['2026-05' => 1200.0], $second['data']);
        $this->assertFalse($premier['from_cache']);
        $this->assertTrue($second['from_cache']);
    }

    public function test_la_fraicheur_est_celle_du_calcul_pas_de_la_lecture(): void
    {
        $cache = new AnalyticsScanCache;

        $premier = $cache->remember('cash_flow_projection', 'portee-a', fn () => ['x' => 1.0]);
        $second = $cache->remember('cash_flow_projection', 'portee-a', fn () => ['x' => 2.0]);

        $this->assertSame(
            $premier['computed_at']->toIso8601String(),
            $second['computed_at']->toIso8601String(),
            'Une valeur mémorisée doit annoncer la date de son calcul, pas celle de sa relecture.',
        );
    }

    public function test_deux_portees_ne_se_melangent_pas(): void
    {
        $cache = new AnalyticsScanCache;

        $cache->remember('recouvrement_gap', 'portee-a', fn () => ['a' => 1.0]);
        $b = $cache->remember('recouvrement_gap', 'portee-b', fn () => ['b' => 2.0]);

        $this->assertSame(['b' => 2.0], $b['data']);
    }

    public function test_deux_familles_ne_se_melangent_pas(): void
    {
        $cache = new AnalyticsScanCache;

        $cache->remember('recouvrement_gap', 'meme-portee', fn () => ['gap' => 1.0]);
        $projection = $cache->remember('cash_flow_projection', 'meme-portee', fn () => ['proj' => 2.0]);

        $this->assertSame(['proj' => 2.0], $projection['data']);
    }

    public function test_invalidation_force_le_recalcul(): void
    {
        $cache = new AnalyticsScanCache;

        $cache->remember('recouvrement_gap', 'portee-a', fn () => ['ancien' => 1.0]);
        $cache->invalidate();
        $apres = $cache->remember('recouvrement_gap', 'portee-a', fn () => ['neuf' => 2.0]);

        $this->assertSame(['neuf' => 2.0], $apres['data']);
        $this->assertFalse($apres['from_cache']);
    }

    public function test_desactive_par_reglage_le_calcul_est_rejoue(): void
    {
        $this->reglages(active: '0', ttl: 900);
        $cache = new AnalyticsScanCache;
        $appels = 0;

        $cache->remember('recouvrement_gap', 'portee-a', function () use (&$appels) {
            $appels++;

            return [];
        });
        $cache->remember('recouvrement_gap', 'portee-a', function () use (&$appels) {
            $appels++;

            return [];
        });

        $this->assertSame(2, $appels, 'Réglage à 0 : le comportement doit redevenir un recalcul intégral.');
    }

    public function test_duree_de_vie_nulle_equivaut_a_desactive(): void
    {
        $this->reglages(active: '1', ttl: 0);
        $cache = new AnalyticsScanCache;

        $this->assertFalse($cache->enabled());
    }

    public function test_une_generation_perdue_ne_ressuscite_pas_une_valeur_perimee(): void
    {
        $cache = new AnalyticsScanCache;
        $cache->remember('recouvrement_gap', 'portee-a', fn () => ['perime' => 1.0]);

        // Le fichier de génération disparaît (purge partielle du cache), mais les
        // charges mémorisées, elles, sont encore là.
        Cache::forget('analytics:scan:generation');

        $apres = $cache->remember('recouvrement_gap', 'portee-a', fn () => ['neuf' => 2.0]);

        $this->assertSame(['neuf' => 2.0], $apres['data']);
    }
}
