<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Index for class-wide queries (ranks, stats)
        Schema::table('esbtp_lmd_bulletins', function (Blueprint $table) {
            $table->index(
                ['classe_id', 'annee_universitaire_id', 'semestre', 'moyenne_generale'],
                'lmd_bulletin_classe_sem_moy'
            );
        });

        // Index for ECUE stats queries
        Schema::table('esbtp_lmd_resultats_ecues', function (Blueprint $table) {
            $table->index(
                ['bulletin_id', 'matiere_id', 'moyenne'],
                'lmd_res_ecue_bulletin_matiere_moy'
            );
        });

        // Index for UE stats queries
        Schema::table('esbtp_lmd_resultats_ues', function (Blueprint $table) {
            $table->index(
                ['bulletin_id', 'unite_enseignement_id', 'moyenne'],
                'lmd_res_ue_bulletin_ue_moy'
            );
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_lmd_bulletins', function (Blueprint $table) {
            $table->dropIndex('lmd_bulletin_classe_sem_moy');
        });
        Schema::table('esbtp_lmd_resultats_ecues', function (Blueprint $table) {
            $table->dropIndex('lmd_res_ecue_bulletin_matiere_moy');
        });
        Schema::table('esbtp_lmd_resultats_ues', function (Blueprint $table) {
            $table->dropIndex('lmd_res_ue_bulletin_ue_moy');
        });
    }
};
