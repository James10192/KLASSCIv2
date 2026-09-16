<?php

namespace Tests\Unit\Domain\Comptabilite\Paie;

use App\Domain\Comptabilite\Paie\ProfilPaysPaie;
use PHPUnit\Framework\TestCase;

class ProfilPaysPaieTest extends TestCase
{
    public function test_un_profil_non_valide_interdit_le_paiement_definitif(): void
    {
        $this->assertFalse(ProfilPaysPaie::permetPaiementDefinitif(ProfilPaysPaie::NON_VALIDE));
        $this->assertTrue(ProfilPaysPaie::permetPaiementDefinitif(ProfilPaysPaie::CI));
    }

    public function test_le_benin_n_herite_pas_des_retenues_ivoiriennes(): void
    {
        $this->assertFalse(ProfilPaysPaie::appliqueRetenuesIvoiriennes(ProfilPaysPaie::BJ));
        $this->assertFalse(ProfilPaysPaie::appliqueRetenuesIvoiriennes(ProfilPaysPaie::NON_VALIDE));
        $this->assertTrue(ProfilPaysPaie::appliqueRetenuesIvoiriennes(ProfilPaysPaie::CI));
    }

    public function test_une_valeur_vide_reste_le_defaut_produit(): void
    {
        $this->assertSame(ProfilPaysPaie::CI, ProfilPaysPaie::depuis(''));
        $this->assertSame(ProfilPaysPaie::NON_VALIDE, ProfilPaysPaie::depuis('', ProfilPaysPaie::NON_VALIDE));
    }
}
