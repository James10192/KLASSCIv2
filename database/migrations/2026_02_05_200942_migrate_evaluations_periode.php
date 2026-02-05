<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migration esbtp_evaluations: periode (VARCHAR) → periode_id (FK NULLABLE)
     *
     * État actuel (après CleanPeriodeDataSeeder):
     * - Colonne periode: VARCHAR ('semestre1', 'semestre2', NULL)
     *
     * Objectif:
     * - Ajouter colonne periode_id (FK vers esbtp_periodes, NULLABLE)
     * - Backfill periode_id depuis periode + annee_universitaire_id
     * - Supprimer colonne periode
     *
     * Note: periode_id reste NULLABLE pour supporter les évaluations continues (pas liées à une période).
     *
     * @return void
     */
    public function up()
    {
        // Étape 1: Ajouter colonne periode_id (nullable)
        Schema::table('esbtp_evaluations', function (Blueprint $table) {
            $table->unsignedBigInteger('periode_id')->nullable()->after('annee_universitaire_id');
            $table->index('periode_id');
        });

        // Étape 2: Backfill periode_id depuis periode + annee_universitaire_id
        $evaluations = DB::table('esbtp_evaluations')
            ->whereNotNull('periode')
            ->get();

        foreach ($evaluations as $evaluation) {
            // Déterminer l'ordre de la période (semestre1 = 1, semestre2 = 2)
            $ordre = ($evaluation->periode === 'semestre1') ? 1 : 2;

            // Trouver la période correspondante
            $periode = DB::table('esbtp_periodes')
                ->where('annee_universitaire_id', $evaluation->annee_universitaire_id)
                ->where('ordre', $ordre)
                ->first();

            if ($periode) {
                DB::table('esbtp_evaluations')
                    ->where('id', $evaluation->id)
                    ->update(['periode_id' => $periode->id]);
            } else {
                \Log::warning("Période introuvable pour evaluation ID {$evaluation->id} (annee_universitaire_id={$evaluation->annee_universitaire_id}, ordre={$ordre})");
            }
        }

        // Étape 3: Ajouter contrainte FK (periode_id reste NULLABLE)
        Schema::table('esbtp_evaluations', function (Blueprint $table) {
            $table->foreign('periode_id')
                  ->references('id')
                  ->on('esbtp_periodes')
                  ->onDelete('set null'); // Si période supprimée, évaluation devient "continue"
        });

        // Étape 4: Supprimer colonne periode
        Schema::table('esbtp_evaluations', function (Blueprint $table) {
            $table->dropColumn('periode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Recréer colonne periode
        Schema::table('esbtp_evaluations', function (Blueprint $table) {
            $table->string('periode', 20)->nullable()->after('annee_universitaire_id');
        });

        // Backfill depuis periode_id
        $evaluations = DB::table('esbtp_evaluations')
            ->whereNotNull('periode_id')
            ->join('esbtp_periodes', 'esbtp_evaluations.periode_id', '=', 'esbtp_periodes.id')
            ->select('esbtp_evaluations.id', 'esbtp_periodes.ordre')
            ->get();

        foreach ($evaluations as $evaluation) {
            $periodeStr = "semestre{$evaluation->ordre}";

            DB::table('esbtp_evaluations')
                ->where('id', $evaluation->id)
                ->update(['periode' => $periodeStr]);
        }

        // Supprimer colonne periode_id
        Schema::table('esbtp_evaluations', function (Blueprint $table) {
            $table->dropForeign(['periode_id']);
            $table->dropColumn('periode_id');
        });
    }
};
