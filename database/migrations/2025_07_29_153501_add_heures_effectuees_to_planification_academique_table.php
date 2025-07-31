<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHeuresEffectueesToPlanificationAcademiqueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_planifications_academiques', function (Blueprint $table) {
            $table->decimal('heures_effectuees', 5, 2)->default(0)->after('volume_horaire_total')
                  ->comment('Nombre d\'heures réellement effectuées basé sur les émargements validés');
            $table->timestamp('derniere_mise_a_jour_heures')->nullable()->after('heures_effectuees')
                  ->comment('Dernière mise à jour des heures effectuées');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_planifications_academiques', function (Blueprint $table) {
            $table->dropColumn(['heures_effectuees', 'derniere_mise_a_jour_heures']);
        });
    }
}
