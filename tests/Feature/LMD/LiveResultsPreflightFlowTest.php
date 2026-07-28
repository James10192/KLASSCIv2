<?php

namespace Tests\Feature\LMD;

use App\Domain\AcademicPilotage\Contracts\AcademicSystemMetricsProvider;
use App\Domain\AcademicPilotage\DTO\AcademicMetricSet;
use App\Domain\AcademicPilotage\DTO\AcademicMetricValue;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Models\ESBTPEvaluation;
use App\Services\LMD\LmdBulletinProjectionService;
use App\Services\LMDBulletinService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LiveResultsPreflightFlowTest extends TestCase
{
    private string $sandboxDatabase = 'klassci_lmd_flow_test';

    protected function setUp(): void
    {
        parent::setUp();

        $this->useMysqlSandboxDatabase();
        config()->set('audit.enabled', false);
        $this->createMinimalSchema();
        $this->seedLmdDataset();
        $this->bindIncompleteReadinessProvider();
        $this->withoutMiddleware();
    }

    protected function tearDown(): void
    {
        DB::purge('mysql');
        $this->dropMysqlSandboxDatabase();

        parent::tearDown();
    }

    public function test_live_results_are_computed_from_notes_without_creating_bulletins(): void
    {
        $results = app(LmdBulletinProjectionService::class)
            ->calculerProjectionsClasse(40, 10, 1);

        $studentWithNote = $results->first(fn (array $result): bool => (int) $result['etudiant']->id === 100);
        $studentWithoutNote = $results->first(fn (array $result): bool => (int) $result['etudiant']->id === 101);

        $this->assertCount(2, $results);
        $this->assertNotNull($studentWithNote);
        $this->assertFalse($studentWithNote['has_bulletin']);
        $this->assertSame(13.5, (float) $studentWithNote['moyenne_generale']);
        $this->assertSame('complete', $studentWithNote['status']);

        $this->assertNotNull($studentWithoutNote);
        $this->assertNull($studentWithoutNote['moyenne_generale']);
        $this->assertSame('incomplete', $studentWithoutNote['status']);
        $this->assertSame(1, $studentWithoutNote['missing_ecues']);

        $this->assertSame(0, DB::table('esbtp_lmd_bulletins')->count());
    }

    public function test_generation_cohort_is_scoped_to_active_created_students_in_selected_year(): void
    {
        $ids = app(LMDBulletinService::class)
            ->studentIdsForGenerationCohort(40, 10)
            ->all();

        $this->assertSame([100, 101], $ids);
    }

    public function test_lmd_preflight_returns_actionable_blocking_json(): void
    {
        $response = $this->postJson(route('esbtp.lmd.bulletins.preflight'), [
            'mode' => 'classe',
            'classe_id' => 40,
            'annee_universitaire_id' => 10,
            'semestre' => 1,
        ]);

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'ready' => false,
            'can_generate' => false,
            'students_count' => 2,
            'ready_count' => 0,
            'blocked_count' => 2,
        ]);
        $response->assertJsonPath('blocking_errors.0.issues.0.code', 'missing_grade_entries');
        $response->assertJsonPath('blocking_errors.0.student_name', 'Kouassi Awa');
        $response->assertJsonPath('blocking_errors.0.student_matricule', 'LMD100');
        $this->assertStringContainsString('Ouvrez Notes LMD', $response->json('message'));
        $this->assertStringContainsString(
            'Certaines notes attendues manquent',
            $response->json('blocking_errors.0.issues.0.message')
        );
    }

    private function useMysqlSandboxDatabase(): void
    {
        config()->set('database.default', 'mysql');
        config()->set('academic_pilotage.student_health.weights', [
            'academic_performance' => 50,
            'assessment_completion' => 50,
        ]);
        config()->set('academic_pilotage.student_health.minimum_coverage_pct', 0);

        $serverConfig = config('database.connections.mysql');
        $serverConfig['database'] = null;
        config()->set('database.connections.mysql_server', $serverConfig);

        $database = str_replace('`', '``', $this->sandboxDatabase);
        DB::connection('mysql_server')->statement("DROP DATABASE IF EXISTS `{$database}`");
        DB::connection('mysql_server')->statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        DB::disconnect('mysql_server');

        config()->set('database.connections.mysql.database', $this->sandboxDatabase);
        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    private function dropMysqlSandboxDatabase(): void
    {
        $serverConfig = config('database.connections.mysql');
        $serverConfig['database'] = null;
        config()->set('database.connections.mysql_server', $serverConfig);

        $database = str_replace('`', '``', $this->sandboxDatabase);
        DB::connection('mysql_server')->statement("DROP DATABASE IF EXISTS `{$database}`");
        DB::disconnect('mysql_server');
    }

    private function createMinimalSchema(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->text('default_value')->nullable();
            $table->json('validation_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_restart')->default(false);
            $table->string('category')->nullable();
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('esbtp_annee_universitaires', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_niveau_etudes', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->string('type')->nullable();
            $table->unsignedTinyInteger('year')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_filieres', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->unsignedBigInteger('filiere_id')->nullable();
            $table->unsignedBigInteger('niveau_etude_id')->nullable();
            $table->unsignedBigInteger('annee_universitaire_id')->nullable();
            $table->string('systeme_academique')->default('BTS');
            $table->unsignedBigInteger('parcours_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_etudiants', function (Blueprint $table): void {
            $table->id();
            $table->string('matricule')->nullable();
            $table->string('nom')->nullable();
            $table->string('prenoms')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_inscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->string('status')->default('active');
            $table->string('workflow_step')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_lmd_parcours', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_unites_enseignement', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedTinyInteger('credit')->default(0);
            $table->unsignedTinyInteger('semestre')->default(1);
            $table->unsignedBigInteger('filiere_id')->nullable();
            $table->unsignedBigInteger('niveau_id')->nullable();
            $table->unsignedBigInteger('parcours_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_matieres', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->float('coefficient')->default(1);
            $table->float('coefficient_ecue')->nullable();
            $table->unsignedTinyInteger('credit_ecue')->default(0);
            $table->unsignedBigInteger('unite_enseignement_id')->nullable();
            $table->unsignedInteger('ordre_bulletin')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_ue_matiere', function (Blueprint $table): void {
            $table->unsignedBigInteger('unite_enseignement_id');
            $table->unsignedBigInteger('matiere_id');
            $table->float('coefficient_ecue')->default(1);
            $table->unsignedTinyInteger('credit_ecue')->default(0);
            $table->unsignedInteger('ordre_bulletin')->default(1);
            $table->timestamps();
        });

        Schema::create('esbtp_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->string('titre')->nullable();
            $table->unsignedBigInteger('matiere_id');
            $table->unsignedBigInteger('classe_id');
            $table->string('periode')->nullable();
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->string('status')->default(ESBTPEvaluation::STATUS_DRAFT);
            $table->float('coefficient')->default(1);
            $table->float('bareme')->default(20);
            $table->unsignedBigInteger('enseignant_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('evaluation_id');
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('matiere_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedTinyInteger('semestre')->nullable();
            $table->float('note')->nullable();
            $table->boolean('is_absent')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('archived_at')->nullable();
        });

        Schema::create('esbtp_lmd_bulletins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->unsignedTinyInteger('semestre');
            $table->float('moyenne_generale')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_lmd_resultats_ues', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('bulletin_id')->nullable();
            $table->unsignedBigInteger('unite_enseignement_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_lmd_resultats_ecues', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('bulletin_id')->nullable();
            $table->unsignedBigInteger('resultat_ue_id')->nullable();
            $table->unsignedBigInteger('matiere_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function seedLmdDataset(): void
    {
        $now = now();

        DB::table('esbtp_annee_universitaires')->insert([
            ['id' => 10, 'name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_current' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'name' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-07-31', 'is_current' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('esbtp_niveau_etudes')->insert(['id' => 20, 'name' => 'Licence 1', 'code' => 'L1', 'type' => 'Licence', 'year' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('esbtp_filieres')->insert(['id' => 30, 'name' => 'Genie Civil', 'code' => 'GC', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('esbtp_classes')->insert(['id' => 40, 'name' => 'L1 GC A', 'code' => 'L1-GC-A', 'filiere_id' => 30, 'niveau_etude_id' => 20, 'annee_universitaire_id' => 10, 'systeme_academique' => 'LMD', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);

        DB::table('esbtp_etudiants')->insert([
            ['id' => 100, 'matricule' => 'LMD100', 'nom' => 'Kouassi', 'prenoms' => 'Awa', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 101, 'matricule' => 'LMD101', 'nom' => 'Yao', 'prenoms' => 'Marc', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 102, 'matricule' => 'LMD102', 'nom' => 'Old', 'prenoms' => 'Year', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 103, 'matricule' => 'LMD103', 'nom' => 'Draft', 'prenoms' => 'Workflow', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('esbtp_inscriptions')->insert([
            ['id' => 200, 'etudiant_id' => 100, 'classe_id' => 40, 'annee_universitaire_id' => 10, 'status' => 'active', 'workflow_step' => 'etudiant_cree', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 201, 'etudiant_id' => 101, 'classe_id' => 40, 'annee_universitaire_id' => 10, 'status' => 'active', 'workflow_step' => 'etudiant_cree', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 202, 'etudiant_id' => 102, 'classe_id' => 40, 'annee_universitaire_id' => 11, 'status' => 'active', 'workflow_step' => 'etudiant_cree', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 203, 'etudiant_id' => 103, 'classe_id' => 40, 'annee_universitaire_id' => 10, 'status' => 'active', 'workflow_step' => 'dossier_en_cours', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('esbtp_unites_enseignement')->insert(['id' => 50, 'name' => 'Mathematiques', 'code' => 'UE-MATH', 'credit' => 5, 'semestre' => 1, 'filiere_id' => 30, 'niveau_id' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('esbtp_matieres')->insert(['id' => 60, 'name' => 'Algebre', 'code' => 'ALG', 'coefficient' => 1, 'coefficient_ecue' => 1, 'credit_ecue' => 5, 'unite_enseignement_id' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('esbtp_ue_matiere')->insert(['unite_enseignement_id' => 50, 'matiere_id' => 60, 'coefficient_ecue' => 1, 'credit_ecue' => 5, 'ordre_bulletin' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('esbtp_evaluations')->insert(['id' => 70, 'titre' => 'CC Algebre', 'matiere_id' => 60, 'classe_id' => 40, 'periode' => 'semestre1', 'annee_universitaire_id' => 10, 'status' => ESBTPEvaluation::STATUS_COMPLETED, 'coefficient' => 1, 'bareme' => 20, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('esbtp_notes')->insert(['id' => 80, 'evaluation_id' => 70, 'etudiant_id' => 100, 'matiere_id' => 60, 'classe_id' => 40, 'semestre' => 1, 'note' => 13.5, 'is_absent' => false, 'created_at' => $now, 'updated_at' => $now]);
    }

    private function bindIncompleteReadinessProvider(): void
    {
        $this->app->bind(AcademicSystemMetricsProvider::class, fn (): AcademicSystemMetricsProvider => new class implements AcademicSystemMetricsProvider {
            public function metricsFor(StudentMetricContext $context): AcademicMetricSet
            {
                return new AcademicMetricSet([
                    new AcademicMetricValue(
                        key: 'academic_performance',
                        value: 70.0,
                        confidencePct: 100,
                        evidence: [
                            'sample_count' => 1,
                            'state' => 'semester_complete',
                            'notes_count' => 1,
                            'subjects_count' => 1,
                            'coefficients_missing' => false,
                            'attendance_adjustment' => 0.0,
                            'configured_credits' => 5,
                            'expected_credits' => 30,
                            'observed_credits' => 5,
                            'published' => false,
                        ],
                        coveragePct: 100,
                    ),
                    new AcademicMetricValue(
                        key: 'assessment_completion',
                        value: 50.0,
                        confidencePct: 100,
                        evidence: [
                            'sample_count' => 2,
                            'configured_sheets' => 2,
                            'applicable_entries' => 2,
                            'resolved_entries' => 1,
                            'missing_entries' => 1,
                        ],
                        coveragePct: 100,
                        numerator: 1,
                        denominator: 2,
                    ),
                ]);
            }
        });
    }
}
