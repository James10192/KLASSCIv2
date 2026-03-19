<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_lmd_resultats_ues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulletin_id')->constrained('esbtp_lmd_bulletins')->cascadeOnDelete();
            $table->foreignId('unite_enseignement_id')->constrained('esbtp_unites_enseignement')->cascadeOnDelete();
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->cascadeOnDelete();
            $table->decimal('moyenne', 5, 2)->nullable();               // Moyenne UE /20
            $table->string('statut')->default('NAQ');                   // AQ|NAQ|APC
            $table->string('mention')->nullable();                      // TB|B|AB|P
            $table->unsignedInteger('credit')->default(0);              // Credits CECT de l'UE
            $table->decimal('stat_min', 5, 2)->nullable();              // Min promo pour cette UE
            $table->decimal('stat_moy', 5, 2)->nullable();              // Moyenne promo
            $table->decimal('stat_max', 5, 2)->nullable();              // Max promo
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['bulletin_id', 'unite_enseignement_id'], 'lmd_res_ue_bulletin_ue_unique');
            $table->index(['etudiant_id', 'unite_enseignement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_lmd_resultats_ues');
    }
};
