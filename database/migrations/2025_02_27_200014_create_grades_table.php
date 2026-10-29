<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('grades')) {
            Schema::create('grades', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->comment('Étudiant concerné')
                      ->constrained('students')->cascadeOnDelete();
                $table->foreignId('evaluation_id')->comment('Évaluation concernée')
                      ->constrained('evaluations')->cascadeOnDelete();
                $table->float('score', 8, 2)->nullable()->comment('Note obtenue');
                $table->text('comments')->nullable()->comment('Commentaires sur la note');
                $table->boolean('is_absent')->default(false)->comment('Si l\'étudiant était absent');
                $table->boolean('is_excused')->default(false)->comment('Si l\'absence est justifiée');
                $table->foreignId('created_by')->nullable()->comment('Utilisateur ayant créé la note')
                      ->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->comment('Utilisateur ayant mis à jour la note')
                      ->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                
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
        Schema::dropIfExists('grades');
    }
}
