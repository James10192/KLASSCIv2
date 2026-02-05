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
     * Migration esbtp_bulletins: periode (VARCHAR) → periode_id (FK NULLABLE) + type_bulletin (ENUM)
     *
     * État actuel (après CleanPeriodeDataSeeder):
     * - Colonne periode: VARCHAR ('semestre1', 'semestre2', NULL)
     *
     * Objectif:
     * - Ajouter colonne periode_id (FK vers esbtp_periodes, NULLABLE)
     * - Ajouter colonne type_bulletin ENUM('periode', 'annuel')
     * - Backfill periode_id et type_bulletin depuis periode + annee_universitaire_id
     * - Supprimer colonne periode
     *
     * Note:
     * - periode_id NULL = Bulletin annuel (type_bulletin='annuel')
     * - periode_id NOT NULL = Bulletin de période (type_bulletin='periode')
     *
     * @return void
     */
    public function up()
    {
        // Étape 1: Ajouter colonnes periode_id et type_bulletin
        Schema::table('esbtp_bulletins', function (Blueprint $table) {
            $table->unsignedBigInteger('periode_id')->nullable()->after('annee_universitaire_id');
            $table->enum('type_bulletin', ['periode', 'annuel'])->default('periode')->after('periode_id');
            $table->index('periode_id');
            $table->index('type_bulletin');
        });

        // Étape 2: Backfill periode_id et type_bulletin depuis periode + annee_universitaire_id
        // Bulletins de période (periode NOT NULL)
        $bulletinsPeriode = DB::table('esbtp_bulletins')
            ->whereNotNull('periode')
            ->get();

        foreach ($bulletinsPeriode as $bulletin) {
            // Déterminer l'ordre de la période (semestre1 = 1, semestre2 = 2)
            $ordre = ($bulletin->periode === 'semestre1') ? 1 : 2;

            // Trouver la période correspondante
            $periode = DB::table('esbtp_periodes')
                ->where('annee_universitaire_id', $bulletin->annee_universitaire_id)
                ->where('ordre', $ordre)
                ->first();

            if ($periode) {
                DB::table('esbtp_bulletins')
                    ->where('id', $bulletin->id)
                    ->update([
                        'periode_id' => $periode->id,
                        'type_bulletin' => 'periode'
                    ]);
            } else {
                \Log::warning("Période introuvable pour bulletin ID {$bulletin->id} (annee_universitaire_id={$bulletin->annee_universitaire_id}, ordre={$ordre})");
            }
        }

        // Bulletins annuels (periode NULL)
        DB::table('esbtp_bulletins')
            ->whereNull('periode')
            ->update([
                'periode_id' => null,
                'type_bulletin' => 'annuel'
            ]);

        // Étape 3: Ajouter contrainte FK (periode_id reste NULLABLE)
        Schema::table('esbtp_bulletins', function (Blueprint $table) {
            $table->foreign('periode_id')
                  ->references('id')
                  ->on('esbtp_periodes')
                  ->onDelete('set null');
        });

        // Étape 4: Supprimer colonne periode
        Schema::table('esbtp_bulletins', function (Blueprint $table) {
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
        Schema::table('esbtp_bulletins', function (Blueprint $table) {
            $table->string('periode', 20)->nullable()->after('annee_universitaire_id');
        });

        // Backfill depuis periode_id et type_bulletin
        // Bulletins de période
        $bulletinsPeriode = DB::table('esbtp_bulletins')
            ->where('type_bulletin', 'periode')
            ->whereNotNull('periode_id')
            ->join('esbtp_periodes', 'esbtp_bulletins.periode_id', '=', 'esbtp_periodes.id')
            ->select('esbtp_bulletins.id', 'esbtp_periodes.ordre')
            ->get();

        foreach ($bulletinsPeriode as $bulletin) {
            $periodeStr = "semestre{$bulletin->ordre}";

            DB::table('esbtp_bulletins')
                ->where('id', $bulletin->id)
                ->update(['periode' => $periodeStr]);
        }

        // Bulletins annuels (periode reste NULL)
        // Rien à faire, periode = NULL par défaut

        // Supprimer colonnes periode_id et type_bulletin
        Schema::table('esbtp_bulletins', function (Blueprint $table) {
            $table->dropForeign(['periode_id']);
            $table->dropColumn(['periode_id', 'type_bulletin']);
        });
    }
};
