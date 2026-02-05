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
     * Migration esbtp_emploi_temps: semestre (VARCHAR) → periode_id (FK)
     *
     * État actuel (après CleanPeriodeDataSeeder):
     * - Colonne semestre: VARCHAR ('semestre1', 'semestre2')
     *
     * Objectif:
     * - Ajouter colonne periode_id (FK vers esbtp_periodes)
     * - Backfill periode_id depuis semestre + annee_universitaire_id
     * - Supprimer colonne semestre
     *
     * Note: Pas de contrainte UNIQUE à reconstruire sur cette table.
     *
     * @return void
     */
    public function up()
    {
        // Étape 1: Ajouter colonne periode_id (nullable temporairement)
        Schema::table('esbtp_emploi_temps', function (Blueprint $table) {
            $table->unsignedBigInteger('periode_id')->nullable()->after('annee_universitaire_id');
            $table->index('periode_id');
        });

        // Étape 2: Backfill periode_id depuis semestre + annee_universitaire_id
        $emploisTemps = DB::table('esbtp_emploi_temps')
            ->whereNotNull('semestre')
            ->get();

        foreach ($emploisTemps as $emploi) {
            // Déterminer l'ordre de la période (semestre1 = 1, semestre2 = 2)
            $ordre = ($emploi->semestre === 'semestre1') ? 1 : 2;

            // Trouver la période correspondante
            $periode = DB::table('esbtp_periodes')
                ->where('annee_universitaire_id', $emploi->annee_universitaire_id)
                ->where('ordre', $ordre)
                ->first();

            if ($periode) {
                DB::table('esbtp_emploi_temps')
                    ->where('id', $emploi->id)
                    ->update(['periode_id' => $periode->id]);
            } else {
                \Log::warning("Période introuvable pour emploi_temps ID {$emploi->id} (annee_universitaire_id={$emploi->annee_universitaire_id}, ordre={$ordre})");
            }
        }

        // Étape 3: Rendre periode_id NOT NULL
        Schema::table('esbtp_emploi_temps', function (Blueprint $table) {
            $table->unsignedBigInteger('periode_id')->nullable(false)->change();
            $table->foreign('periode_id')
                  ->references('id')
                  ->on('esbtp_periodes')
                  ->onDelete('cascade');
        });

        // Étape 4: Supprimer colonne semestre
        Schema::table('esbtp_emploi_temps', function (Blueprint $table) {
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
        Schema::table('esbtp_emploi_temps', function (Blueprint $table) {
            $table->string('semestre', 20)->nullable()->after('annee_universitaire_id');
        });

        // Backfill depuis periode_id
        $emploisTemps = DB::table('esbtp_emploi_temps')
            ->join('esbtp_periodes', 'esbtp_emploi_temps.periode_id', '=', 'esbtp_periodes.id')
            ->select('esbtp_emploi_temps.id', 'esbtp_periodes.ordre')
            ->get();

        foreach ($emploisTemps as $emploi) {
            $semestreStr = "semestre{$emploi->ordre}";

            DB::table('esbtp_emploi_temps')
                ->where('id', $emploi->id)
                ->update(['semestre' => $semestreStr]);
        }

        // Supprimer colonne periode_id
        Schema::table('esbtp_emploi_temps', function (Blueprint $table) {
            $table->dropForeign(['periode_id']);
            $table->dropColumn('periode_id');
        });
    }
};
