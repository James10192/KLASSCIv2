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
        Schema::table('esbtp_depenses', function (Blueprint $table) {
            // Ajout des colonnes pour le workflow de bons de sortie
            $table->string('numero_bon', 50)->unique()->nullable()->after('id');
            $table->enum('statut_workflow', ['brouillon', 'en_attente', 'approuve', 'paye', 'rejete'])
                  ->default('brouillon')->after('statut');
            $table->json('workflow_data')->nullable()->after('statut_workflow');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('date_approbation')->nullable();
            
            $table->index(['statut_workflow', 'created_at']);
            $table->index('numero_bon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esbtp_depenses', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['statut_workflow', 'created_at']);
            $table->dropIndex(['numero_bon']);
            $table->dropColumn([
                'numero_bon',
                'statut_workflow', 
                'workflow_data',
                'approved_by',
                'date_approbation'
            ]);
        });
    }
};
