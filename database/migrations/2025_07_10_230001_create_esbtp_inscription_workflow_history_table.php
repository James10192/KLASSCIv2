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
        Schema::create('esbtp_inscription_workflow_history', function (Blueprint $table) {
            $table->id();
            
            // Référence vers l'inscription concernée
            $table->foreignId('inscription_id')
                  ->constrained('esbtp_inscriptions')
                  ->onDelete('cascade');
            
            // Étape du workflow (from et to pour tracer les transitions)
            $table->enum('etape_from', [
                'prospect', 
                'documents_complets', 
                'en_validation', 
                'valide', 
                'etudiant_cree'
            ])->nullable();
            
            $table->enum('etape_to', [
                'prospect', 
                'documents_complets', 
                'en_validation', 
                'valide', 
                'etudiant_cree'
            ]);
            
            // Action effectuée
            $table->string('action', 100); // ex: 'creation', 'validation', 'rejet', 'creation_etudiant'
            
            // Utilisateur qui a effectué l'action
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('restrict');
            
            // Timestamp de l'action
            $table->timestamp('action_timestamp')->useCurrent();
            
            // Commentaires ou notes sur l'action
            $table->text('commentaires')->nullable();
            
            // Métadonnées JSON pour informations supplémentaires
            $table->json('metadata')->nullable();
            
            // Adresse IP pour audit
            $table->string('ip_address', 45)->nullable();
            
            // User agent pour audit
            $table->string('user_agent')->nullable();
            
            $table->timestamps();
            
            // Index pour performance
            $table->index('inscription_id');
            $table->index('action_timestamp');
            $table->index(['inscription_id', 'action_timestamp'], 'inscription_workflow_history_idx');
            $table->index('user_id');
            $table->index('etape_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_inscription_workflow_history');
    }
};
