<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpConfigMatiereTypeFormationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_config_matiere_type_formations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->string('periode')->default('annuel'); // semestre1, semestre2, annuel
            $table->json('configuration'); // stocke la configuration JSON des matières par type (general et technique)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Ajouter les contraintes de clé étrangère séparément avec des noms d'index courts
            $table->foreign('classe_id', 'ecmtf_classe_id_foreign')
                ->references('id')
                ->on('esbtp_classes')
                ->onDelete('cascade');

            $table->foreign('annee_universitaire_id', 'ecmtf_annee_id_foreign')
                ->references('id')
                ->on('esbtp_annee_universitaires')
                ->onDelete('cascade');

            $table->foreign('created_by', 'ecmtf_created_by_foreign')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('updated_by', 'ecmtf_updated_by_foreign')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Ajouter un index unique pour éviter les doublons avec un nom court
            $table->unique(['classe_id', 'annee_universitaire_id', 'periode'], 'ecmtf_unique_config');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_config_matiere_type_formations');
    }
}
