<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixOptionAssignmentsForeignKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_option_assignments', function (Blueprint $table) {
            // Supprimer l'ancienne contrainte de clé étrangère
            $table->dropForeign(['option_id']);
            
            // Ajouter la nouvelle contrainte vers esbtp_frais_options
            $table->foreign('option_id')->references('id')->on('esbtp_frais_options')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_option_assignments', function (Blueprint $table) {
            // Supprimer la nouvelle contrainte
            $table->dropForeign(['option_id']);
            
            // Remettre l'ancienne contrainte vers esbtp_frais_variants
            $table->foreign('option_id')->references('id')->on('esbtp_frais_variants')->onDelete('cascade');
        });
    }
}
