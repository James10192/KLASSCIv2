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
        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            $table->enum('affectation_status', ['affecté', 'réaffecté', 'non_affecté'])
                  ->default('affecté')
                  ->after('classe_id')
                  ->comment('Statut d\'affectation de l\'étudiant : affecté, réaffecté ou non affecté');
            
            // Index pour optimiser les requêtes par statut d'affectation
            $table->index(['affectation_status'], 'idx_inscriptions_affectation');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_inscriptions_affectation');
            $table->dropColumn('affectation_status');
        });
    }
};
