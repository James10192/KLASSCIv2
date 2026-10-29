<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpCoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_cours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->onDelete('cascade');
            $table->foreignId('classe_id')->constrained('esbtp_classes')->onDelete('cascade');
            $table->foreignId('enseignant_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires')->onDelete('cascade');
            $table->string('jour', 20)->comment('Jour de la semaine');
            $table->time('heure_debut')->comment('Heure de début du cours');
            $table->time('heure_fin')->comment('Heure de fin du cours');
            $table->string('salle')->nullable()->comment('Salle où se déroule le cours');
            $table->enum('type', ['CM', 'TD', 'TP'])->default('CM')->comment('Type de cours: CM (Cours Magistral), TD (Travaux Dirigés), TP (Travaux Pratiques)');
            $table->string('periode', 50)->nullable()->comment('Période académique (S1, S2, etc.)');
            $table->boolean('is_active')->default(true)->comment('Indique si le cours est actif');
            $table->text('commentaire')->nullable()->comment('Commentaire ou note sur le cours');
            $table->timestamps();
            $table->softDeletes();
            
            // Contrainte d'unicité pour éviter les doublons
            $table->unique(['matiere_id', 'classe_id', 'jour', 'heure_debut'], 'cours_unique');
            
            // Logging
            \Log::info('Table esbtp_cours créée avec succès.');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_cours');
        
        // Logging
        \Log::info('Table esbtp_cours supprimée avec succès.');
    }
}
