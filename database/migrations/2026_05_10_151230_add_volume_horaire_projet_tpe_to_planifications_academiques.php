<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add `volume_horaire_projet` and `volume_horaire_tpe` to planifications.
     *
     * Required for UEMOA LMD curriculum entry (Projet, Travail Personnel Étudiant).
     * Existing BTS rows keep these at 0 so `validerCoherence()` and the total
     * accessor stay consistent — the model is updated to include both fields
     * in the same PR.
     */
    public function up(): void
    {
        Schema::table('esbtp_planifications_academiques', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_planifications_academiques', 'volume_horaire_projet')) {
                $table->integer('volume_horaire_projet')->default(0)->after('volume_horaire_tp')
                    ->comment('Heures de Projet (UEMOA LMD)');
            }
            if (!Schema::hasColumn('esbtp_planifications_academiques', 'volume_horaire_tpe')) {
                $table->integer('volume_horaire_tpe')->default(0)->after('volume_horaire_projet')
                    ->comment('Heures de Travail Personnel Etudiant (UEMOA LMD)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_planifications_academiques', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_planifications_academiques', 'volume_horaire_tpe')) {
                $table->dropColumn('volume_horaire_tpe');
            }
            if (Schema::hasColumn('esbtp_planifications_academiques', 'volume_horaire_projet')) {
                $table->dropColumn('volume_horaire_projet');
            }
        });
    }
};
