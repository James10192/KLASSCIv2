<?php

namespace Tests\Unit\Exports;

use App\Domain\Exports\Reports\EnseignantsReport;
use App\Exports\EnseignantsExport;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Tests\TestCase;

class EnseignantsReportTest extends TestCase
{
    private function row(string $name = 'Professeur Diarra'): array
    {
        return [
            'matricule' => 'ENS-004', 'nom' => $name, 'telephone' => '+225 07 12 34 56 78',
            'email' => 'prof@example.test', 'specialisation' => 'Mathématiques',
            'regime' => 'Vacataire', 'statut' => 'Actif',
        ];
    }

    public function test_rapport_utilise_template_et_excel_unifies(): void
    {
        $report = new EnseignantsReport([$this->row()], ['Statut' => 'Actif']);

        $this->assertSame('esbtp.enseignants.export-pdf', $report->pdfView());
        $this->assertSame('landscape', $report->orientation());
        $this->assertSame(1, $report->viewData()['total']);
        $this->assertSame(['Statut' => 'Actif'], $report->filters());
        $this->assertInstanceOf(EnseignantsExport::class, $report->excelExport());
    }

    public function test_changement_de_nom_invalide_le_cache_pdf(): void
    {
        $premier = new EnseignantsReport([$this->row()], []);
        $modifie = new EnseignantsReport([$this->row('Professeur Kouame')], []);
        $this->assertNotSame($premier->cacheKey(), $modifie->cacheKey());
    }

    public function test_le_pdf_separe_les_fiches_des_saisies_manuelles_et_le_mode_change_le_cache(): void
    {
        $inscrit = array_merge($this->row(), ['origine' => 'Enregistré', 'classes' => '']);
        $manuel = array_merge($this->row('M. DIARRA'), [
            'origine' => 'Bulletin (saisie manuelle)', 'classes' => '1 BTS',
        ]);
        $affectations = [['classe' => '1 BTS', 'semestre' => 'S1', 'matiere' => 'Dessin', 'nom' => 'M. DIARRA']];
        $synthese = new EnseignantsReport([$inscrit, $manuel], [], $affectations, 1, 'synthese');
        $rapprochement = new EnseignantsReport([$inscrit, $manuel], [], $affectations, 1, 'rapprochement');

        $this->assertSame(1, $synthese->viewData()['nombreFiches']);
        $this->assertSame(1, $synthese->viewData()['nombreSaisies']);
        $this->assertSame(1, $synthese->viewData()['nombreClasses']);
        $this->assertSame('rapprochement', $rapprochement->viewData()['vue']);
        $this->assertNotSame($synthese->cacheKey(), $rapprochement->cacheKey());
    }

    public function test_export_excel_formate_le_telephone_comme_texte_et_protege_les_formules(): void
    {
        $export = new EnseignantsExport([$this->row('=2+2')]);
        $this->assertSame(NumberFormat::FORMAT_TEXT, $export->columnFormats()['D']);
        $this->assertSame('N°', $export->headings()[0]);
        $this->assertSame("'=2+2", $export->map($this->row('=2+2'))[2]);
    }
}
