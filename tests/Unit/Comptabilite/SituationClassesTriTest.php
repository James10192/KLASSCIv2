<?php

namespace Tests\Unit\Comptabilite;

use App\Domain\Comptabilite\SuiviClasses\SituationClassesService;
use PHPUnit\Framework\TestCase;

final class SituationClassesTriTest extends TestCase
{
    private function ligne(string $classe, float $restant, float $echu, ?float $taux): array
    {
        return compact('classe', 'restant', 'echu', 'taux');
    }

    private function liste(): array
    {
        return [
            $this->ligne('BTS 2', 300, 0, 70.0),
            $this->ligne('BTS 10', 900, 500, 20.0),
            $this->ligne('BTS 1', 100, 50, null),
        ];
    }

    public function test_le_tri_par_nom_suit_l_ordre_naturel(): void
    {
        $noms = array_column(SituationClassesService::trier($this->liste(), 'classe'), 'classe');

        $this->assertSame(['BTS 1', 'BTS 2', 'BTS 10'], $noms);
    }

    public function test_le_tri_par_reste_et_par_echu_met_les_plus_gros_montants_en_tete(): void
    {
        $this->assertSame('BTS 10', SituationClassesService::trier($this->liste(), 'reste')[0]['classe']);
        $this->assertSame(['BTS 10', 'BTS 1', 'BTS 2'], array_column(SituationClassesService::trier($this->liste(), 'echu'), 'classe'));
    }

    public function test_le_tri_par_recouvrement_met_le_plus_faible_en_tete_et_les_classes_sans_attendu_a_la_fin(): void
    {
        $this->assertSame(['BTS 10', 'BTS 2', 'BTS 1'], array_column(SituationClassesService::trier($this->liste(), 'taux'), 'classe'));
    }

    public function test_seules_les_classes_avec_un_echu_sont_a_surveiller_par_montant_decroissant(): void
    {
        $surveiller = SituationClassesService::aSurveiller($this->liste());

        $this->assertSame(['BTS 10', 'BTS 1'], array_column($surveiller, 'classe'));
    }
}
