<?php

namespace Tests\Unit\RendezVous;

use App\Services\RendezVous\AffecteurDossiersRdv;
use App\Services\RendezVous\CatalogueCreneaux;
use App\Services\RendezVous\MessagerieRdv;
use App\Services\RendezVous\RendezVousReglages;
use App\Services\RendezVous\ReservateurRdv;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/**
 * « Placer et convoquer » dit pourquoi il n'a rien fait.
 *
 * Avant, un canal ferme faisait compter chaque dossier « sans creneau » : sur une
 * instance fermee, l'ecole lisait « 0 place, 47 sans creneau » et cherchait des
 * creneaux qui existaient.
 */
class AffecteurDossiersRdvTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_un_canal_ferme_est_un_refus_nomme(): void
    {
        Cache::put('setting_'.RendezVousReglages::ENABLED, '0', 60);
        $catalogue = Mockery::mock(CatalogueCreneaux::class);
        $catalogue->shouldNotReceive('anneeDesCreneaux');

        $rapport = $this->affecteur($catalogue)->placer();

        $this->assertStringContainsString('fermée', (string) $rapport['refus']);
        $this->assertSame(0, $rapport['sans_creneau']);
    }

    public function test_aucune_place_libre_est_un_refus_nomme(): void
    {
        Cache::put('setting_'.RendezVousReglages::ENABLED, '1', 60);
        $catalogue = Mockery::mock(CatalogueCreneaux::class);
        $catalogue->shouldReceive('placesLibres')->andReturn([]);
        $catalogue->shouldNotReceive('anneeDesCreneaux');

        $rapport = $this->affecteur($catalogue)->placer();

        $this->assertStringContainsString('Aucune place libre', (string) $rapport['refus']);
    }

    private function affecteur(CatalogueCreneaux $catalogue): AffecteurDossiersRdv
    {
        return new AffecteurDossiersRdv(
            app(RendezVousReglages::class),
            $catalogue,
            Mockery::mock(ReservateurRdv::class),
            Mockery::mock(MessagerieRdv::class),
        );
    }
}
