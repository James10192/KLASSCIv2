<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAbandonTypeToEsbtpEtudiantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_etudiants', function (Blueprint $table) {
            $table->enum('abandon_type', ['annee_scolaire', 'ecole'])
                  ->nullable()
                  ->comment('Type abandon: annee_scolaire=abandon année courante, ecole=abandon établissement après année réussie');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_etudiants', function (Blueprint $table) {
            $table->dropColumn('abandon_type');
        });
    }
}
