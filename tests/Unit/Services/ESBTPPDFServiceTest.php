<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ESBTPPDFService;
use App\Services\NumberToWords;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPResultatMatiere;
use Barryvdh\DomPDF\PDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ESBTPPDFServiceTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPPDFService $pdfService;
    private $numberToWords;

    protected function setUp(): void
    {
        parent::setUp();
        $this->numberToWords = Mockery::mock(NumberToWords::class);
        $this->pdfService = new ESBTPPDFService($this->numberToWords);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generer_bulletin_pdf()
    {
        // Mock de NumberToWords
        $this->numberToWords->shouldReceive('convert')
            ->with(15.00)
            ->andReturn('quinze');

        // Création des données de test
        $etudiant = ESBTPEtudiant::factory()->create();
        $classe = ESBTPClasse::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();

        $bulletin = ESBTPBulletin::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => 1,
            'moyenne_generale' => 15.00,
            'rang' => 1,
            'mention' => 'Bien',
            'absences_justifiees' => 4,
            'absences_non_justifiees' => 2
        ]);

        ESBTPResultatMatiere::factory()->create([
            'bulletin_id' => $bulletin->id,
            'matiere_id' => $matiere->id,
            'moyenne' => 15.00,
            'coefficient' => 2,
            'rang' => 1
        ]);

        // Test de la génération du PDF
        $pdf = $this->pdfService->genererBulletinPDF($bulletin);

        $this->assertInstanceOf(PDF::class, $pdf);
    }

    public function test_generer_pv_deliberation_pdf()
    {
        // Création des données de test
        $classe = ESBTPClasse::factory()->create();
        $etudiant1 = ESBTPEtudiant::factory()->create();
        $etudiant2 = ESBTPEtudiant::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();

        $bulletin1 = ESBTPBulletin::factory()->create([
            'etudiant_id' => $etudiant1->id,
            'classe_id' => $classe->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => 1
        ]);

        $bulletin2 = ESBTPBulletin::factory()->create([
            'etudiant_id' => $etudiant2->id,
            'classe_id' => $classe->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => 1
        ]);

        ESBTPResultatMatiere::factory()->create([
            'bulletin_id' => $bulletin1->id,
            'matiere_id' => $matiere->id
        ]);

        ESBTPResultatMatiere::factory()->create([
            'bulletin_id' => $bulletin2->id,
            'matiere_id' => $matiere->id
        ]);

        // Test de la génération du PDF
        $pdf = $this->pdfService->genererPVDeliberationPDF($classe, 'semestre1', 1);

        $this->assertInstanceOf(PDF::class, $pdf);
    }

    public function test_generer_rapport_absences_pdf()
    {
        // Création des données de test
        $classe = ESBTPClasse::factory()->create();
        $etudiant = ESBTPEtudiant::factory()->create(['classe_id' => $classe->id]);
        $matiere = ESBTPMatiere::factory()->create();

        // Test de la génération du PDF
        $pdf = $this->pdfService->genererRapportAbsencesPDF($classe, 'semestre1', 1);

        $this->assertInstanceOf(PDF::class, $pdf);
    }
}
