<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsToEsbtpFiliereNiveauTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_filiere_niveau', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_filiere_niveau', 'filiere_id')) {
                $table->unsignedBigInteger('filiere_id')->after('id');
                $table->foreign('filiere_id')->references('id')->on('esbtp_filieres')->onDelete('cascade');
            }

            if (!Schema::hasColumn('esbtp_filiere_niveau', 'niveau_etude_id')) {
                $table->unsignedBigInteger('niveau_etude_id')->after('filiere_id');
                $table->foreign('niveau_etude_id')->references('id')->on('esbtp_niveau_etudes')->onDelete('cascade');
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
        Schema::table('esbtp_filiere_niveau', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_filiere_niveau', 'filiere_id')) {
                $table->dropForeign(['filiere_id']);
                $table->dropColumn('filiere_id');
            }

            if (Schema::hasColumn('esbtp_filiere_niveau', 'niveau_etude_id')) {
                $table->dropForeign(['niveau_etude_id']);
                $table->dropColumn('niveau_etude_id');
            }
        });
    }
}
