<?php

namespace Tests\Unit\Exports;

use App\Domain\Exports\Reports\EnseignantsReport;
use App\Exports\EnseignantsExport;
use App\Exports\EnseignantsGroupesExport;
use App\Exports\EnseignantsCompositionExport;
use App\Services\Enseignants\CompositionExportEnseignants;
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
        $this->assertInstanceOf(EnseignantsGroupesExport::class, $report->excelExport());
        $annuaire = new EnseignantsReport([$this->row()], [], [], 0, 'annuaire');
        $this->assertInstanceOf(EnseignantsExport::class, $annuaire->excelExport());
    }

    public function test_liste_simplifiee_declenche_portrait_excel_personnalise_et_cache_distinct(): void
    {
        $affectations = [
            ['classe' => '1 BTS', 'semestre' => 'S1', 'matiere' => 'Maths',
                'nom' => 'PROF KONE', 'filiere' => 'BTP', 'niveau' => 'BTS 1'],
        ];
        $options = CompositionExportEnseignants::options('liste', ['nom'], 'matiere');
        $report = new EnseignantsReport([], [], $affectations, 1, 'liste', $options, 'auto');
        $autre = new EnseignantsReport([], [], $affectations, 1, 'liste', $options, 'landscape');

        $this->assertSame('portrait', $report->orientation());
        $this->assertSame('portrait', $report->viewData()['reportOrientation']);
        $this->assertSame(1, $report->viewData()['composition']['total']);
        $this->assertInstanceOf(EnseignantsCompositionExport::class, $report->excelExport());
        $this->assertSame(['Nom du professeur'], $report->excelExport()->headings());
        $this->assertNotSame($report->cacheKey(), $autre->cacheKey());
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

    public function test_excel_regroupe_les_matières_et_protege_les_champs_libres(): void
    {
        $affectations = [
            ['matiere' => 'Maths', 'nom' => '=DANGER', 'classe' => '1 BTS', 'semestre' => 'S1'],
            ['matiere' => 'Maths', 'nom' => '=DANGER', 'classe' => '1 BTS', 'semestre' => 'S2'],
        ];
        $report = new EnseignantsReport([], [], $affectations, 2, 'matieres');
        $export = $report->excelExport();
        $this->assertInstanceOf(EnseignantsGroupesExport::class, $export);
        $this->assertSame(1, $export->nombreLignes());
        $row = $export->collection()->first();
        $this->assertSame('Maths', $row[0]);
        $this->assertSame("'=DANGER", $row[1]);
        $this->assertSame('1 BTS (S1 / S2)', $row[2]);
        $this->assertSame(2, $row[6]);
    }

    public function test_excel_specialisations_garde_les_libelles_non_classes(): void
    {
        $fiche = array_merge($this->row(), ['origine' => 'Enregistré']);
        $manuel = array_merge($this->row('M. DIARRA'), [
            'origine' => 'Bulletin (saisie manuelle)', 'specialisation' => '—',
        ]);
        $export = (new EnseignantsReport([$fiche, $manuel], [], [], 0, 'specialisations'))->excelExport();
        $this->assertSame(2, $export->nombreLignes());
        $this->assertSame('À classer', $export->collection()->last()[0]);
    }

    public function test_export_excel_formate_le_telephone_comme_texte_et_protege_les_formules(): void
    {
        $export = new EnseignantsExport([$this->row('=2+2')]);
        $this->assertSame(NumberFormat::FORMAT_TEXT, $export->columnFormats()['D']);
        $this->assertSame('N°', $export->headings()[0]);
        $this->assertSame("'=2+2", $export->map($this->row('=2+2'))[2]);
    }
}
