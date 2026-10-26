<?php

namespace Tests\Unit\Paiements;

use App\Enums\ModePaiement;
use App\Services\PaymentFilterService;
use PHPUnit\Framework\TestCase;

class RegroupementDesModesTest extends TestCase
{
    public function test_le_libelle_carte_bancaire_est_reconnu_comme_carte(): void
    {
        $this->assertSame(ModePaiement::CARTE, ModePaiement::fromLegacy('Carte bancaire'));
    }

    public function test_un_code_et_un_libelle_du_meme_mode_ont_la_meme_cle(): void
    {
        $this->assertSame('especes', PaymentFilterService::cleDuMode('especes'));
        $this->assertSame('especes', PaymentFilterService::cleDuMode('Espèces'));
    }

    public function test_une_valeur_inconnue_garde_sa_forme(): void
    {
        $this->assertSame('xyz', PaymentFilterService::cleDuMode('xyz'));
        $this->assertSame('Xyz', PaymentFilterService::libelleDuMode('xyz'));
    }

    public function test_le_libelle_d_un_mode_canonique_vient_de_l_enum(): void
    {
        $this->assertSame('Espèces', PaymentFilterService::libelleDuMode('especes'));
    }
}
