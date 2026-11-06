<?php

namespace Tests\Unit\Comptabilite;

use App\Domain\Comptabilite\SuiviClasses\SituationClassesService;
use App\Domain\Exports\Reports\SituationClassesReport;
use App\Exports\SituationClassesExport;
use PHPUnit\Framework\TestCase;

final class SituationClassesExportContractTest extends TestCase
{
    public function test_limites_de_volume_et_modeles_pdf_excel_distincts(): void
    {
        $this->assertSame(1000, SituationClassesService::PDF_MAX_ROWS);
        $this->assertSame(50000, SituationClassesService::EXCEL_MAX_ROWS);
        $this->assertTrue(is_subclass_of(SituationClassesReport::class, \App\Domain\Exports\ExportableReport::class));
        $this->assertSame('esbtp.comptabilite.suivi-classes.pdf', (new SituationClassesReport([
            'classes' => collect(), 'totaux' => [], 'filtered_category' => false,
        ], []))->pdfView());
        $this->assertTrue(is_subclass_of(SituationClassesExport::class, \Maatwebsite\Excel\Concerns\WithDrawings::class));
    }
}
