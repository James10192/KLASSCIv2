<?php

namespace Tests\Feature\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetEvent;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\ContractExpiryMiddleware;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\LogRequestMiddleware;
use App\Http\Middleware\LogRequests;
use App\Http\Middleware\PaywallMiddleware;
use App\Http\Middleware\RouteDebugMiddleware;
use App\Http\Middleware\UpdateLastLogin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\Unit\Domain\AcademicPilotage\AcademicPilotageDatabaseTestCase;

class GradeSheetDetailEndpointsTest extends AcademicPilotageDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createDetailSchema();
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
        Gate::before(fn (): bool => true);
    }

    public function test_show_returns_the_complete_detail_contract_and_allowed_actions(): void
    {
        $sheet = $this->createGradeSheet([
            'evaluation_id' => 77,
            'matiere_id' => 30,
            'teacher_id' => 5,
            'assigned_processor_id' => 50,
            'academic_system' => 'LMD',
            'semester' => 'semestre1',
            'evaluation_type' => 'devoir',
            'lock_version' => 7,
        ]);
        $this->seedDetailRelations($sheet->id);

        $response = $this->get(route('esbtp.academic-sheets.show', $sheet));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('grade_sheet.id', $sheet->id)
            ->assertJsonPath('grade_sheet.lock_version', 7)
            ->assertJsonPath('grade_sheet.classe', 'L1-A · L1 A')
            ->assertJsonPath('grade_sheet.matiere', 'Mathematiques')
            ->assertJsonPath('grade_sheet.teacher', 'Marie Kouame')
            ->assertJsonPath('grade_sheet.assigned_processor', 'Marie Kouame')
            ->assertJsonPath('progress.total_entries', 1)
            ->assertJsonPath('progress.resolved_entries', 1)
            ->assertJsonPath('progress.completion_pct', 100)
            ->assertJsonPath('entries.0.student.matricule', 'ETU-001')
            ->assertJsonPath('entries.0.note_evidence.note', '14.5')
            ->assertJsonPath('entries.0.note_evidence.commentaire', 'Bon travail')
            ->assertJsonPath('documents.0.original_name', 'fiche.pdf')
            ->assertJsonPath('events.0.type', 'created')
            ->assertJsonFragment([
                'action' => 'start_entry',
                'authorization_ability' => 'enter',
                'target_status' => 'in_entry',
            ]);
        $this->assertSame(
            $response->json('grade_sheet.allowed_actions'),
            $response->json('allowed_actions'),
        );
    }

    public function test_transition_returns_the_new_status_and_incremented_lock_version(): void
    {
        $sheet = $this->createGradeSheet();

        $response = $this->post(route('esbtp.academic-sheets.transition', $sheet), [
            'action' => 'start_entry',
            'expected_lock_version' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('grade_sheet.status', GradeSheetStatus::IN_ENTRY->value)
            ->assertJsonPath('grade_sheet.lock_version', 2);
        $this->assertSame(GradeSheetStatus::IN_ENTRY, $sheet->fresh()->status);
        $this->assertSame(2, $sheet->fresh()->lock_version);
    }

    public function test_transition_rejects_a_stale_lock_version_with_a_json_conflict(): void
    {
        $sheet = $this->createGradeSheet(['lock_version' => 2]);

        $response = $this->post(route('esbtp.academic-sheets.transition', $sheet), [
            'action' => 'start_entry',
            'expected_lock_version' => 1,
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'academic_pilotage.stale_grade_sheet')
            ->assertJsonPath('details.expected_lock_version', 1)
            ->assertJsonPath('details.actual_lock_version', 2);
        $this->assertSame(GradeSheetStatus::EXPECTED, $sheet->fresh()->status);
        $this->assertSame(0, GradeSheetEvent::query()->count());
    }

    private function createDetailSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email');
        });
        Schema::create('esbtp_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->softDeletes();
        });
        Schema::create('esbtp_matieres', function (Blueprint $table): void {
            $table->id();
            // Voir AcademicNoteCoverageServiceTest : sans cette colonne, SQLite rend
            // `btsOnly()` silencieusement vide au lieu d'echouer.
            $table->unsignedBigInteger('unite_enseignement_id')->nullable();
            $table->string('name');
            $table->string('code');
            $table->softDeletes();
        });
        Schema::create('esbtp_etudiants', function (Blueprint $table): void {
            $table->id();
            $table->string('nom');
            $table->string('prenoms');
            $table->string('matricule');
            $table->softDeletes();
        });

        Schema::table('esbtp_notes', function (Blueprint $table): void {
            $table->decimal('note', 5, 2)->nullable();
            $table->text('commentaire')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    private function seedDetailRelations(int $sheetId): void
    {
        $now = now();
        DB::table('users')->insert([
            'id' => 50,
            'name' => 'Marie Kouame',
            'first_name' => 'Marie',
            'last_name' => 'Kouame',
            'email' => 'marie@example.test',
        ]);
        DB::table('esbtp_classes')->insert(['id' => 10, 'name' => 'L1 A', 'code' => 'L1-A']);
        DB::table('esbtp_matieres')->insert(['id' => 30, 'name' => 'Mathematiques', 'code' => 'MATH']);
        DB::table('esbtp_teachers')->insert(['id' => 5, 'user_id' => 50]);
        DB::table('esbtp_etudiants')->insert([
            'id' => 101,
            'nom' => 'Yao',
            'prenoms' => 'Awa',
            'matricule' => 'ETU-001',
        ]);
        DB::table('esbtp_notes')->insert([
            'id' => 201,
            'evaluation_id' => 77,
            'etudiant_id' => 101,
            'note' => 14.5,
            'is_absent' => false,
            'commentaire' => 'Bon travail',
            'created_by' => 50,
            'updated_by' => 50,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->createEntry(GradeSheet::query()->findOrFail($sheetId), 101, [
            'note_id' => 201,
            'status' => GradeSheetEntryStatus::ENTERED->value,
            'entered_by' => 50,
            'resolved_at' => $now,
        ]);
        DB::table('esbtp_grade_sheet_documents')->insert([
            'grade_sheet_id' => $sheetId,
            'disk' => 'local',
            'path' => "academic-pilotage/grade-sheets/{$sheetId}/fiche.pdf",
            'original_name' => 'fiche.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 8,
            'checksum_sha256' => hash('sha256', '%PDF-1.4'),
            'uploaded_by' => 50,
            'uploaded_at' => $now,
        ]);
        DB::table('esbtp_grade_sheet_events')->insert([
            'grade_sheet_id' => $sheetId,
            'event_type' => 'created',
            'actor_id' => 50,
            'metadata' => json_encode(['source' => 'test']),
            'occurred_at' => $now,
        ]);
    }

}
