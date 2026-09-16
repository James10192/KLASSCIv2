<?php

namespace Tests\Unit\Domain\Comptabilite;

use App\Domain\Comptabilite\MappingComptesExport;
use PHPUnit\Framework\TestCase;

class MappingComptesExportTest extends TestCase
{
    public function test_sans_mapping_l_export_n_est_pas_pret(): void
    {
        $this->assertFalse(MappingComptesExport::estPret([], ''));
        $this->assertTrue(MappingComptesExport::estPret(['inscription' => '706100'], ''));
        $this->assertTrue(MappingComptesExport::estPret([], '411000'));
    }

    public function test_le_defaut_produit_ne_copie_pas_les_comptes_ci(): void
    {
        $this->assertTrue(MappingComptesExport::nImportePasLesComptesCiParDefaut(''));
        $this->assertFalse(MappingComptesExport::nImportePasLesComptesCiParDefaut('411000'));
    }

    public function test_un_ecart_de_totaux_est_un_echec_de_rapprochement(): void
    {
        $this->assertTrue(MappingComptesExport::totauxConcordent(1000.00, 1000.00));
        $this->assertFalse(MappingComptesExport::totauxConcordent(1000.00, 1000.01));
    }
}
