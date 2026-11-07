<?php

namespace Tests\Unit\Support;

use App\Support\NomDeFichier;
use PHPUnit\Framework\TestCase;

final class NomDeFichierTest extends TestCase
{
    public function test_les_barres_obliques_d_un_matricule_et_d_une_annee_deviennent_des_tirets(): void
    {
        $this->assertSame(
            'bulletin_25-ESBTP-001_1BTS-A_semestre1_2025-2026.pdf',
            NomDeFichier::sur('bulletin_25/ESBTP/001_1BTS\\A_semestre1_2025/2026.pdf')
        );
    }

    public function test_les_caracteres_interdits_par_windows_sont_retires(): void
    {
        $this->assertSame('releve - L3 GC-2.xlsx', NomDeFichier::sur('releve : L3 GC*?2.xlsx'));
    }

    public function test_un_nom_vide_retombe_sur_le_repli(): void
    {
        $this->assertSame('document.pdf', NomDeFichier::sur(' /// ', 'document.pdf'));
    }

    public function test_un_nom_deja_propre_est_inchange(): void
    {
        $this->assertSame('bulletin_A123_semestre1.pdf', NomDeFichier::sur('bulletin_A123_semestre1.pdf'));
    }
}
