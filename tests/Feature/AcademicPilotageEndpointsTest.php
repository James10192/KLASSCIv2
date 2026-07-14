<?php

namespace Tests\Feature;

use App\Domain\AcademicPilotage\DTO\AcademicAlertCandidate;
use App\Domain\AcademicPilotage\Enums\AcademicAlertSeverity;
use App\Domain\AcademicPilotage\Enums\AcademicAlertType;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Models\GradeSheetDocument;
use App\Domain\AcademicPilotage\Models\GradeSheetEvent;
use App\Domain\AcademicPilotage\Services\AcademicAlertEngineService;
use App\Domain\AcademicPilotage\Services\GradeSheetDocumentDownloadService;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\ContractExpiryMiddleware;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\LogRequestMiddleware;
use App\Http\Middleware\LogRequests;
use App\Http\Middleware\PaywallMiddleware;
use App\Http\Middleware\RouteDebugMiddleware;
use App\Http\Middleware\UpdateLastLogin;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Unit\Domain\AcademicPilotage\AcademicPilotageDatabaseTestCase;

class AcademicPilotageEndpointsTest extends AcademicPilotageDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->withoutMiddleware([
            CheckInstalled::class,
            EnsureInstalled::class,
            ForcePasswordChange::class,
            PaywallMiddleware::class,
            UpdateLastLogin::class,
            ContractExpiryMiddleware::class,
            LogRequests::class,
            LogRequestMiddleware::class,
            RouteDebugMiddleware::class,
        ]);
        $this->actingAs($this->actor(50));
    }

    public function test_module_permission_failure_is_json_without_accept_header(): void
    {
        $response = $this->post(route('esbtp.academic-sheets.store'));

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_validation_failure_is_json_without_accept_header(): void
    {
        $this->allowAllAbilities();

        $response = $this->post(route('esbtp.academic-sheets.store'));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['evaluation_id', 'entry_mode']);
    }

    public function test_stale_transition_uses_route_binding_and_stable_json_error(): void
    {
        $this->allowAllAbilities();
        $sheet = $this->createGradeSheet(['lock_version' => 2]);

        $response = $this->post(route('esbtp.academic-sheets.transition', $sheet), [
            'action' => 'start_entry',
            'expected_lock_version' => 1,
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'academic_pilotage.stale_grade_sheet')
            ->assertJsonPath('details.actual_lock_version', 2);
    }

    public function test_alert_transition_rejects_an_invalid_state_change_with_a_stable_json_error(): void
    {
        $this->allowAllAbilities();
        $alert = app(AcademicAlertEngineService::class)->upsert(new AcademicAlertCandidate(
            type: AcademicAlertType::MISSING_GRADE,
            severity: AcademicAlertSeverity::BLOCKING,
            academicYearId: 20,
            semester: 'semestre1',
            classId: 10,
            studentId: 101,
            subjectId: null,
            teacherId: null,
            message: 'Une note attendue est manquante.',
            recommendedAction: 'Completez la fiche.',
            metadata: ['missing_entries' => 1],
        ));

        $response = $this->post(route('esbtp.academic-alerts.transition', $alert), [
            'status' => 'in_progress',
            'reason' => 'Tentative de traitement prematuree',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'academic_pilotage.invalid_transition')
            ->assertJsonPath('details.from', 'open')
            ->assertJsonPath('details.to', 'in_progress')
            ->assertJsonPath('details.allowed_transitions', ['acknowledged', 'dismissed']);
    }

    public function test_document_upload_is_audited_and_bumps_version(): void
    {
        $this->allowAllAbilities();
        $sheet = $this->createGradeSheet();

        $response = $this->post(route('esbtp.academic-sheets.documents.upload', $sheet), [
            'expected_lock_version' => 1,
            'document' => UploadedFile::fake()->createWithContent('fiche.pdf', '%PDF-1.4'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('grade_sheet_lock_version', 2)
            ->assertJsonPath('document.original_name', 'fiche.pdf');
        $this->assertSame('document_uploaded', GradeSheetEvent::query()->sole()->event_type);
        $this->assertSame(2, $sheet->fresh()->lock_version);
    }

    public function test_signed_document_download_streams_private_file(): void
    {
        $this->allowAllAbilities();
        $sheet = $this->createGradeSheet(['status' => GradeSheetStatus::RECEIVED->value]);
        $path = "academic-pilotage/grade-sheets/{$sheet->id}/fiche.pdf";
        Storage::disk('local')->put($path, '%PDF-1.4');
        $id = DB::table('esbtp_grade_sheet_documents')->insertGetId([
            'grade_sheet_id' => $sheet->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'fiche.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 8,
            'checksum_sha256' => hash('sha256', '%PDF-1.4'),
            'uploaded_by' => 50,
            'uploaded_at' => now(),
        ]);
        $document = GradeSheetDocument::query()->findOrFail($id);
        $frameworkOnlyUrl = URL::temporarySignedRoute(
            'esbtp.academic-sheets.documents.download',
            now()->addMinutes(5),
            ['document' => $document->id],
        );
        $this->get($frameworkOnlyUrl)->assertForbidden();

        $url = app(GradeSheetDocumentDownloadService::class)->signedUrl($document);

        $this->get($url)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function allowAllAbilities(): void
    {
        Gate::before(fn (): bool => true);
    }
}
