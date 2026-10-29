<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEvaluationGrades extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Vérifier si la table evaluations existe déjà
        if (!Schema::hasTable('evaluations')) {
            Schema::create('evaluations', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->enum('type', ['examen', 'devoir', 'controle', 'tp', 'projet', 'oral', 'autre'])->default('examen');
                $table->text('description')->nullable();
                $table->date('date');
                $table->integer('semester')->comment('1 ou 2');
                $table->decimal('total_points', 5, 2)->default(20.00);
                $table->decimal('passing_grade', 5, 2)->nullable();
                $table->integer('coefficient')->default(1);
                $table->boolean('is_published')->default(false);
                
                // Relations
                $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
                $table->foreignId('subject_id')->constrained('matieres')->onDelete('cascade');
                $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
                $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
                
                // Métadonnées
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Vérifier si la table student_grades existe déjà
        if (!Schema::hasTable('student_grades')) {
            Schema::create('student_grades', function (Blueprint $table) {
                $table->id();
                
                // Relations
                $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
                $table->foreignId('evaluation_id')->constrained('evaluations')->onDelete('cascade');
                
                // Données de note
                $table->decimal('grade', 5, 2)->nullable();
                $table->enum('status', ['present', 'absent', 'exempt'])->default('present');
                $table->text('comment')->nullable();
                
                // Métadonnées
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();
                
                // Contrainte d'unicité pour éviter les doublons
                $table->unique(['student_id', 'evaluation_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_grades');
        Schema::dropIfExists('evaluations');
    }
}
