<?php

namespace Tests\Feature\Notes;

use App\Domain\Notes\Classeur\ImportDuClasseur;
use App\Exports\NotesClasseurExport;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Aller-retour réel : le classeur tel que KLASSCI le produit (bandeau,
 * colonnes ajoutées, paysage), rempli comme le ferait l'école, puis relu par
 * l'import du classeur. Aucune ligne n'est écrite à la main dans le fichier.
 */
class FicheNotesExcelAllerRetourTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_classeur_exporte_puis_rempli_se_reimporte_et_cree_les_evaluations(): void
    {
        Role::findOrCreate('superAdmin', 'web');
        $this->actingAs(User::factory()->create()->assignRole('superAdmin'));

        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);
        $classe = ESBTPClasse::factory()->create();
        $algo = ESBTPMatiere::factory()->create(['name' => 'Algorithme', 'code' => 'ALGO']);
        $reseau = ESBTPMatiere::factory()->create(['name' => 'Réseaux', 'code' => 'RES']);
        $devoir = ESBTPEvaluation::factory()->create([
            'classe_id' => $classe->id, 'matiere_id' => $algo->id, 'periode' => 'semestre1',
            'annee_universitaire_id' => $annee->id, 'is_published' => 1, 'bareme' => 20, 'coefficient' => 1,
            'titre' => 'Devoir 1', 'date_evaluation' => '2026-10-01',
        ]);
        $eleves = collect(['MAT001' => 'KOFFI', 'MAT002' => 'TRAORE'])->map(function ($nom, $matricule) use ($classe, $annee) {
            $e = ESBTPEtudiant::factory()->create(['matricule' => $matricule, 'nom' => $nom, 'prenoms' => 'Test']);
            ESBTPInscription::factory()->create(['etudiant_id' => $e->id, 'classe_id' => $classe->id,
                'annee_universitaire_id' => $annee->id, 'status' => 'active', 'workflow_step' => 'etudiant_cree']);

            return $e;
        });

        // 1. Export réel : 3 colonnes, paysage.
        $chemin = tempnam(sys_get_temp_dir(), 'aller_').'.xlsx';
        file_put_contents($chemin, Excel::raw(new NotesClasseurExport($classe->id, collect([$algo, $reseau]), 'semestre1', $annee->id, 3, 'landscape'), \Maatwebsite\Excel\Excel::XLSX));

        // 2. L'école remplit, dans Excel.
        $classeur = IOFactory::load($chemin);
        $algoFeuille = $classeur->getSheetByName('ALGO');
        $resFeuille = $classeur->getSheetByName('RES');
        $this->assertSame('Matricule', $algoFeuille->getCell('A6')->getValue());
        $this->assertStringStartsWith('Devoir 1', (string) $algoFeuille->getCell('C6')->getValue());
        $this->assertStringStartsWith('Évaluation 2', (string) $algoFeuille->getCell('D6')->getValue());
        $this->assertStringStartsWith('Examen', (string) $resFeuille->getCell('C6')->getValue());
        // Lignes 7 et 8 : élèves triés par nom (KOFFI puis TRAORE).
        $this->assertSame('MAT001', (string) $algoFeuille->getCell('A7')->getValue());
        $algoFeuille->setCellValue('C7', 14);                       // Devoir 1 existant
        $algoFeuille->setCellValue('D6', 'Interrogation (/40 ×2)'); // colonne ajoutée renommée
        $algoFeuille->setCellValue('D7', 30);
        $algoFeuille->setCellValue('D8', 'ABS');
        // E (Évaluation 3) laissée vide : ne crée rien.
        $resFeuille->setCellValue('C7', 12);                        // « Examen » d'une matière vide
        $resFeuille->setCellValue('D8', 9);                         // « Évaluation 2 »
        IOFactory::createWriter($classeur, 'Xlsx')->save($chemin);
        $fichier = fn () => new UploadedFile($chemin, 'fiche.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        // 3. Simulation puis écriture.
        $import = app(ImportDuClasseur::class);
        $simulation = $import->simuler($fichier(), $classe->id);
        $this->assertSame(0, $simulation['total']['errors'], json_encode($simulation['feuilles'], JSON_UNESCAPED_UNICODE));
        $this->assertSame(5, $simulation['total']['will_create']);
        $parFeuille = collect($simulation['feuilles'])->keyBy('feuille');
        $this->assertSame(['Interrogation'], $parFeuille['ALGO']['evaluations_a_creer']);
        $this->assertSame(['Examen', 'Évaluation 2'], $parFeuille['RES']['evaluations_a_creer']);

        $resultat = $import->appliquer($fichier(), $classe->id);
        $this->assertTrue($resultat['applique']);

        $interro = ESBTPEvaluation::where('matiere_id', $algo->id)->where('titre', 'Interrogation')->firstOrFail();
        $this->assertSame([40.0, 2.0], [(float) $interro->bareme, (float) $interro->coefficient]);
        $this->assertSame(1, ESBTPEvaluation::where('matiere_id', $algo->id)->count() - 1, 'Évaluation 3, vide, ne crée rien.');
        $this->assertSame(14.0, (float) ESBTPNote::where('evaluation_id', $devoir->id)->where('etudiant_id', $eleves['MAT001']->id)->value('note'));
        $this->assertSame(30.0, (float) ESBTPNote::where('evaluation_id', $interro->id)->where('etudiant_id', $eleves['MAT001']->id)->value('note'));
        $this->assertTrue((bool) ESBTPNote::where('evaluation_id', $interro->id)->where('etudiant_id', $eleves['MAT002']->id)->value('is_absent'));
        $this->assertSame(2, ESBTPEvaluation::where('matiere_id', $reseau->id)->count());

        // 4. Réimporter le même fichier : ni évaluation ni note en double.
        $avant = [ESBTPEvaluation::count(), ESBTPNote::count()];
        $this->assertSame(0, $import->simuler($fichier(), $classe->id)['total']['will_create']);
        $import->appliquer($fichier(), $classe->id);
        $this->assertSame($avant, [ESBTPEvaluation::count(), ESBTPNote::count()]);
    }
}
