<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnneeUniversitaireIdToEsbtpClassesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('esbtp_classes', function (Blueprint $table) {
            $table->foreignId('annee_universitaire_id')->after('niveau_etude_id')
                  ->constrained('esbtp_annee_universitaires')
                  ->onDelete('cascade');

            // Add index for better performance
            $table->index('annee_universitaire_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esbtp_classes', function (Blueprint $table) {
            $table->dropForeign(['annee_universitaire_id']);
            $table->dropIndex(['annee_universitaire_id']);
            $table->dropColumn('annee_universitaire_id');
        });
    }
}
