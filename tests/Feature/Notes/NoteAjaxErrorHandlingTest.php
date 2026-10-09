<?php

namespace Tests\Feature\Notes;

use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\Notes\SaisieGroupeeDeNotes;
use App\Http\Controllers\ESBTPNoteController;
use App\Http\Requests\Notes\StoreBulkNotesRequest;
use App\Models\User;
use App\Services\Notes\NoteStudentCohortService;
use App\Services\Notes\NoteSubmissionSynchronizationService;
use App\Services\NotesWindowGuard;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Les exceptions métiers bloquant une note doivent garder leur réponse 409.
 * Aucune connexion à une base tenant n'est nécessaire : la saisie est simulée.
 */
class NoteAjaxErrorHandlingTest extends TestCase
{
    public function test_validated_grade_sheet_returns_409_instead_of_a_generic_500(): void
    {
        $controller = $this->controllerWithFailure(AcademicPilotageException::validatedNoteLocked(42));

        $response = $controller->saveNotesAjaxBulk($this->requestForBulkSave());

        $this->assertSame(409, $response->status());
        $this->assertSame(
            AcademicPilotageException::VALIDATED_NOTE_LOCKED,
            $response->getData(true)['error']
        );
        $this->assertStringContainsString('Rouvrez la fiche', $response->getData(true)['message']);
    }

    public function test_locked_exam_returns_409_with_actionable_message(): void
    {
        $controller = $this->controllerWithFailure(AcademicPilotageException::examNotesLocked(15));

        $response = $controller->saveNotesAjaxBulk($this->requestForBulkSave());

        $this->assertSame(409, $response->status());
        $this->assertSame(
            AcademicPilotageException::EXAM_NOTES_LOCKED,
            $response->getData(true)['error']
        );
        $this->assertStringContainsString('déverrouillage', $response->getData(true)['message']);
    }

    public function test_unexpected_failure_is_private_and_has_a_log_correlation_id(): void
    {
        $controller = $this->controllerWithFailure(new RuntimeException('SQLSTATE internal sensitive details'));

        $response = $controller->saveNotesAjaxBulk($this->requestForBulkSave());
        $body = $response->getData(true);

        $this->assertSame(500, $response->status());
        $this->assertFalse($body['success']);
        $this->assertNotEmpty($body['incident_id']);
        $this->assertStringContainsString($body['incident_id'], $body['message']);
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
    }

    public function test_php_error_is_reported_as_a_traceable_500_too(): void
    {
        $controller = $this->controllerWithFailure(new \Error('Unexpected PHP type error with internal details'));

        $response = $controller->saveNotesAjaxBulk($this->requestForBulkSave());
        $body = $response->getData(true);

        $this->assertSame(500, $response->status());
        $this->assertNotEmpty($body['incident_id']);
        $this->assertStringNotContainsString('Unexpected PHP type error', $response->getContent());
    }

    private function requestForBulkSave(): StoreBulkNotesRequest
    {
        return StoreBulkNotesRequest::create('/esbtp/notes/save-ajax-bulk', 'POST', [
            'notes' => [
                ['evaluation_id' => 101, 'etudiant_id' => 201, 'note' => 12.5],
            ],
        ]);
    }

    private function controllerWithFailure(\Throwable $failure): ESBTPNoteController
    {
        $service = Mockery::mock(SaisieGroupeeDeNotes::class);
        $service->shouldReceive('enregistrer')->once()->andThrow($failure);
        $this->app->instance(SaisieGroupeeDeNotes::class, $service);

        Auth::shouldReceive('user')->andReturn(new User);
        Auth::shouldReceive('id')->andReturn(1);

        return new ESBTPNoteController(
            Mockery::mock(NotificationService::class),
            Mockery::mock(NoteStudentCohortService::class),
            Mockery::mock(NotesWindowGuard::class),
            Mockery::mock(NoteSubmissionSynchronizationService::class),
        );
    }
}
