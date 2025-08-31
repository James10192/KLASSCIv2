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
            // Numéro de bon unique pour les bons de sortie - ajouter seulement si n'existe pas
            if (!Schema::hasColumn('esbtp_depenses', 'numero_bon')) {
                $table->string('numero_bon', 50)->unique()->nullable()->after('reference');
            }

            // Statut du workflow d'approbation - ajouter seulement si n'existe pas
            if (!Schema::hasColumn('esbtp_depenses', 'statut_workflow')) {
                $table->enum('statut_workflow', ['brouillon', 'en_attente', 'approuve', 'paye', 'rejete'])
                      ->default('brouillon')->after('statut');
            }

            // Données JSON pour le workflow - ajouter seulement si n'existe pas
            if (!Schema::hasColumn('esbtp_depenses', 'workflow_data')) {
                $table->json('workflow_data')->nullable()->after('statut_workflow');
            }

            // Utilisateur qui a approuvé la dépense - ajouter seulement si n'existe pas
            if (!Schema::hasColumn('esbtp_depenses', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('validateur_id');
            }

            // Date d'approbation - ajouter seulement si n'existe pas
            if (!Schema::hasColumn('esbtp_depenses', 'date_approbation')) {
                $table->timestamp('date_approbation')->nullable()->after('approved_by');
            }

            // Clé étrangère pour approved_by - déjà créée par la migration précédente, pas besoin de la recréer
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
