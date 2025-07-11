<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            // Ajout du workflow step pour suivre le processus d'inscription
            $table->enum('workflow_step', [
                'prospect', 
                'documents_complets', 
                'en_validation', 
                'valide', 
                'etudiant_cree'
            ])->default('prospect')->after('status');
            
            // Référence vers un paiement de validation spécifique
            $table->foreignId('paiement_validation_id')
                  ->nullable()
                  ->constrained('esbtp_paiements')
                  ->onDelete('set null')
                  ->after('workflow_step');
            
            // Classe alternative en cas de classe principale pleine
            $table->foreignId('classe_alternative_id')
                  ->nullable()
                  ->constrained('esbtp_classes')
                  ->onDelete('set null')
                  ->after('classe_id');
            
            // Flag pour activer/désactiver la comptabilité pour cette inscription
            $table->boolean('comptabilite_activee')->default(true)->after('paiement_validation_id');
            
            // Index pour optimiser les requêtes sur le workflow
            $table->index('workflow_step');
            $table->index(['workflow_step', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            // Supprimer les index d'abord
            $table->dropIndex(['workflow_step']);
            $table->dropIndex(['workflow_step', 'status']);
            
            // Supprimer les colonnes
            $table->dropForeign(['paiement_validation_id']);
            $table->dropForeign(['classe_alternative_id']);
            $table->dropColumn([
                'workflow_step',
                'paiement_validation_id', 
                'classe_alternative_id',
                'comptabilite_activee'
            ]);
        });
    }
};
