<?php

namespace Tests\Unit\Paiements;

use App\Models\ESBTPPaiement;
use App\Services\Paiements\EtatRecuPaiement;
use Carbon\Carbon;
use Tests\TestCase;

class EtatRecuPaiementTest extends TestCase
{
    public function test_les_versements_avant_et_apres_sont_separes(): void
    {
        $avant = $this->versement(1, '2026-08-20');
        $courant = $this->versement(2, '2026-09-01');
        $apres = $this->versement(3, '2026-09-10');

        $classes = (new EtatRecuPaiement)->classerVersements(
            collect([$avant, $apres]),
            $courant
        );

        $this->assertTrue($classes['avant']->contains('id', 1));
        $this->assertTrue($classes['apres']->contains('id', 3));
        $this->assertFalse($classes['avant']->contains('id', 2));
        $this->assertFalse($classes['apres']->contains('id', 2));
    }

    public function test_un_versement_du_meme_jour_mais_plus_tot_est_anterieur(): void
    {
        $premier = $this->versement(10, '2026-09-01');
        $second = $this->versement(11, '2026-09-01');

        $classes = (new EtatRecuPaiement)->classerVersements(
            collect([$premier]),
            $second
        );

        $this->assertTrue($classes['avant']->contains('id', 10));
        $this->assertTrue($classes['apres']->isEmpty());
    }

    public function test_le_gabarit_du_recu_porte_affectation_nature_et_historique(): void
    {
        $source = file_get_contents(resource_path('views/esbtp/paiements/partials/recu-exemplaire.blade.php'));

        $this->assertStringContainsString("Statut d'affectation", $source);
        $this->assertStringContainsString('reçu en nature', $source);
        $this->assertStringContainsString('Versements antérieurs', $source);
        $this->assertStringContainsString('Versements postérieurs', $source);
        $this->assertStringNotContainsString('La ligne reste DECOCHEE', $source);
    }

    private function versement(int $id, string $date): ESBTPPaiement
    {
        $paiement = new ESBTPPaiement;
        $paiement->id = $id;
        $paiement->date_paiement = Carbon::parse($date);
        $paiement->montant = 10000;
        $paiement->numero_recu = 'REC-'.$id;

        return $paiement;
    }
}
