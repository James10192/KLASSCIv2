<?php

namespace Tests\Unit\Services\Chatbot\Tools;

use App\Services\Chatbot\Tools\SearchNotesTool;
use App\Services\Chatbot\Tools\SearchBulletinsTool;
use App\Services\Chatbot\Tools\SearchPaymentsTool;
use App\Services\Chatbot\Tools\SearchStudentsTool;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatbotDataScopeIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'chatbot_scope_test');
        config()->set('database.connections.chatbot_scope_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('chatbot_scope_test');
        DB::reconnect('chatbot_scope_test');

        $this->createSchema();
    }

    public function test_financial_kill_switch_refuses_access_by_default(): void
    {
        $this->assertFalse(config('chatbot.tools.search_payments.enabled'));

        $result = (new SearchPaymentsTool())->executeAuthorized([], new ChatbotScopeUser(10, ['paiements.view']));

        $this->assertSame(['error' => 'Outil indisponible.'], $result);
    }

    public function test_cashier_sees_only_own_payments_while_global_permission_sees_all(): void
    {
        config()->set('chatbot.tools.search_payments.enabled', true);
        $this->seedStudent(1, 'Paiement', 'Visible');
        $this->seedStudent(2, 'Paiement', 'Tiers');
        $this->seedInscription(1, 1, 1);
        $this->seedInscription(2, 2, 1);
        DB::table('esbtp_paiements')->insert([
            ['id' => 1, 'etudiant_id' => 1, 'inscription_id' => 1, 'created_by' => 10, 'montant' => 10000, 'status' => 'valide', 'date_paiement' => '2026-07-01', 'mode_paiement' => 'especes'],
            ['id' => 2, 'etudiant_id' => 2, 'inscription_id' => 2, 'created_by' => 99, 'montant' => 20000, 'status' => 'valide', 'date_paiement' => '2026-07-02', 'mode_paiement' => 'especes'],
        ]);

        $tool = new SearchPaymentsTool();
        $cashierResult = $tool->execute([], new ChatbotScopeUser(10, ['paiements.view_own']));
        $globalResult = $tool->execute([], new ChatbotScopeUser(10, ['paiements.view']));

        $this->assertSame(1, $cashierResult['total']);
        $this->assertSame('Paiement Visible', $cashierResult['results'][0]['inscription']['etudiant']);
        $this->assertSame(2, $globalResult['total']);
    }

    public function test_teacher_student_scope_uses_pivot_and_lmd_principal_without_leaking_other_classes(): void
    {
        config()->set('chatbot.tools.search_students.enabled', true);
        $this->seedAcademicContext();
        $this->seedTeacher(1, 50);
        $this->seedTeacher(2, 60);
        $this->seedPlanification(1, 1, 1, 1);
        $this->seedPlanification(2, 2, 2, 2, 50);
        $this->seedPlanification(3, 3, 3, 1);
        DB::table('esbtp_planification_teachers')->insert([
            ['planification_id' => 1, 'teacher_id' => 1],
            ['planification_id' => 3, 'teacher_id' => 2],
        ]);
        $this->seedStudent(1, 'Pivot', 'Affecte');
        $this->seedStudent(2, 'Principal', 'Lmd');
        $this->seedStudent(3, 'Hors', 'Perimetre');
        $this->seedInscription(1, 1, 1);
        $this->seedInscription(2, 2, 2);
        $this->seedInscription(3, 3, 3);

        $result = (new SearchStudentsTool())->execute([], new ChatbotScopeUser(50, ['students.view_own', 'identity.teach']));

        $this->assertSame(2, $result['total']);
        $this->assertSame(['Pivot Affecte', 'Principal Lmd'], collect($result['results'])->pluck('nom')->sort()->values()->all());
    }

    public function test_teacher_note_scope_uses_pivot_and_lmd_principal_without_leaking_other_subjects(): void
    {
        config()->set('chatbot.tools.search_notes.enabled', true);
        $this->seedAcademicContext();
        $this->seedTeacher(1, 50);
        $this->seedTeacher(2, 60);
        $this->seedPlanification(1, 1, 1, 1);
        $this->seedPlanification(2, 2, 2, 2, 50);
        $this->seedPlanification(3, 1, 1, 2);
        DB::table('esbtp_planification_teachers')->insert(['planification_id' => 1, 'teacher_id' => 1]);
        $this->seedStudent(1, 'Pivot', 'Note');
        $this->seedStudent(2, 'Principal', 'Note');
        $this->seedStudent(3, 'Hors', 'Note');
        $this->seedInscription(1, 1, 1);
        $this->seedInscription(2, 2, 2);
        $this->seedInscription(3, 3, 1);
        $this->seedEvaluation(1, 1, 1);
        $this->seedEvaluation(2, 2, 2);
        $this->seedEvaluation(3, 1, 2);
        $this->seedNote(1, 1, 1, 1, 1);
        $this->seedNote(2, 2, 2, 2, 2);
        $this->seedNote(3, 3, 3, 1, 2);

        $result = (new SearchNotesTool())->execute([], new ChatbotScopeUser(50, ['notes.view_own', 'identity.teach']));

        $this->assertSame(2, $result['total']);
        $this->assertSame(['Pivot Note', 'Principal Note'], collect($result['results'])->pluck('etudiant')->sort()->values()->all());
    }

    public function test_student_bulletin_scope_returns_only_own_published_bulletins(): void
    {
        config()->set('chatbot.tools.search_bulletins.enabled', true);
        $this->seedAcademicContext();
        $this->seedStudent(1, 'Bulletin', 'Visible', 50);
        $this->seedStudent(2, 'Bulletin', 'Tiers', 99);
        $this->seedInscription(1, 1, 1);
        $this->seedInscription(2, 2, 1);
        DB::table('esbtp_bulletins')->insert([
            ['id' => 1, 'etudiant_id' => 1, 'classe_id' => 1, 'annee_universitaire_id' => 1, 'periode' => 'semestre1', 'moyenne_generale' => 14, 'rang' => 1, 'effectif_classe' => 2, 'is_published' => true, 'created_at' => '2026-07-01', 'updated_at' => '2026-07-01'],
            ['id' => 2, 'etudiant_id' => 2, 'classe_id' => 1, 'annee_universitaire_id' => 1, 'periode' => 'semestre1', 'moyenne_generale' => 13, 'rang' => 2, 'effectif_classe' => 2, 'is_published' => true, 'created_at' => '2026-07-02', 'updated_at' => '2026-07-02'],
            ['id' => 3, 'etudiant_id' => 1, 'classe_id' => 1, 'annee_universitaire_id' => 1, 'periode' => 'semestre2', 'moyenne_generale' => 15, 'rang' => 1, 'effectif_classe' => 2, 'is_published' => false, 'created_at' => '2026-07-03', 'updated_at' => '2026-07-03'],
        ]);

        $tool = new SearchBulletinsTool();
        $ownResult = $tool->execute(['published_only' => false], new ChatbotScopeUser(50, ['bulletins.view_own']));
        $globalResult = $tool->execute([], new ChatbotScopeUser(50, ['bulletins.view']));

        $this->assertSame(1, $ownResult['total']);
        $this->assertSame('Bulletin Visible', $ownResult['results'][0]['nom']);
        $this->assertNull($ownResult['deep_link']);
        $this->assertSame(3, $globalResult['total']);
    }

    private function seedAcademicContext(): void
    {
        DB::table('esbtp_annee_universitaires')->insert(['id' => 1, 'name' => '2025-2026', 'is_current' => true, 'is_active' => true]);
        DB::table('esbtp_filieres')->insert([
            ['id' => 1, 'name' => 'Informatique'], ['id' => 2, 'name' => 'Gestion'], ['id' => 3, 'name' => 'Droit'],
        ]);
        DB::table('esbtp_niveau_etudes')->insert([
            ['id' => 1, 'name' => 'Niveau 1'], ['id' => 2, 'name' => 'Niveau 2'],
        ]);
        DB::table('esbtp_matieres')->insert([
            ['id' => 1, 'name' => 'Algorithmique'], ['id' => 2, 'name' => 'Comptabilite'],
        ]);
        DB::table('esbtp_classes')->insert([
            ['id' => 1, 'name' => 'Info 1', 'filiere_id' => 1, 'niveau_etude_id' => 1],
            ['id' => 2, 'name' => 'Gestion 1', 'filiere_id' => 2, 'niveau_etude_id' => 2],
            ['id' => 3, 'name' => 'Droit 1', 'filiere_id' => 3, 'niveau_etude_id' => 1],
        ]);
    }

    private function seedTeacher(int $id, int $userId): void
    {
        DB::table('esbtp_teachers')->insert(['id' => $id, 'user_id' => $userId, 'employee_id' => "EMP-{$id}"]);
    }

    private function seedPlanification(int $id, int $filiereId, int $niveauId, int $matiereId, ?int $principalId = null): void
    {
        DB::table('esbtp_planifications_academiques')->insert([
            'id' => $id,
            'annee_universitaire_id' => 1,
            'filiere_id' => $filiereId,
            'niveau_etude_id' => $niveauId,
            'matiere_id' => $matiereId,
            'enseignant_principal_id' => $principalId,
            'is_active' => true,
        ]);
    }

    private function seedStudent(int $id, string $nom, string $prenoms, ?int $userId = null): void
    {
        DB::table('esbtp_etudiants')->insert(['id' => $id, 'user_id' => $userId, 'matricule' => "ETU-{$id}", 'nom' => $nom, 'prenoms' => $prenoms]);
    }

    private function seedInscription(int $id, int $studentId, int $classId): void
    {
        DB::table('esbtp_inscriptions')->insert([
            'id' => $id, 'etudiant_id' => $studentId, 'classe_id' => $classId, 'annee_universitaire_id' => 1,
            'status' => 'active', 'workflow_step' => 'etudiant_cree', 'date_inscription' => '2026-01-01',
        ]);
    }

    private function seedEvaluation(int $id, int $classId, int $matiereId): void
    {
        DB::table('esbtp_evaluations')->insert([
            'id' => $id, 'classe_id' => $classId, 'matiere_id' => $matiereId, 'annee_universitaire_id' => 1,
            'titre' => "Evaluation {$id}", 'bareme' => 20,
        ]);
    }

    private function seedNote(int $id, int $evaluationId, int $studentId, int $classId, int $matiereId): void
    {
        DB::table('esbtp_notes')->insert([
            'id' => $id, 'evaluation_id' => $evaluationId, 'etudiant_id' => $studentId, 'classe_id' => $classId,
            'matiere_id' => $matiereId, 'note' => 15, 'type_evaluation' => 'devoir', 'semestre' => 'semestre1', 'is_absent' => false,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('esbtp_annee_universitaires', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_current');
            $table->boolean('is_active');
            $table->softDeletes();
        });
        Schema::create('esbtp_filieres', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('esbtp_niveau_etudes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('esbtp_matieres', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('esbtp_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('filiere_id');
            $table->unsignedBigInteger('niveau_etude_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('esbtp_teachers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('employee_id')->nullable();
        });
        Schema::create('esbtp_planifications_academiques', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->unsignedBigInteger('filiere_id');
            $table->unsignedBigInteger('niveau_etude_id');
            $table->unsignedBigInteger('matiere_id')->nullable();
            $table->unsignedBigInteger('enseignant_principal_id')->nullable();
            $table->boolean('is_active');
            $table->softDeletes();
        });
        Schema::create('esbtp_planification_teachers', function (Blueprint $table): void {
            $table->unsignedBigInteger('planification_id');
            $table->unsignedBigInteger('teacher_id');
        });
        Schema::create('esbtp_etudiants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('matricule')->nullable();
            $table->string('nom');
            $table->string('prenoms');
            $table->softDeletes();
        });
        Schema::create('esbtp_inscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->string('status');
            $table->string('workflow_step');
            $table->date('date_inscription');
            $table->string('type_inscription')->nullable();
            $table->softDeletes();
        });
        Schema::create('esbtp_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('matiere_id');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->string('titre');
            $table->decimal('bareme', 8, 2);
            $table->softDeletes();
        });
        Schema::create('esbtp_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('evaluation_id');
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('matiere_id');
            $table->decimal('note', 8, 2);
            $table->string('type_evaluation');
            $table->string('semestre');
            $table->boolean('is_absent');
            $table->softDeletes('archived_at');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('esbtp_frais_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('esbtp_paiements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('inscription_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('frais_category_id')->nullable();
            $table->decimal('montant', 12, 2);
            $table->string('status');
            $table->date('date_paiement');
            $table->string('mode_paiement')->nullable();
            $table->unsignedInteger('tranche')->nullable();
            $table->softDeletes();
        });
        Schema::create('esbtp_bulletins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->string('periode')->nullable();
            $table->decimal('moyenne_generale', 5, 2)->nullable();
            $table->unsignedInteger('rang')->nullable();
            $table->unsignedInteger('effectif_classe')->nullable();
            $table->boolean('signature_responsable')->default(false);
            $table->boolean('signature_directeur')->default(false);
            $table->boolean('signature_parent')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->softDeletes('archived_at');
        });
    }
}

class ChatbotScopeUser
{
    public function __construct(public int $id, private array $permissions)
    {
    }

    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}
