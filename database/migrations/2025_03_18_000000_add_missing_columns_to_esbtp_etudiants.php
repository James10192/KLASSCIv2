<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::table('esbtp_etudiants', function (Blueprint $table) {
            // Add classe_id if it doesn't exist
            if (!Schema::hasColumn('esbtp_etudiants', 'classe_id')) {
                $table->unsignedBigInteger('classe_id')->nullable()->after('user_id');
                $table->foreign('classe_id')->references('id')->on('esbtp_classes')->onDelete('set null');
            }

            // Add annee_universitaire_id if it doesn't exist
            if (!Schema::hasColumn('esbtp_etudiants', 'annee_universitaire_id')) {
                $table->unsignedBigInteger('annee_universitaire_id')->nullable()->after('classe_id');
                $table->foreign('annee_universitaire_id')->references('id')->on('esbtp_annee_universitaires')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('esbtp_etudiants', function (Blueprint $table) {
            // Drop foreign keys first
            if (Schema::hasColumn('esbtp_etudiants', 'annee_universitaire_id')) {
                $table->dropForeign(['annee_universitaire_id']);
                $table->dropColumn('annee_universitaire_id');
            }

            if (Schema::hasColumn('esbtp_etudiants', 'classe_id')) {
                $table->dropForeign(['classe_id']);
                $table->dropColumn('classe_id');
            }
        });
    }
};
