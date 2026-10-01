<?php

namespace Tests\Unit\RendezVous;

use App\Services\RendezVous\RendezVousReglages;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Le lieu du rendez-vous : la convocation donnait la date et l'heure, jamais
 * l'endroit. Les familles appelaient pour le demander, ou ecrivaient a KLASSCI.
 *
 * Les reglages sont lus par `Setting::get`, qui passe par le cache : on les y
 * pose directement, sans base.
 */
class LieuDuRendezVousTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function regler(string $cle, string $valeur): void
    {
        Cache::put('setting_'.$cle, $valeur, 3600);
    }

    public function test_le_lieu_regle_par_l_ecole_est_annonce(): void
    {
        $this->regler(RendezVousReglages::LIEU, '  Scolarité, bâtiment A  ');
        $this->regler('school_address', 'Yamoussoukro, quartier 220 logements');

        $this->assertSame('Scolarité, bâtiment A', app(RendezVousReglages::class)->lieu());
    }

    public function test_sans_lieu_l_adresse_de_l_etablissement_est_reprise(): void
    {
        $this->regler(RendezVousReglages::LIEU, '');
        $this->regler('school_address', 'Yamoussoukro, quartier 220 logements');

        $this->assertSame('Yamoussoukro, quartier 220 logements', app(RendezVousReglages::class)->lieu());
    }

    public function test_sans_rien_le_lieu_est_vide_et_le_courriel_ne_l_affiche_pas(): void
    {
        $this->regler(RendezVousReglages::LIEU, '');
        $this->regler('school_address', '');

        $this->assertSame('', app(RendezVousReglages::class)->lieu());
        $this->assertStringNotContainsString('Lieu :', $this->courriel(''));
    }

    public function test_le_courriel_de_convocation_porte_le_lieu(): void
    {
        $this->assertStringContainsString('Scolarité, bâtiment A', $this->courriel('Scolarité, bâtiment A'));
    }

    public function test_le_lieu_est_un_reglage_enregistre_par_l_ecran(): void
    {
        $this->assertContains(RendezVousReglages::LIEU, RendezVousReglages::clesTexte());
    }

    private function courriel(string $lieu): string
    {
        return View::make('esbtp.emails.parents.rendez-vous-convocation', [
            'nom' => 'Kouassi Ama',
            'intro' => 'Votre rendez-vous est confirmé.',
            'date' => 'jeudi 2 octobre 2026',
            'heure' => '09:00 – 09:30',
            'reference' => 'DS3H-6EZG-XAB8',
            'lieu' => $lieu,
            'lien' => '',
            'lienPdf' => '',
            'schoolName' => 'École de démonstration',
            'schoolLogoUrl' => null,
        ])->render();
    }
}
