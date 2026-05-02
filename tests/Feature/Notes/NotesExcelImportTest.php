<?php

namespace Tests\Feature\Notes;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\User;
use App\Services\NotesImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NotesExcelImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('notes.view', 'web');
        Permission::findOrCreate('notes.create', 'web');
        Permission::findOrCreate('notes.edit', 'web');
        Permission::findOrCreate('notes.import_excel', 'web');
        Permission::findOrCreate('module.notes_evaluations.access', 'web');
        Permission::findOrCreate('admin.access', 'web');
    }

    private function authUser(array $perms = ['notes.view', 'notes.create', 'notes.edit', 'notes.import_excel']): User
    {
        $user = User::factory()->create();
        foreach (array_merge($perms, ['module.notes_evaluations.access', 'admin.access']) as $p) {
            $user->givePermissionTo($p);
        }
        $this->actingAs($user);

        return $user;
    }

    private function buildContext(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);
        $classe = ESBTPClasse::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();
        $eval = ESBTPEvaluation::factory()->create([
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $annee->id,
            'is_published' => 1,
            'bareme' => 20,
            'coefficient' => 1,
            'titre' => 'Devoir 1',
        ]);

        return compact('annee', 'classe', 'matiere', 'eval');
    }

    private function makeInscription(int $classeId, int $anneeId, string $matricule = 'STU001'): ESBTPEtudiant
    {
        $etudiant = ESBTPEtudiant::factory()->create([
            'matricule' => $matricule,
            'nom' => 'KOFFI',
            'prenoms' => 'Marie',
        ]);
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classeId,
            'annee_universitaire_id' => $anneeId,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        return $etudiant;
    }

    /**
     * Build une UploadedFile xlsx avec meta + 1 row note.
     */
    private function buildExcelFile(array $ctx, string $matricule, $noteValue): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Ligne 1 : meta
        $sheet->setCellValue('A1', '__KLASSCI_NOTES_EXPORT__');
        $sheet->setCellValue('B1', json_encode([
            'classe_id' => $ctx['classe']->id,
            'matiere_id' => $ctx['matiere']->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $ctx['annee']->id,
            'evaluations' => [[
                'id' => $ctx['eval']->id,
                'titre' => $ctx['eval']->titre,
                'bareme' => 20,
                'coefficient' => 1,
            ]],
            'generated_at' => now()->toIso8601String(),
        ]));

        // Ligne 2 : header
        $sheet->setCellValue('A2', 'Matricule');
        $sheet->setCellValue('B2', 'Nom & Prénoms');
        $sheet->setCellValue('C2', 'Devoir 1 (/20 ×1)');
        $sheet->setCellValue('D2', 'Moyenne /20');

        // Ligne 3 : data
        $sheet->setCellValueExplicit('A3', $matricule, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('B3', 'KOFFI Marie');
        $sheet->setCellValue('C3', $noteValue);

        $tmpPath = tempnam(sys_get_temp_dir(), 'notes_imp_') . '.xlsx';
        $writer = new XlsxWriter($spreadsheet);
        $writer->save($tmpPath);

        return new UploadedFile($tmpPath, 'notes_test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_it_parses_valid_excel_file(): void
    {
        $this->authUser();
        $ctx = $this->buildContext();
        $this->makeInscription($ctx['classe']->id, $ctx['annee']->id, 'STU001');

        $file = $this->buildExcelFile($ctx, 'STU001', 15);

        $service = app(NotesImportService::class);
        $parsed = $service->parseFile($file);

        $this->assertArrayHasKey('rows', $parsed);
        $this->assertArrayHasKey('meta', $parsed);
        $this->assertNotNull($parsed['meta']);
        $this->assertSame($ctx['classe']->id, $parsed['meta']['classe_id']);
    }

    public function test_dry_run_returns_diff(): void
    {
        $this->authUser();
        $ctx = $this->buildContext();
        $this->makeInscription($ctx['classe']->id, $ctx['annee']->id, 'STU001');

        $file = $this->buildExcelFile($ctx, 'STU001', 15);

        $service = app(NotesImportService::class);
        $parsed = $service->parseFile($file);
        $diff = $service->dryRun($parsed, $ctx['classe']->id, $ctx['matiere']->id, 'semestre1', $ctx['annee']->id);

        $this->assertSame(1, $diff['summary']['will_create']);
        $this->assertSame(0, $diff['summary']['will_update']);
        $this->assertSame(0, $diff['summary']['errors']);
        $this->assertCount(1, $diff['changes']);
        $this->assertSame('create', $diff['changes'][0]['action']);
        $this->assertSame(15.0, (float) $diff['changes'][0]['after']);
    }

    public function test_apply_persists_changes_atomically(): void
    {
        $this->authUser();
        $ctx = $this->buildContext();
        $student = $this->makeInscription($ctx['classe']->id, $ctx['annee']->id, 'STU001');

        $file = $this->buildExcelFile($ctx, 'STU001', 17.5);

        $service = app(NotesImportService::class);
        $parsed = $service->parseFile($file);
        $result = $service->apply($parsed, $ctx['classe']->id, $ctx['matiere']->id, 'semestre1', $ctx['annee']->id);

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(0, $result['errors']);

        $this->assertDatabaseHas('esbtp_notes', [
            'etudiant_id' => $student->id,
            'evaluation_id' => $ctx['eval']->id,
            'note' => 17.5,
            'is_absent' => 0,
        ]);
    }

    public function test_it_rejects_notes_exceeding_bareme_during_import(): void
    {
        $this->authUser();
        $ctx = $this->buildContext();
        $this->makeInscription($ctx['classe']->id, $ctx['annee']->id, 'STU001');

        $file = $this->buildExcelFile($ctx, 'STU001', 25); // Bareme 20 → 25 hors barème

        $service = app(NotesImportService::class);
        $parsed = $service->parseFile($file);
        $diff = $service->dryRun($parsed, $ctx['classe']->id, $ctx['matiere']->id, 'semestre1', $ctx['annee']->id);

        $this->assertSame(0, $diff['summary']['will_create']);
        $this->assertSame(1, $diff['summary']['errors']);
        $this->assertStringContainsString('hors barème', $diff['errors'][0]['reason']);
    }

    public function test_it_throttles_import_dry_run_at_5_per_minute(): void
    {
        $this->authUser();
        $ctx = $this->buildContext();
        $this->makeInscription($ctx['classe']->id, $ctx['annee']->id, 'STU001');

        // Fire 5 fast (each request needs a fresh file)
        for ($i = 0; $i < 5; $i++) {
            $file = $this->buildExcelFile($ctx, 'STU001', 10 + $i);
            $r = $this->postJson(route('esbtp.notes.import.dry-run'), [
                'file' => $file,
                'classe_id' => $ctx['classe']->id,
                'matiere_id' => $ctx['matiere']->id,
                'periode' => 'semestre1',
            ]);
            $this->assertContains($r->status(), [200, 422, 429], "Iteration {$i}: status was {$r->status()}");
        }

        // 6th = throttle
        $file6 = $this->buildExcelFile($ctx, 'STU001', 12);
        $r6 = $this->postJson(route('esbtp.notes.import.dry-run'), [
            'file' => $file6,
            'classe_id' => $ctx['classe']->id,
            'matiere_id' => $ctx['matiere']->id,
            'periode' => 'semestre1',
        ]);

        $this->assertSame(429, $r6->status(), 'La 6ème requête doit être throttled.');
    }

    public function test_it_supports_absent_marker(): void
    {
        $this->authUser();
        $ctx = $this->buildContext();
        $student = $this->makeInscription($ctx['classe']->id, $ctx['annee']->id, 'STU001');

        $file = $this->buildExcelFile($ctx, 'STU001', 'ABS');

        $service = app(NotesImportService::class);
        $parsed = $service->parseFile($file);
        $result = $service->apply($parsed, $ctx['classe']->id, $ctx['matiere']->id, 'semestre1', $ctx['annee']->id);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('esbtp_notes', [
            'etudiant_id' => $student->id,
            'evaluation_id' => $ctx['eval']->id,
            'is_absent' => 1,
        ]);
    }

    public function test_it_accepts_french_decimal_format(): void
    {
        $this->authUser();
        $ctx = $this->buildContext();
        $student = $this->makeInscription($ctx['classe']->id, $ctx['annee']->id, 'STU001');

        $file = $this->buildExcelFile($ctx, 'STU001', '12,5');

        $service = app(NotesImportService::class);
        $parsed = $service->parseFile($file);
        $result = $service->apply($parsed, $ctx['classe']->id, $ctx['matiere']->id, 'semestre1', $ctx['annee']->id);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('esbtp_notes', [
            'etudiant_id' => $student->id,
            'evaluation_id' => $ctx['eval']->id,
            'note' => 12.5,
        ]);
    }
}
