<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_frais_configurations', function (Blueprint $table) {
            // Ajouter 3 champs de montant pour les différents statuts d'affectation
            $table->decimal('amount_affecte', 12, 2)
                  ->nullable()
                  ->after('amount')
                  ->comment('Montant pour les étudiants affectés');
                  
            $table->decimal('amount_reaffecte', 12, 2)
                  ->nullable()
                  ->after('amount_affecte')
                  ->comment('Montant pour les étudiants réaffectés');
                  
            $table->decimal('amount_non_affecte', 12, 2)
                  ->nullable()
                  ->after('amount_reaffecte')
                  ->comment('Montant pour les étudiants non affectés');
            
            // Index composé pour optimiser les requêtes par filière, niveau et statut d'affectation
            $table->index(['filiere_id', 'niveau_id', 'frais_category_id'], 'idx_frais_config_affectation');
        });
        
        // Migration des données existantes : copier amount vers les 3 nouveaux champs
        DB::statement('UPDATE esbtp_frais_configurations SET 
            amount_affecte = amount,
            amount_reaffecte = amount, 
            amount_non_affecte = amount 
            WHERE amount_affecte IS NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_frais_configurations', function (Blueprint $table) {
            $table->dropIndex('idx_frais_config_affectation');
            $table->dropColumn([
                'amount_affecte',
                'amount_reaffecte', 
                'amount_non_affecte'
            ]);
        });
    }
};
