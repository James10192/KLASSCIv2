<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sous-lot α : ajoute colonne `periode` à esbtp_matiere_coefficients pour permettre
     * coefficients différents S1 vs S2.
     *
     * Backfill : pour chaque row existante (qui était "commune S1+S2"),
     *   1. UPDATE en place pour mettre periode='semestre1'
     *   2. INSERT une copie de chaque row avec periode='semestre2'
     * → comportement actuel "coeff commun" préservé après migration.
     *
     * Unique key recomposé : (matiere, filiere, niveau, annee, periode).
     */
    public function up()
    {
        if (!Schema::hasTable('esbtp_matiere_coefficients')) {
            return;
        }

        Schema::table('esbtp_matiere_coefficients', function (Blueprint $table) {
            try {
                $table->dropUnique('matiere_coeff_unique');
            } catch (\Throwable $e) {
                // déjà droppée ou nom différent — ignore
            }
        });

        Schema::table('esbtp_matiere_coefficients', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_matiere_coefficients', 'periode')) {
                $table->enum('periode', ['semestre1', 'semestre2'])
                    ->default('semestre1')
                    ->after('annee_universitaire_id');
            }
        });

        // Backfill : toutes les rows existantes deviennent S1, puis on duplique en S2
        DB::table('esbtp_matiere_coefficients')->update(['periode' => 'semestre1']);

        $rowsS1 = DB::table('esbtp_matiere_coefficients')
            ->where('periode', 'semestre1')
            ->get();

        $now = now();
        $batchS2 = [];
        foreach ($rowsS1 as $r) {
            $batchS2[] = [
                'matiere_id' => $r->matiere_id,
                'filiere_id' => $r->filiere_id,
                'niveau_etude_id' => $r->niveau_etude_id,
                'annee_universitaire_id' => $r->annee_universitaire_id,
                'periode' => 'semestre2',
                'coefficient' => $r->coefficient,
                'created_by' => $r->created_by,
                'updated_by' => $r->updated_by,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (count($batchS2) >= 500) {
                DB::table('esbtp_matiere_coefficients')->insert($batchS2);
                $batchS2 = [];
            }
        }
        if (!empty($batchS2)) {
            DB::table('esbtp_matiere_coefficients')->insert($batchS2);
        }

        Schema::table('esbtp_matiere_coefficients', function (Blueprint $table) {
            $table->unique(
                ['matiere_id', 'filiere_id', 'niveau_etude_id', 'annee_universitaire_id', 'periode'],
                'matiere_coeff_unique'
            );
        });
    }

    public function down()
    {
        if (!Schema::hasTable('esbtp_matiere_coefficients')) {
            return;
        }

        Schema::table('esbtp_matiere_coefficients', function (Blueprint $table) {
            try {
                $table->dropUnique('matiere_coeff_unique');
            } catch (\Throwable $e) {}
        });

        DB::table('esbtp_matiere_coefficients')->where('periode', 'semestre2')->delete();

        Schema::table('esbtp_matiere_coefficients', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_matiere_coefficients', 'periode')) {
                $table->dropColumn('periode');
            }
            $table->unique(
                ['matiere_id', 'filiere_id', 'niveau_etude_id', 'annee_universitaire_id'],
                'matiere_coeff_unique'
            );
        });
    }
};
