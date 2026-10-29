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
        Schema::table('esbtp_emploi_temps', function (Blueprint $table) {
            // Add missing columns
            $table->string('titre')->after('id');
            $table->string('semestre')->after('classe_id');
            $table->date('date_debut')->after('semestre');
            $table->date('date_fin')->after('date_debut');
            
            // Drop columns that are not used in the current model/controller
            $table->dropForeign(['matiere_id']);
            $table->dropColumn('matiere_id');
            $table->dropColumn('jour');
            $table->dropColumn('heure_debut');
            $table->dropColumn('heure_fin');
            $table->dropColumn('salle');
            $table->dropColumn('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esbtp_emploi_temps', function (Blueprint $table) {
            // Remove added columns
            $table->dropColumn('titre');
            $table->dropColumn('semestre');
            $table->dropColumn('date_debut');
            $table->dropColumn('date_fin');
            
            // Restore removed columns
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->onDelete('cascade');
            $table->string('jour');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->string('salle')->nullable();
            $table->text('notes')->nullable();
        });
    }
};
