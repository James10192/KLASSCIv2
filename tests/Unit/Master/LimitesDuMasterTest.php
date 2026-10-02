<?php

namespace Tests\Unit\Master;

use App\Services\Master\LimitesDuMaster;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Un echec du master ne doit pas etre redemande a chaque page : c'est ce qui
 * faisait attendre l'utilisateur jusqu'a 10 s par clic quand adminKlassci
 * repondait en erreur.
 */
class LimitesDuMasterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'services.master.api_url' => 'https://master.test/api',
            'services.master.api_token' => 'jeton',
            'app.tenant_code' => 'ecole-test',
        ]);
        Cache::flush();
    }

    public function test_une_reponse_est_gardee_et_le_master_n_est_appele_qu_une_fois(): void
    {
        Http::fake(['master.test/*' => Http::response(['plan' => 'elite'], 200)]);

        $limites = app(LimitesDuMaster::class);

        $this->assertSame(['plan' => 'elite'], $limites->lire());
        $this->assertSame(['plan' => 'elite'], $limites->lire());
        Http::assertSentCount(1);
    }

    public function test_un_echec_est_retenu_et_n_est_pas_redemande_a_chaque_lecture(): void
    {
        Http::fake(['master.test/*' => Http::response('erreur', 500)]);

        $limites = app(LimitesDuMaster::class);

        $this->assertNull($limites->lire());
        $this->assertNull($limites->lire());
        $this->assertNull($limites->lire());
        Http::assertSentCount(1);
    }

    public function test_sans_configuration_aucun_appel_n_est_fait(): void
    {
        config(['services.master.api_token' => null]);
        Http::fake();

        $this->assertNull(app(LimitesDuMaster::class)->lire());
        Http::assertNothingSent();
    }

    public function test_la_reponse_est_rangee_sous_la_cle_lue_par_l_assistant(): void
    {
        Http::fake(['master.test/*' => Http::response(['assistant' => ['budget_mensuel_fcfa' => 5000]], 200)]);

        app(LimitesDuMaster::class)->lire();

        $this->assertSame(5000, Cache::get(LimitesDuMaster::cle('ecole-test'))['assistant']['budget_mensuel_fcfa']);
    }
}
