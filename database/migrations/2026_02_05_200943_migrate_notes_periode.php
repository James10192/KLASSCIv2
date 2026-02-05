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
     * Migration esbtp_notes: semestre (VARCHAR) → periode_id (FK NULLABLE)
     *
     * État actuel (après CleanPeriodeDataSeeder):
     * - Colonne semestre: VARCHAR ('semestre1', 'semestre2', NULL)
     *
     * Objectif:
     * - Ajouter colonne periode_id (FK vers esbtp_periodes, NULLABLE)
     * - Backfill periode_id depuis semestre + evaluation.annee_universitaire_id
     * - Supprimer colonne semestre
     *
     * Note: periode_id reste NULLABLE car les notes héritent de leur evaluation.periode_id.
     *
     * @return void
     */
    public function up()
    {
        // Étape 1: Ajouter colonne periode_id (nullable)
        Schema::table('esbtp_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('periode_id')->nullable()->after('evaluation_id');
            $table->index('periode_id');
        });

        // Étape 2: Backfill periode_id depuis semestre + evaluation.annee_universitaire_id
        $notes = DB::table('esbtp_notes')
            ->join('esbtp_evaluations', 'esbtp_notes.evaluation_id', '=', 'esbtp_evaluations.id')
            ->whereNotNull('esbtp_notes.semestre')
            ->select('esbtp_notes.id', 'esbtp_notes.semestre', 'esbtp_evaluations.annee_universitaire_id')
            ->get();

        foreach ($notes as $note) {
            // Déterminer l'ordre de la période (semestre1 = 1, semestre2 = 2)
            $ordre = ($note->semestre === 'semestre1') ? 1 : 2;

            // Trouver la période correspondante
            $periode = DB::table('esbtp_periodes')
                ->where('annee_universitaire_id', $note->annee_universitaire_id)
                ->where('ordre', $ordre)
                ->first();

            if ($periode) {
                DB::table('esbtp_notes')
                    ->where('id', $note->id)
                    ->update(['periode_id' => $periode->id]);
            } else {
                \Log::warning("Période introuvable pour note ID {$note->id} (annee_universitaire_id={$note->annee_universitaire_id}, ordre={$ordre})");
            }
        }

        // Étape 3: Ajouter contrainte FK (periode_id reste NULLABLE)
        Schema::table('esbtp_notes', function (Blueprint $table) {
            $table->foreign('periode_id')
                  ->references('id')
                  ->on('esbtp_periodes')
                  ->onDelete('set null');
        });

        // Étape 4: Supprimer colonne semestre
        Schema::table('esbtp_notes', function (Blueprint $table) {
            $table->dropColumn('semestre');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Recréer colonne semestre
        Schema::table('esbtp_notes', function (Blueprint $table) {
            $table->string('semestre', 20)->nullable()->after('evaluation_id');
        });

        // Backfill depuis periode_id
        $notes = DB::table('esbtp_notes')
            ->whereNotNull('periode_id')
            ->join('esbtp_periodes', 'esbtp_notes.periode_id', '=', 'esbtp_periodes.id')
            ->select('esbtp_notes.id', 'esbtp_periodes.ordre')
            ->get();

        foreach ($notes as $note) {
            $semestreStr = "semestre{$note->ordre}";

            DB::table('esbtp_notes')
                ->where('id', $note->id)
                ->update(['semestre' => $semestreStr]);
        }

        // Supprimer colonne periode_id
        Schema::table('esbtp_notes', function (Blueprint $table) {
            $table->dropForeign(['periode_id']);
            $table->dropColumn('periode_id');
        });
    }
};
