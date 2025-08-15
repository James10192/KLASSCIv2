<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpSessionReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_session_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seance_cours_id');
            $table->unsignedBigInteger('teacher_id');
            $table->text('content_summary'); // Résumé du contenu enseigné (min 30 caractères)
            $table->text('teaching_methods')->nullable(); // Méthodes pédagogiques utilisées
            $table->enum('student_behavior', ['excellent', 'good', 'satisfactory', 'difficult'])->default('good'); // Comportement des étudiants
            $table->text('difficulties_encountered')->nullable(); // Difficultés rencontrées
            $table->text('next_session_preparation')->nullable(); // Préparation pour la prochaine séance
            $table->text('homework_assigned')->nullable(); // Devoirs donnés
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('seance_cours_id')->references('id')->on('esbtp_seance_cours')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            
            // Index pour les requêtes fréquentes
            $table->index(['teacher_id', 'status']);
            $table->index('seance_cours_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_session_reports');
    }
}
