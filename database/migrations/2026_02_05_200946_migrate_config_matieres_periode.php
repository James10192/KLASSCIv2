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
     * Migration esbtp_config_matieres: semestre (INT) → periode_id (FK NULLABLE)
     *
     * État actuel (après CleanPeriodeDataSeeder):
     * - Colonne semestre: INT (1, 2, NULL)
     * - Colonne semestre_str: VARCHAR ('semestre1', 'semestre2', NULL)
     *
     * Objectif:
     * - Ajouter colonne periode_id (FK vers esbtp_periodes, NULLABLE)
     * - Backfill periode_id depuis semestre_str + annee_universitaire_id
     * - Supprimer colonnes semestre et semestre_str
     * - Recréer contrainte UNIQUE avec periode_id
     *
     * Note: periode_id peut être NULL pour les matières annuelles.
     *
     * @return void
     */
    public function up()
    {
        // Étape 1: Ajouter colonne periode_id (nullable)
        Schema::table('esbtp_config_matieres', function (Blueprint $table) {
            $table->unsignedBigInteger('periode_id')->nullable()->after('annee_universitaire_id');
            $table->index('periode_id');
        });

        // Étape 2: Backfill periode_id depuis semestre_str + annee_universitaire_id
        $configMatieres = DB::table('esbtp_config_matieres')
            ->whereNotNull('semestre_str')
            ->get();

        foreach ($configMatieres as $config) {
            // Déterminer l'ordre de la période (semestre1 = 1, semestre2 = 2)
            $ordre = ($config->semestre_str === 'semestre1') ? 1 : 2;

            // Trouver la période correspondante
            $periode = DB::table('esbtp_periodes')
                ->where('annee_universitaire_id', $config->annee_universitaire_id)
                ->where('ordre', $ordre)
                ->first();

            if ($periode) {
                DB::table('esbtp_config_matieres')
                    ->where('id', $config->id)
                    ->update(['periode_id' => $periode->id]);
            } else {
                \Log::warning("Période introuvable pour config_matiere ID {$config->id} (annee_universitaire_id={$config->annee_universitaire_id}, ordre={$ordre})");
            }
        }

        // Étape 3: Ajouter contrainte FK (periode_id reste NULLABLE)
        Schema::table('esbtp_config_matieres', function (Blueprint $table) {
            $table->foreign('periode_id')
                  ->references('id')
                  ->on('esbtp_periodes')
                  ->onDelete('set null');
        });

        // Étape 4: Supprimer ancienne contrainte UNIQUE (si elle existe)
        Schema::table('esbtp_config_matieres', function (Blueprint $table) {
            $indexName = DB::select("SHOW INDEX FROM esbtp_config_matieres WHERE Column_name = 'matiere_id' AND Non_unique = 0");

            if (!empty($indexName)) {
                $table->dropUnique($indexName[0]->Key_name ?? 'esbtp_config_matieres_unique');
            }
        });

        // Étape 5: Créer nouvelle contrainte UNIQUE avec periode_id
        Schema::table('esbtp_config_matieres', function (Blueprint $table) {
            $table->unique(
                ['classe_id', 'annee_universitaire_id', 'periode_id', 'matiere_id'],
                'unique_config_matiere_classe_annee_periode'
            );
        });

        // Étape 6: Supprimer colonnes semestre et semestre_str
        Schema::table('esbtp_config_matieres', function (Blueprint $table) {
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
        Schema::table('esbtp_config_matieres', function (Blueprint $table) {
            $table->tinyInteger('semestre')->unsigned()->nullable()->after('annee_universitaire_id');
            $table->string('semestre_str', 20)->nullable()->after('semestre');
        });

        // Backfill depuis periode_id
        $configMatieres = DB::table('esbtp_config_matieres')
            ->whereNotNull('periode_id')
            ->join('esbtp_periodes', 'esbtp_config_matieres.periode_id', '=', 'esbtp_periodes.id')
            ->select('esbtp_config_matieres.id', 'esbtp_periodes.ordre')
            ->get();

        foreach ($configMatieres as $config) {
            $semestre = $config->ordre;
            $semestreStr = "semestre{$semestre}";

            DB::table('esbtp_config_matieres')
                ->where('id', $config->id)
                ->update([
                    'semestre' => $semestre,
                    'semestre_str' => $semestreStr
                ]);
        }

        // Supprimer nouvelle contrainte UNIQUE
        Schema::table('esbtp_config_matieres', function (Blueprint $table) {
            $table->dropUnique('unique_config_matiere_classe_annee_periode');
        });

        // Recréer ancienne contrainte UNIQUE
        Schema::table('esbtp_config_matieres', function (Blueprint $table) {
            $table->unique(['classe_id', 'annee_universitaire_id', 'semestre', 'matiere_id']);
        });

        // Supprimer colonne periode_id
        Schema::table('esbtp_config_matieres', function (Blueprint $table) {
            $table->dropForeign(['periode_id']);
            $table->dropColumn('periode_id');
        });
    }
};
