<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnneeDebutToEsbtpAnneeUniversitairesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_annee_universitaires', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_annee_universitaires', 'annee_debut')) {
                $table->year('annee_debut')->nullable()->after('name');
            }
            if (!Schema::hasColumn('esbtp_annee_universitaires', 'annee_fin')) {
                $table->year('annee_fin')->nullable()->after('annee_debut');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_annee_universitaires', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_annee_universitaires', 'annee_debut')) {
                $table->dropColumn('annee_debut');
            }
            if (Schema::hasColumn('esbtp_annee_universitaires', 'annee_fin')) {
                $table->dropColumn('annee_fin');
            }
        });
    }
}
