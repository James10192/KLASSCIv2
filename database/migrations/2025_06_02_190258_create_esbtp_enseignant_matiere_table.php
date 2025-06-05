<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('esbtp_enseignant_matiere', function (Blueprint $table) {
            $table->id();

            // Clés étrangères
            $table->unsignedBigInteger('enseignant_id');
            $table->unsignedBigInteger('matiere_id');
            $table->unsignedBigInteger('annee_universitaire_id');

            // Champs additionnels
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->integer('heures_prevues')->nullable();
            $table->integer('heures_effectuees')->default(0);

            $table->timestamps();

            // Index et contraintes
            $table->unique(['enseignant_id', 'matiere_id', 'annee_universitaire_id'], 'unique_enseignant_matiere_annee');
            $table->index(['enseignant_id', 'is_active']);
            $table->index(['matiere_id', 'is_active']);
            $table->index('annee_universitaire_id');

            // Clés étrangères
            $table->foreign('enseignant_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('matiere_id')->references('id')->on('esbtp_matieres')->onDelete('cascade');
            $table->foreign('annee_universitaire_id')->references('id')->on('esbtp_annee_universitaires')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_enseignant_matiere');
    }
};
