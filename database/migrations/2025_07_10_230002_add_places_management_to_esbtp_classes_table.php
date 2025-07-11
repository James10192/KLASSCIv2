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
        Schema::table('esbtp_classes', function (Blueprint $table) {
            // Renommer capacity en places_totales pour plus de clarté
            $table->renameColumn('capacity', 'places_totales');
        });
        
        Schema::table('esbtp_classes', function (Blueprint $table) {
            // Ajouter colonne pour les places occupées (calculée mais stockée pour performance)
            $table->integer('places_occupees')->default(0)->after('places_totales');
            
            // Note: places_disponibles sera une colonne calculée (accessor) dans le modèle
            // car c'est simplement places_totales - places_occupees
            
            // Index pour optimiser les requêtes sur les places
            $table->index('places_totales');
            $table->index('places_occupees');
            $table->index(['places_totales', 'places_occupees']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esbtp_classes', function (Blueprint $table) {
            // Supprimer les index d'abord
            $table->dropIndex(['places_totales']);
            $table->dropIndex(['places_occupees']);
            $table->dropIndex(['places_totales', 'places_occupees']);
            
            // Supprimer la colonne places_occupees
            $table->dropColumn('places_occupees');
        });
        
        Schema::table('esbtp_classes', function (Blueprint $table) {
            // Renommer places_totales en capacity
            $table->renameColumn('places_totales', 'capacity');
        });
    }
};
