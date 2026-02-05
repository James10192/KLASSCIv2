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
     * Migration esbtp_resultats: periode (VARCHAR) → periode_id (FK NULLABLE)
     *
     * État actuel (après CleanPeriodeDataSeeder):
     * - Colonne periode: VARCHAR ('semestre1', 'semestre2', NULL)
     * - Table vide en production (structure seulement)
     *
     * Objectif:
     * - Ajouter colonne periode_id (FK vers esbtp_periodes, NULLABLE)
     * - Backfill periode_id si données existantes
     * - Supprimer colonne periode
     * - Recréer contrainte UNIQUE avec periode_id
     *
     * @return void
     */
    public function up()
    {
        // Étape 1: Ajouter colonne periode_id (nullable)
        Schema::table('esbtp_resultats', function (Blueprint $table) {
            $table->unsignedBigInteger('periode_id')->nullable()->after('annee_universitaire_id');
            $table->index('periode_id');
        });

        // Étape 2: Backfill periode_id depuis periode + annee_universitaire_id (si données existantes)
        $resultats = DB::table('esbtp_resultats')
            ->whereNotNull('periode')
            ->get();

        foreach ($resultats as $resultat) {
            // Déterminer l'ordre de la période (semestre1 = 1, semestre2 = 2)
            $ordre = ($resultat->periode === 'semestre1') ? 1 : 2;

            // Trouver la période correspondante
            $periode = DB::table('esbtp_periodes')
                ->where('annee_universitaire_id', $resultat->annee_universitaire_id)
                ->where('ordre', $ordre)
                ->first();

            if ($periode) {
                DB::table('esbtp_resultats')
                    ->where('id', $resultat->id)
                    ->update(['periode_id' => $periode->id]);
            } else {
                \Log::warning("Période introuvable pour resultat ID {$resultat->id} (annee_universitaire_id={$resultat->annee_universitaire_id}, ordre={$ordre})");
            }
        }

        // Étape 3: Ajouter contrainte FK (periode_id reste NULLABLE)
        Schema::table('esbtp_resultats', function (Blueprint $table) {
            $table->foreign('periode_id')
                  ->references('id')
                  ->on('esbtp_periodes')
                  ->onDelete('set null');
        });

        // Étape 4: Supprimer ancienne contrainte UNIQUE (si elle existe)
        Schema::table('esbtp_resultats', function (Blueprint $table) {
            $indexName = DB::select("SHOW INDEX FROM esbtp_resultats WHERE Column_name = 'periode' AND Non_unique = 0");

            if (!empty($indexName)) {
                $table->dropUnique($indexName[0]->Key_name);
            }
        });

        // Étape 5: Créer nouvelle contrainte UNIQUE avec periode_id
        Schema::table('esbtp_resultats', function (Blueprint $table) {
            $table->unique(
                ['etudiant_id', 'matiere_id', 'annee_universitaire_id', 'periode_id'],
                'unique_resultat_etudiant_matiere_annee_periode'
            );
        });

        // Étape 6: Supprimer colonne periode
        Schema::table('esbtp_resultats', function (Blueprint $table) {
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
        Schema::table('esbtp_resultats', function (Blueprint $table) {
            $table->string('periode', 20)->nullable()->after('annee_universitaire_id');
        });

        // Backfill depuis periode_id
        $resultats = DB::table('esbtp_resultats')
            ->whereNotNull('periode_id')
            ->join('esbtp_periodes', 'esbtp_resultats.periode_id', '=', 'esbtp_periodes.id')
            ->select('esbtp_resultats.id', 'esbtp_periodes.ordre')
            ->get();

        foreach ($resultats as $resultat) {
            $periodeStr = "semestre{$resultat->ordre}";

            DB::table('esbtp_resultats')
                ->where('id', $resultat->id)
                ->update(['periode' => $periodeStr]);
        }

        // Supprimer nouvelle contrainte UNIQUE
        Schema::table('esbtp_resultats', function (Blueprint $table) {
            $table->dropUnique('unique_resultat_etudiant_matiere_annee_periode');
        });

        // Recréer ancienne contrainte UNIQUE
        Schema::table('esbtp_resultats', function (Blueprint $table) {
            $table->unique(['etudiant_id', 'matiere_id', 'annee_universitaire_id', 'periode']);
        });

        // Supprimer colonne periode_id
        Schema::table('esbtp_resultats', function (Blueprint $table) {
            $table->dropForeign(['periode_id']);
            $table->dropColumn('periode_id');
        });
    }
};
