<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddEstActifToEsbtpAnneeUniversitairesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_annee_universitaires', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_annee_universitaires', 'est_actif')) {
                $table->boolean('est_actif')->default(false);
            }
        });

        // Mettre à jour les enregistrements existants
        DB::statement('UPDATE esbtp_annee_universitaires SET est_actif = false WHERE est_actif IS NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_annee_universitaires', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_annee_universitaires', 'est_actif')) {
                $table->dropColumn('est_actif');
            }
        });
    }
}
