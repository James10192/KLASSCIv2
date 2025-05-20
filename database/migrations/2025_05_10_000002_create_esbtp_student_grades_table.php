<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpStudentGradesTable extends Migration
{
    public function up()
    {
        // Vérification de l'existence de la table avant création
        if (!Schema::hasTable('esbtp_student_grades')) {
            Schema::create('esbtp_student_grades', function (Blueprint $table) {
                $table->id();

                // Relations
                $table->foreignId('student_id')->constrained('esbtp_etudiants')->onDelete('cascade');
                $table->foreignId('evaluation_id')->constrained('esbtp_evaluations')->onDelete('cascade');

                // Données de note
                $table->decimal('grade', 5, 2)->nullable();
                $table->enum('status', ['present', 'absent', 'exempt'])->default('present');
                $table->text('comment')->nullable();

                // Métadonnées
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();

                // Contrainte d'unicité
                $table->unique(['student_id', 'evaluation_id'], 'student_evaluation_unique');
            });

            // Migration corrective si la table existe déjà
            Schema::table('esbtp_student_grades', function (Blueprint $table) {
                if (!Schema::hasColumn('esbtp_student_grades', 'status')) {
                    $table->enum('status', ['present', 'absent', 'exempt'])->default('present')->after('grade');
                }

                // Ajoutez ici d'autres colonnes manquantes si nécessaire
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_student_grades');
    }
}
// class CreateEsbtpStudentGradesTable extends Migration
// {
//     /**
//      * Run the migrations.
//      *
//      * @return void
//      */
//     public function up()
//     {
//         Schema::create('student_grades', function (Blueprint $table) {
//             $table->id();

//             // Relations
//             $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
//             $table->foreignId('evaluation_id')->constrained('evaluations')->onDelete('cascade');

//             // Données de note
//             $table->decimal('grade', 5, 2)->nullable();
//             $table->enum('status', ['present', 'absent', 'exempt'])->default('present');
//             $table->text('comment')->nullable();

//             // Métadonnées
//             $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
//             $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
//             $table->timestamps();
//             $table->softDeletes();

//             // Contrainte d'unicité pour éviter les doublons
//             $table->unique(['student_id', 'evaluation_id']);
//         });
//     }

//     /**
//      * Reverse the migrations.
//      *
//      * @return void
//      */
//     public function down()
//     {
//         Schema::dropIfExists('student_grades');
//     }
// }
