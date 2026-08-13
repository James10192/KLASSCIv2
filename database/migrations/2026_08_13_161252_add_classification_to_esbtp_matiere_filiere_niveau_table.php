<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_matiere_filiere_niveau', function (Blueprint $table) {
            // Statut d'une matière DANS un combo (filière, niveau) :
            //  - tronc_commun : matière du socle commun, remonte au bulletin TC
            //  - specialite   : matière de spécialité, exclue du bulletin TC
            //  - null         : non classée => comportement historique (aucune régression)
            // Grain (matière, filière, niveau) : une même matière peut être TC dans un
            // combo et spécialité dans un autre. BTS uniquement, LMD intouché.
            $table->enum('classification', ['tronc_commun', 'specialite'])
                ->nullable()
                ->after('niveau_etude_id');

            // Accélère le filtrage du resolver de bulletin par combo + statut.
            $table->index(['filiere_id', 'niveau_etude_id', 'classification'], 'idx_mfn_combo_classification');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_matiere_filiere_niveau', function (Blueprint $table) {
            $table->dropIndex('idx_mfn_combo_classification');
            $table->dropColumn('classification');
        });
    }
};
