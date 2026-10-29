<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_lmd_resultats_ecues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulletin_id')->constrained('esbtp_lmd_bulletins')->cascadeOnDelete();
            $table->foreignId('resultat_ue_id')->constrained('esbtp_lmd_resultats_ues')->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->cascadeOnDelete();  // ECUE = matiere
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->cascadeOnDelete();
            $table->decimal('moyenne', 5, 2)->nullable();               // Moyenne ECUE /20
            $table->unsignedInteger('credit')->default(0);              // Credits CECT de l'ECUE
            $table->unsignedInteger('rang')->nullable();                // Rang dans la promo
            $table->foreignId('enseignant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('stat_min', 5, 2)->nullable();
            $table->decimal('stat_moy', 5, 2)->nullable();
            $table->decimal('stat_max', 5, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['bulletin_id', 'matiere_id'], 'lmd_res_ecue_bulletin_matiere_unique');
            $table->index(['etudiant_id', 'matiere_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_lmd_resultats_ecues');
    }
};
