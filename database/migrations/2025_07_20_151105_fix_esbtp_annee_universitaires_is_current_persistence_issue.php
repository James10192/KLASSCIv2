<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixEsbtpAnneeUniversitairesIsCurrentPersistenceIssue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_annee_universitaires', function (Blueprint $table) {
            // Modifier la colonne is_current pour avoir une valeur par défaut explicite
            $table->boolean('is_current')->default(false)->change();
            
            // Ajouter un index pour améliorer les performances des requêtes
            $table->index('is_current');
        });
        
        // S'assurer qu'il n'y ait qu'une seule année en cours maximum
        DB::statement('UPDATE esbtp_annee_universitaires SET is_current = FALSE WHERE is_current IS NULL');
        
        // Vérifier s'il y a plusieurs années marquées comme courantes et corriger
        $currentYears = DB::table('esbtp_annee_universitaires')->where('is_current', true)->count();
        if ($currentYears > 1) {
            // Désactiver toutes les années courantes sauf la plus récente
            DB::statement('
                UPDATE esbtp_annee_universitaires 
                SET is_current = FALSE 
                WHERE is_current = TRUE 
                AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM esbtp_annee_universitaires 
                        WHERE is_current = TRUE 
                        ORDER BY start_date DESC, created_at DESC 
                        LIMIT 1
                    ) as subquery
                )
            ');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_annee_universitaires', function (Blueprint $table) {
            // Supprimer l'index ajouté
            $table->dropIndex(['is_current']);
            
            // Remettre la colonne sans valeur par défaut
            $table->boolean('is_current')->nullable()->change();
        });
    }
}
