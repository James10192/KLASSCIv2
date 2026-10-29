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
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            // Ajouter la colonne pour la nouvelle relation
            $table->foreignId('frais_category_id')->nullable()->after('inscription_id')->constrained('esbtp_frais_categories')->onDelete('set null');
            
            // Ajouter un index pour optimiser les requêtes
            $table->index(['inscription_id', 'frais_category_id']);
            $table->index(['frais_category_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->dropForeign(['frais_category_id']);
            $table->dropIndex(['inscription_id', 'frais_category_id']);
            $table->dropIndex(['frais_category_id', 'status']);
            $table->dropColumn('frais_category_id');
        });
    }
};