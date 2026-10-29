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
        if (Schema::hasTable('esbtp_evaluations') && !Schema::hasColumn('esbtp_evaluations', 'periode')) {
            Schema::table('esbtp_evaluations', function (Blueprint $table) {
                // Ajouter la colonne periode pour identifier à quelle période appartient l'évaluation
                $table->string('periode')->default('semestre1')->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('esbtp_evaluations') && Schema::hasColumn('esbtp_evaluations', 'periode')) {
            Schema::table('esbtp_evaluations', function (Blueprint $table) {
                $table->dropColumn('periode');
            });
        }
    }
};
