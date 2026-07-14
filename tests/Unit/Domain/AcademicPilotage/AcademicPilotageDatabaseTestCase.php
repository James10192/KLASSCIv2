<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetEntryMode;
use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class AcademicPilotageDatabaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('audit.enabled', false);
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->createMinimalSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    protected function createGradeSheet(array $attributes = []): GradeSheet
    {
        $now = now();
        $id = DB::table('esbtp_grade_sheets')->insertGetId(array_merge([
            'code' => 'GS-'.uniqid(),
            'classe_id' => 10,
            'annee_universitaire_id' => 20,
            'evaluation_id' => null,
            'entry_mode' => GradeSheetEntryMode::DIRECT->value,
            'status' => GradeSheetStatus::EXPECTED->value,
            'lock_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], $attributes));

        return GradeSheet::query()->findOrFail($id);
    }

    protected function createEntry(GradeSheet $sheet, int $studentId, array $attributes = []): int
    {
        $now = now();

        return DB::table('esbtp_grade_sheet_entries')->insertGetId(array_merge([
            'grade_sheet_id' => $sheet->id,
            'etudiant_id' => $studentId,
            'note_id' => null,
            'status' => GradeSheetEntryStatus::EXPECTED->value,
            'source' => 'workflow',
            'entered_by' => null,
            'validated_by' => null,
            'resolved_at' => null,
            'metadata' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $attributes));
    }

    protected function actor(int $id): User
    {
        $actor = new User;
        $actor->setRawAttributes(['id' => $id], true);
        $actor->exists = true;

        return $actor;
    }

    private function createMinimalSchema(): void
    {
        Schema::create('esbtp_grade_sheets', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('obligation_key')->nullable()->unique();
            $table->unsignedBigInteger('evaluation_id')->nullable();
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('matiere_id')->nullable();
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->unsignedBigInteger('assigned_processor_id')->nullable();
            $table->string('academic_system')->nullable();
            $table->string('semester')->nullable();
            $table->string('evaluation_type')->nullable();
            $table->string('entry_mode');
            $table->string('status');
            $table->dateTime('expected_at')->nullable();
            $table->string('source')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('entry_started_at')->nullable();
            $table->dateTime('entered_at')->nullable();
            $table->dateTime('controlled_at')->nullable();
            $table->dateTime('validated_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->unsignedBigInteger('entered_by')->nullable();
            $table->unsignedBigInteger('controlled_by')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_grade_sheet_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('grade_sheet_id');
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('note_id')->nullable();
            $table->string('status');
            $table->string('source');
            $table->unsignedBigInteger('entered_by')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['grade_sheet_id', 'etudiant_id']);
        });

        Schema::create('esbtp_grade_sheet_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('grade_sheet_id');
            $table->string('event_type');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');
        });

        Schema::create('esbtp_inscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->string('status');
            $table->string('workflow_step');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('evaluation_id');
            $table->unsignedBigInteger('etudiant_id');
            $table->boolean('is_absent')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('matiere_id')->nullable();
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_teachers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
        });

        Schema::create('esbtp_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->unsignedBigInteger('enseignant_id')->nullable();
            $table->unsignedBigInteger('matiere_id')->nullable();
            $table->string('periode')->nullable();
        });

        Schema::create('esbtp_emploi_temps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('annee_universitaire_id');
        });

        Schema::create('esbtp_seance_cours', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('emploi_temps_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
        });

        Schema::create('esbtp_academic_actor_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->string('responsibility');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique([
                'user_id',
                'classe_id',
                'annee_universitaire_id',
                'responsibility',
            ]);
        });

        Schema::create('esbtp_grade_sheet_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('grade_sheet_id');
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum_sha256');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->dateTime('uploaded_at');
            $table->unique(['disk', 'path']);
        });

        Schema::create('esbtp_academic_metric_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('context_hash')->unique();
            $table->string('scope_type');
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('academic_system')->nullable();
            $table->unsignedBigInteger('annee_universitaire_id')->nullable();
            $table->string('semester')->nullable();
            $table->unsignedBigInteger('classe_id')->nullable();
            $table->unsignedBigInteger('etudiant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('academic_score', 5, 2)->nullable();
            $table->decimal('operational_score', 5, 2)->nullable();
            $table->unsignedTinyInteger('coverage_pct')->default(0);
            $table->unsignedTinyInteger('confidence_pct')->default(0);
            $table->string('level')->default('insufficient_data');
            $table->json('metrics');
            $table->json('factors')->nullable();
            $table->json('reasons')->nullable();
            $table->string('evidence_hash');
            $table->string('engine_version')->default('1');
            $table->boolean('is_dirty')->default(false);
            $table->unsignedBigInteger('source_revision')->default(0);
            $table->string('refresh_token', 64)->nullable();
            $table->dateTime('refresh_started_at')->nullable();
            $table->unsignedInteger('refresh_attempts')->default(0);
            $table->string('last_refresh_error', 255)->nullable();
            $table->dateTime('stale_at')->nullable();
            $table->dateTime('calculated_at');
            $table->timestamps();
        });

        Schema::create('esbtp_academic_alerts', function (Blueprint $table): void {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->string('type', 64);
            $table->string('severity', 20)->default('warning');
            $table->string('status', 24)->default('open');
            $table->unsignedBigInteger('annee_universitaire_id')->nullable();
            $table->string('semester', 20)->nullable();
            $table->unsignedBigInteger('classe_id')->nullable();
            $table->unsignedBigInteger('etudiant_id')->nullable();
            $table->unsignedBigInteger('matiere_id')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->string('entity_type', 120)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('message');
            $table->text('recommended_action')->nullable();
            $table->json('metadata')->nullable();
            $table->string('source_version', 24)->default('1');
            $table->dateTime('detected_at');
            $table->dateTime('last_seen_at');
            $table->dateTime('acknowledged_at')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->dateTime('dismissed_at')->nullable();
            $table->unsignedBigInteger('dismissed_by')->nullable();
            $table->timestamps();
        });

        Schema::create('esbtp_academic_alert_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('academic_alert_id');
            $table->string('event_type', 40);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }
}
