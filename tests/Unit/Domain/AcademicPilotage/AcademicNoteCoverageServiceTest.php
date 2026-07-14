<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicNoteCoverageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicNoteCoverageServiceTest extends AcademicPilotageDatabaseTestCase
{
    public function test_it_summarizes_subject_evaluation_student_and_actor_note_coverage(): void
    {
        $this->createReferenceTables();

        DB::table('users')->insert([
            ['id' => 41, 'name' => 'Awa Koffi'],
            ['id' => 42, 'name' => 'Mariam Yao'],
        ]);
        DB::table('esbtp_classes')->insert([
            ['id' => 10, 'name' => 'BTS1 CG A', 'code' => 'CG-A', 'filiere_id' => 100, 'niveau_etude_id' => 200, 'systeme_academique' => 'BTS', 'is_active' => true],
        ]);
        DB::table('esbtp_matieres')->insert([
            ['id' => 501, 'name' => 'Comptabilite', 'code' => 'CPT', 'is_active' => true],
            ['id' => 502, 'name' => 'Anglais', 'code' => 'ANG', 'is_active' => true],
        ]);
        DB::table('esbtp_matiere_filiere_niveau')->insert([
            ['matiere_id' => 501, 'filiere_id' => 100, 'niveau_etude_id' => 200],
            ['matiere_id' => 502, 'filiere_id' => 100, 'niveau_etude_id' => 200],
        ]);
        DB::table('esbtp_etudiants')->insert([
            ['id' => 301, 'nom' => 'Kouadio', 'prenoms' => 'Awa', 'matricule' => 'M301'],
            ['id' => 302, 'nom' => 'Yao', 'prenoms' => 'Eric', 'matricule' => 'M302'],
        ]);
        DB::table('esbtp_inscriptions')->insert([
            ['etudiant_id' => 301, 'classe_id' => 10, 'annee_universitaire_id' => 20, 'status' => 'active', 'workflow_step' => 'etudiant_cree', 'created_at' => now(), 'updated_at' => now()],
            ['etudiant_id' => 302, 'classe_id' => 10, 'annee_universitaire_id' => 20, 'status' => 'active', 'workflow_step' => 'etudiant_cree', 'created_at' => now(), 'updated_at' => now()],
            ['etudiant_id' => 999, 'classe_id' => 10, 'annee_universitaire_id' => 20, 'status' => 'en_attente', 'workflow_step' => 'prospect', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('esbtp_evaluations')->insert([
            ['id' => 701, 'titre' => 'Devoir 1', 'classe_id' => 10, 'matiere_id' => 501, 'annee_universitaire_id' => 20, 'periode' => 'semestre1', 'status' => 'completed', 'type' => 'devoir', 'date_evaluation' => now()],
            ['id' => 702, 'titre' => 'Oral', 'classe_id' => 10, 'matiere_id' => 502, 'annee_universitaire_id' => 20, 'periode' => 'semestre1', 'status' => 'completed', 'type' => 'devoir', 'date_evaluation' => now()],
            ['id' => 703, 'titre' => 'Annule', 'classe_id' => 10, 'matiere_id' => 502, 'annee_universitaire_id' => 20, 'periode' => 'semestre1', 'status' => 'cancelled', 'type' => 'devoir', 'date_evaluation' => now()],
        ]);
        DB::table('esbtp_notes')->insert([
            ['evaluation_id' => 701, 'etudiant_id' => 301, 'matiere_id' => 501, 'note' => 14, 'is_absent' => false, 'created_by' => 41, 'updated_by' => 42, 'created_at' => now()->subMinute(), 'updated_at' => now()],
            ['evaluation_id' => 701, 'etudiant_id' => 302, 'matiere_id' => 501, 'note' => 0, 'is_absent' => true, 'created_by' => 41, 'updated_by' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $result = app(AcademicNoteCoverageService::class)->summarize(20, 'semestre1', 'BTS', 10);

        $this->assertSame(2, $result['summary']['subjects_total']);
        $this->assertSame(1, $result['summary']['subjects_evaluated']);
        $this->assertSame(0, $result['summary']['orphan_subjects']);
        $this->assertSame(2, $result['summary']['evaluations_total']);
        $this->assertSame(2, $result['summary']['students_expected']);
        $this->assertSame(4, $result['summary']['expected_results']);
        $this->assertSame(2, $result['summary']['treated_results']);
        $this->assertSame(2, $result['summary']['missing_results']);
        $this->assertSame(2, $result['summary']['incomplete_students']);
        $this->assertSame(2, $result['summary']['actors_count']);

        $comptabilite = collect($result['subjects'])->firstWhere('id', 501);
        $this->assertSame(2, $comptabilite['treated_count']);
        $this->assertSame(0, $comptabilite['missing_count']);
        $this->assertSame(1, $comptabilite['numeric_count']);
        $this->assertSame(1, $comptabilite['absent_count']);

        $anglais = collect($result['subjects'])->firstWhere('id', 502);
        $this->assertSame(2, $anglais['missing_count']);
        $this->assertSame(['Kouadio Awa', 'Yao Eric'], collect($anglais['missing_students'])->pluck('name')->all());
    }

    private function createReferenceTables(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        Schema::create('esbtp_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedBigInteger('filiere_id')->nullable();
            $table->unsignedBigInteger('niveau_etude_id')->nullable();
            $table->string('systeme_academique')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_matieres', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_matiere_filiere_niveau', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('matiere_id');
            $table->unsignedBigInteger('filiere_id');
            $table->unsignedBigInteger('niveau_etude_id');
        });
        Schema::create('esbtp_etudiants', function (Blueprint $table): void {
            $table->id();
            $table->string('nom');
            $table->string('prenoms')->nullable();
            $table->string('matricule')->nullable();
            $table->softDeletes();
        });
        Schema::table('esbtp_evaluations', function (Blueprint $table): void {
            $table->string('titre')->nullable();
            $table->string('type')->nullable();
            $table->dateTime('date_evaluation')->nullable();
            $table->string('status')->nullable();
            $table->softDeletes();
        });
        Schema::table('esbtp_notes', function (Blueprint $table): void {
            $table->decimal('note', 8, 2)->nullable();
        });
    }
}
