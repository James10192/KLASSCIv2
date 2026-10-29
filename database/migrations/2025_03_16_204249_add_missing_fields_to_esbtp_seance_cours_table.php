<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingFieldsToEsbtpSeanceCoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            // Ajouter le champ is_active s'il n'existe pas
            if (!Schema::hasColumn('esbtp_seance_cours', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }

            // Ajouter le champ type_seance s'il n'existe pas
            if (!Schema::hasColumn('esbtp_seance_cours', 'type_seance')) {
                $table->string('type_seance')->default('cours')->after('is_active');
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
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            // Supprimer les champs ajoutés
            $table->dropColumn(['is_active', 'type_seance']);
        });
    }
}
