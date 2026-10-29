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
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            // Ajouter la colonne annee_universitaire_id après etudiant_id
            $table->unsignedBigInteger('annee_universitaire_id')->nullable()->after('etudiant_id');

            // Ajouter la clé étrangère
            $table->foreign('annee_universitaire_id')
                  ->references('id')
                  ->on('esbtp_annee_universitaires')
                  ->onDelete('cascade');

            // Créer un index pour optimiser les requêtes de filtrage par année
            $table->index('annee_universitaire_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            // Supprimer la clé étrangère et l'index
            $table->dropForeign(['annee_universitaire_id']);
            $table->dropIndex(['annee_universitaire_id']);

            // Supprimer la colonne
            $table->dropColumn('annee_universitaire_id');
        });
    }
};
