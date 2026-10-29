<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlignSemestrePeriodeTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Normalisez les valeurs dans esbtp_evaluations.periode si la table et la colonne existent
        if (Schema::hasTable('esbtp_evaluations') && Schema::hasColumn('esbtp_evaluations', 'periode')) {
            DB::statement("UPDATE esbtp_evaluations SET periode = 'semestre1' WHERE periode = 's1' OR periode = 'S1'");
            DB::statement("UPDATE esbtp_evaluations SET periode = 'semestre2' WHERE periode = 's2' OR periode = 'S2'");
        }

        // 2. Normalisez les valeurs dans esbtp_notes.semestre si la table et la colonne existent
        if (Schema::hasTable('esbtp_notes') && Schema::hasColumn('esbtp_notes', 'semestre')) {
            DB::statement("UPDATE esbtp_notes SET semestre = 'semestre1' WHERE semestre = 's1' OR semestre = 'S1'");
            DB::statement("UPDATE esbtp_notes SET semestre = 'semestre2' WHERE semestre = 's2' OR semestre = 'S2'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Cette migration ne peut pas être inversée de manière raisonnable
    }
}
