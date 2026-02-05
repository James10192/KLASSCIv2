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
     * Migration esbtp_planifications_academiques: semestre (INT) → periode_id (FK)
     *
     * État actuel (après CleanPeriodeDataSeeder):
     * - Colonne semestre: INT (1, 2)
     * - Colonne semestre_str: VARCHAR ('semestre1', 'semestre2')
     *
     * Objectif:
     * - Ajouter colonne periode_id (FK vers esbtp_periodes)
     * - Backfill periode_id depuis semestre_str + annee_universitaire_id
     * - Supprimer colonnes semestre et semestre_str
     * - Recréer contrainte UNIQUE avec periode_id
     *
     * @return void
     */
    public function up()
    {
        // Étape 1: Ajouter colonne periode_id (nullable temporairement)
        Schema::table('esbtp_planifications_academiques', function (Blueprint $table) {
            $table->unsignedBigInteger('periode_id')->nullable()->after('annee_universitaire_id');
            $table->index('periode_id');
        });

        // Étape 2: Backfill periode_id depuis semestre_str + annee_universitaire_id
        $planifications = DB::table('esbtp_planifications_academiques')
            ->whereNotNull('semestre_str')
            ->get();

        foreach ($planifications as $planif) {
            // Déterminer l'ordre de la période (semestre1 = 1, semestre2 = 2)
            $ordre = ($planif->semestre_str === 'semestre1') ? 1 : 2;

            // Trouver la période correspondante
            $periode = DB::table('esbtp_periodes')
                ->where('annee_universitaire_id', $planif->annee_universitaire_id)
                ->where('ordre', $ordre)
                ->first();

            if ($periode) {
                DB::table('esbtp_planifications_academiques')
                    ->where('id', $planif->id)
                    ->update(['periode_id' => $periode->id]);
            } else {
                \Log::warning("Période introuvable pour planification ID {$planif->id} (annee_universitaire_id={$planif->annee_universitaire_id}, ordre={$ordre})");
            }
        }

        // Étape 3: Rendre periode_id NOT NULL
        Schema::table('esbtp_planifications_academiques', function (Blueprint $table) {
            $table->unsignedBigInteger('periode_id')->nullable(false)->change();
            $table->foreign('periode_id')
                  ->references('id')
                  ->on('esbtp_periodes')
                  ->onDelete('cascade');
        });

        // Étape 4: Supprimer ancienne contrainte UNIQUE (si elle existe)
        Schema::table('esbtp_planifications_academiques', function (Blueprint $table) {
            // Récupérer le nom de la contrainte UNIQUE existante
            $indexName = DB::select("SHOW INDEX FROM esbtp_planifications_academiques WHERE Column_name = 'matiere_id' AND Non_unique = 0");

            if (!empty($indexName)) {
                $table->dropUnique($indexName[0]->Key_name ?? 'esbtp_planifications_academiques_matiere_id_unique');
            }
        });

        // Étape 5: Créer nouvelle contrainte UNIQUE avec periode_id
        Schema::table('esbtp_planifications_academiques', function (Blueprint $table) {
            $table->unique(
                ['matiere_id', 'annee_universitaire_id', 'periode_id'],
                'unique_planif_matiere_annee_periode'
            );
        });

        // Étape 6: Supprimer colonnes semestre et semestre_str
        Schema::table('esbtp_planifications_academiques', function (Blueprint $table) {
            $table->dropColumn(['semestre', 'semestre_str']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Recréer colonnes semestre et semestre_str
        Schema::table('esbtp_planifications_academiques', function (Blueprint $table) {
            $table->tinyInteger('semestre')->unsigned()->nullable()->after('annee_universitaire_id');
            $table->string('semestre_str', 20)->nullable()->after('semestre');
        });

        // Backfill depuis periode_id
        $planifications = DB::table('esbtp_planifications_academiques')
            ->join('esbtp_periodes', 'esbtp_planifications_academiques.periode_id', '=', 'esbtp_periodes.id')
            ->select('esbtp_planifications_academiques.id', 'esbtp_periodes.ordre')
            ->get();

        foreach ($planifications as $planif) {
            $semestre = $planif->ordre;
            $semestreStr = "semestre{$semestre}";

            DB::table('esbtp_planifications_academiques')
                ->where('id', $planif->id)
                ->update([
                    'semestre' => $semestre,
                    'semestre_str' => $semestreStr
                ]);
        }

        // Supprimer nouvelle contrainte UNIQUE
        Schema::table('esbtp_planifications_academiques', function (Blueprint $table) {
            $table->dropUnique('unique_planif_matiere_annee_periode');
        });

        // Recréer ancienne contrainte UNIQUE (approximative)
        Schema::table('esbtp_planifications_academiques', function (Blueprint $table) {
            $table->unique(['matiere_id', 'annee_universitaire_id', 'semestre']);
        });

        // Supprimer colonne periode_id
        Schema::table('esbtp_planifications_academiques', function (Blueprint $table) {
            $table->dropForeign(['periode_id']);
            $table->dropColumn('periode_id');
        });
    }
};
