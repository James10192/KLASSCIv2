<?php

namespace Tests\Feature\Notes;

use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use App\Domain\Notes\SaisieGroupeeDeNotes;
use App\Http\Controllers\ESBTPNoteController;
use App\Http\Requests\Notes\StoreBulkNotesRequest;
use App\Models\User;
use App\Services\Notes\NoteStudentCohortService;
use App\Services\Notes\NoteSubmissionSynchronizationService;
use App\Services\NotesWindowGuard;
use App\Services\NotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

/**
 * Régression ESBTP Abidjan : la colonne VARCHAR semestre peut contenir
 * l'ancienne écriture 'semestre2' et MySQL/MariaDB strict ne doit pas la
 * convertir en DECIMAL lors de saveNotesAjaxBulk.
 *
 * Exécuter sur MariaDB (CI intégration), pas en simulant le SQL en SQLite.
 */
class NoteBulkSemesterSqlRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Base dédiée au test : schéma minimal isolant exactement le SQL fautif.
        Schema::dropIfExists('esbtp_notes');
        Schema::dropIfExists('esbtp_evaluations');

        Schema::create('esbtp_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('matiere_id');
            $table->string('periode');
            $table->softDeletes();
        });
        Schema::create('esbtp_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluation_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('matiere_id');
            $table->string('semestre')->nullable();
            $table->string('submission_status')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('esbtp_notes');
        Schema::dropIfExists('esbtp_evaluations');
        parent::tearDown();
    }

    public function test_bulk_save_normalizes_legacy_semestre2_without_sql_22007_or_deleting_notes(): void
    {
        DB::table('esbtp_evaluations')->insert([
            'id' => 235, 'classe_id' => 59, 'matiere_id' => 15, 'periode' => 'semestre2',
        ]);

        DB::table('esbtp_notes')->insert([
            ['id' => 1, 'evaluation_id' => 235, 'classe_id' => 59, 'matiere_id' => 15, 'semestre' => 'semestre2', 'submission_status' => 'submitted'],
            ['id' => 2, 'evaluation_id' => 235, 'classe_id' => 59, 'matiere_id' => 15, 'semestre' => '2', 'submission_status' => 'submitted'],
            ['id' => 3, 'evaluation_id' => 235, 'classe_id' => 59, 'matiere_id' => 15, 'semestre' => 'semestre2', 'submission_status' => 'draft'],
        ]);

        // Le vrai synchroniseur est exécuté depuis le contrôleur bulk.
        // La saisie initiale, sans rapport avec la régression, est simulée.
        $synchronizer = app(NoteSubmissionSynchronizationService::class);
        $bulk = Mockery::mock(SaisieGroupeeDeNotes::class);
        $bulk->shouldReceive('enregistrer')->once()->andReturnUsing(function ($notes, $user, $final) use ($synchronizer) {
            $this->assertTrue($final);
            $this->assertSame(235, $notes[0]['evaluation_id']);
            return [
                'saved' => 1,
                'refused' => [],
                'total' => 1,
                'synchronization' => $synchronizer->synchronize([235]),
            ];
        });
        $this->app->instance(SaisieGroupeeDeNotes::class, $bulk);

        Auth::shouldReceive('user')->andReturn(new User);
        Auth::shouldReceive('id')->andReturn(17);
        $controller = new ESBTPNoteController(
            Mockery::mock(NotificationService::class),
            Mockery::mock(NoteStudentCohortService::class),
            Mockery::mock(NotesWindowGuard::class),
            Mockery::mock(NoteSubmissionSynchronizationService::class),
        );

        $request = StoreBulkNotesRequest::create('/esbtp/notes/save-ajax-bulk', 'POST', [
            'submit_final' => 1,
            'notes' => [['evaluation_id' => 235, 'etudiant_id' => 123, 'note' => 12]],
        ]);
        $response = $controller->saveNotesAjaxBulk($request);

        $this->assertSame(200, $response->status(), $response->getContent());
        $this->assertTrue($response->getData(true)['success']);
        $this->assertSame(1, $response->getData(true)['synchronization']['notes_synchronized']);

        // L'écriture canonique historique de ce VARCHAR est '2' (voir ESBTPNote).
        $this->assertSame('2', DB::table('esbtp_notes')->where('id', 1)->value('semestre'));
        $this->assertSame('2', DB::table('esbtp_notes')->where('id', 2)->value('semestre'));
        $this->assertSame('semestre2', DB::table('esbtp_notes')->where('id', 3)->value('semestre'));
        $this->assertSame(3, DB::table('esbtp_notes')->count());
        $this->assertSame(0, $synchronizer->synchronize([235])['notes_synchronized']);
    }

    public function test_period_normalization_accepts_string_and_numeric_aliases(): void
    {
        $normalizer = app(AcademicPeriodNormalizer::class);
        foreach ([1 => 'semestre1', 2 => 'semestre2', 'semestre1' => 'semestre1', 'semestre2' => 'semestre2'] as $source => $expected) {
            $this->assertSame($expected, $normalizer->normalize($source));
        }
    }
}
