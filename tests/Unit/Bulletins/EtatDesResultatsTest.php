<?php

namespace Tests\Unit\Bulletins;

use App\Domain\Bulletins\EtatDesResultats as Etat;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EtatDesResultatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->poser('12', '70', '50');
    }

    public function test_la_moyenne_prend_la_couleur_de_son_seuil(): void
    {
        $etat = app(Etat::class);

        $this->assertSame(Etat::ALERTE, $etat->etatDeLaMoyenne(9.99));
        $this->assertSame(Etat::A_SURVEILLER, $etat->etatDeLaMoyenne(10));
        $this->assertSame(Etat::A_SURVEILLER, $etat->etatDeLaMoyenne(11.99));
        $this->assertSame(Etat::BON, $etat->etatDeLaMoyenne(12));
        $this->assertSame(Etat::BON, $etat->etatDeLaMoyenne('14.5'));
    }

    public function test_la_reussite_prend_la_couleur_de_ses_deux_seuils(): void
    {
        $etat = app(Etat::class);

        $this->assertSame(Etat::ALERTE, $etat->etatDeLaReussite(49.9));
        $this->assertSame(Etat::A_SURVEILLER, $etat->etatDeLaReussite(50));
        $this->assertSame(Etat::A_SURVEILLER, $etat->etatDeLaReussite(69.9));
        $this->assertSame(Etat::BON, $etat->etatDeLaReussite(70));
    }

    public function test_sans_valeur_aucune_couleur(): void
    {
        $kpis = app(Etat::class)->completer(['moyenne_generale' => null, 'taux_reussite' => null]);

        $this->assertSame(['moyenne_generale' => null, 'taux_reussite' => null], $kpis['etats']);
    }

    public function test_les_seuils_de_l_ecole_sont_lus(): void
    {
        $this->poser('15', '90', '80');
        $etat = app(Etat::class);

        $this->assertSame(Etat::A_SURVEILLER, $etat->etatDeLaMoyenne(14));
        $this->assertSame(Etat::ALERTE, $etat->etatDeLaReussite(75));
        $this->assertSame(Etat::A_SURVEILLER, $etat->etatDeLaReussite(85));
    }

    public function test_des_seuils_incoherents_retombent_sur_le_repli(): void
    {
        // Rouge au-dessus du vert, moyenne verte sous le seuil de reussite.
        $this->poser('8', '40', '60');
        $etat = app(Etat::class);

        $this->assertSame([Etat::REUSSITE_ALERTE_REPLI, Etat::REUSSITE_SATISFAISANTE_REPLI], $etat->seuilsDeReussite());
        $this->assertSame(Etat::MOYENNE_SATISFAISANTE_REPLI, $etat->moyenneSatisfaisante());
    }

    public function test_l_ecran_des_reglages_refuse_des_seuils_incoherents(): void
    {
        $this->assertNull(Etat::incoherence('12', '70', '50'));
        $this->assertNull(Etat::incoherence(null, null, null));
        $this->assertStringContainsString('inférieur', (string) Etat::incoherence('12', '60', '80'));
        $this->assertStringContainsString('inférieur', (string) Etat::incoherence('12', '60', '60'));
        $this->assertStringContainsString('entre 10 et 20', (string) Etat::incoherence('8', '70', '50'));
        $this->assertStringContainsString('0 et 100', (string) Etat::incoherence('12', '120', '50'));
    }

    private function poser(string $moyenne, string $bon, string $alerte): void
    {
        Cache::put('setting_'.Etat::REGLAGE_MOYENNE_SATISFAISANTE, $moyenne, 60);
        Cache::put('setting_'.Etat::REGLAGE_REUSSITE_SATISFAISANTE, $bon, 60);
        Cache::put('setting_'.Etat::REGLAGE_REUSSITE_ALERTE, $alerte, 60);
    }
}
