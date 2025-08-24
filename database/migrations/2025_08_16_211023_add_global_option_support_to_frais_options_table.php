<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGlobalOptionSupportToFraisOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_frais_options', function (Blueprint $table) {
            // Ajouter le support pour les options globales
            $table->unsignedBigInteger('frais_category_id')->nullable()->after('configuration_id');
            $table->enum('option_type', ['class_based', 'global'])->default('class_based')->after('frais_category_id');
            
            // Ajouter la clé étrangère pour frais_category_id
            $table->foreign('frais_category_id')->references('id')->on('esbtp_frais_categories')->onDelete('cascade');
            
            // Ajouter des index pour les performances
            $table->index(['option_type', 'frais_category_id']);
            $table->index(['option_type', 'configuration_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_frais_options', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['option_type', 'frais_category_id']);
            $table->dropIndex(['option_type', 'configuration_id']);
            
            // Supprimer la clé étrangère
            $table->dropForeign(['frais_category_id']);
            
            // Supprimer les colonnes ajoutées
            $table->dropColumn(['frais_category_id', 'option_type']);
        });
    }
}
