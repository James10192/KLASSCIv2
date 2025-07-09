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
            // Numéro de bon unique pour les bons de sortie
            $table->string('numero_bon', 50)->unique()->nullable()->after('reference');

            // Statut du workflow d'approbation
            $table->enum('statut_workflow', ['brouillon', 'en_attente', 'approuve', 'paye', 'rejete'])
                  ->default('brouillon')->after('statut');

            // Données JSON pour le workflow (historique, commentaires, etc.)
            $table->json('workflow_data')->nullable()->after('statut_workflow');

            // Utilisateur qui a approuvé la dépense
            $table->unsignedBigInteger('approved_by')->nullable()->after('validateur_id');

            // Date d'approbation
            $table->timestamp('date_approbation')->nullable()->after('approved_by');

            // Clé étrangère pour approved_by
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esbtp_depenses', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
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
